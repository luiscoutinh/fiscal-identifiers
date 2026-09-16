# Fiscal Identifiers

[![CI](https://github.com/luiscoutinh/fiscal-identifiers/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/luiscoutinh/fiscal-identifiers/actions/workflows/ci.yml) [![Coverage](https://raw.githubusercontent.com/luiscoutinh/fiscal-identifiers/coverage-badges/coverage.svg)](https://github.com/luiscoutinh/fiscal-identifiers/actions/workflows/ci.yml) [![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A framework-agnostic PHP library for normalizing and validating fiscal identifiers across jurisdictions.

> **Status: early development.** Portugal, Brazil, Spain, Germany and the United Kingdom are currently implemented. The public API may still change before the first stable release. Validation is deterministic and local; authoritative registry checks such as VIES are deliberately kept outside the validation pipeline.

## Why this package exists

A fiscal identifier is not one universal thing. Different jurisdictions distinguish between people, companies, VAT registrations, local tax-office identifiers and other schemes. Even within the same country, one subject can have several identifiers with different purposes.

This package therefore keeps four concepts separate:

```text
country
  -> subject
      -> identifier type
          -> optional category
```

- **Country** identifies the jurisdiction, using ISO 3166-1 alpha-2 codes such as `PT`, `BR`, `ES`, `DE` or `GB`.
- **Subject** describes who or what is being identified, such as `person` or `company`.
- **Identifier type** is the concrete fiscal scheme, such as `nif`, `cnpj`, `nie`, `ust_idnr` or `utr`.
- **Category** is an optional jurisdiction-specific subdivision encoded inside an identifier, such as the Spanish entity-class letter `B`.

Subjects are a convenience layer. They do not replace identifier types. A country may map `company` to one general identifier while still exposing additional purpose-specific identifiers explicitly.

## Supported jurisdictions

| Country | Subject resolution | Identifier types | Notes |
| --- | --- | --- | --- |
| **Portugal (`PT`)** | `person -> nif`, `company -> nipc` | `nif`, `nipc` | Both use the Portuguese nine-digit local format/checksum. |
| **Brazil (`BR`)** | `person -> cpf`, `company -> cnpj` | `cpf`, `cnpj` | CNPJ supports both numeric and current alphanumeric forms. |
| **Spain (`ES`)** | `company -> entity_nif`; generic `person` is intentionally ambiguous | `dni_nif`, `nie`, `entity_nif` | Entity NIF exposes the official entity-class letter as an optional category. |
| **Germany (`DE`)** | `person -> idnr`, `company -> widnr` | `idnr`, `widnr`, `ust_idnr`, `steuernummer` | One company can have several identifiers with different purposes. |
| **United Kingdom (`GB`)** | `person -> utr`, `company -> utr` | `utr`, `vat_registration_number`, `employer_paye_reference` | Companies House company numbers are intentionally kept outside fiscal-identifier validation. |

Detailed jurisdiction notes and authoritative sources live under [`docs/jurisdictions`](docs/jurisdictions/README.md).

## Core API

Applications register the country definitions they need and then validate either by explicit identifier type or by subject.

```php
use FiscalIdentifiers\Countries\BR\Brazil;
use FiscalIdentifiers\Countries\DE\Germany;
use FiscalIdentifiers\Countries\ES\Spain;
use FiscalIdentifiers\Countries\GB\GreatBritain;
use FiscalIdentifiers\Countries\PT\Portugal;
use FiscalIdentifiers\FiscalIdentifierValidator;
use FiscalIdentifiers\Registry\CountryRegistry;

$countries = new CountryRegistry();
$countries->register(Portugal::definition());
$countries->register(Brazil::definition());
$countries->register(Spain::definition());
$countries->register(Germany::definition());
$countries->register(GreatBritain::definition());

$validator = new FiscalIdentifierValidator($countries);
```

### Validate an explicit identifier type

Use `validate()` with an identifier type when you know exactly which scheme the value belongs to:

```php
$result = $validator->validate('BR', $value, 'cnpj');
$result = $validator->validate('ES', $value, 'nie');
$result = $validator->validate('DE', $value, 'ust_idnr');
$result = $validator->validate('GB', $value, 'vat_registration_number');
```

This is the precise, low-level API.

### Validate by subject

Use `validateFor()` when the jurisdiction has an unambiguous mapping for that subject:

```php
$result = $validator->validateFor(
    countryCode: 'BR',
    value: $value,
    subject: 'company',
); // resolves to cnpj
```

For Portugal:

```php
$validator->validateFor('PT', $value, 'person');  // nif
$validator->validateFor('PT', $value, 'company'); // nipc
```

For the United Kingdom, both `person` and `company` resolve to the general HMRC `utr` scheme when a UTR exists. Purpose-specific references such as VAT registration and employer PAYE remain explicit identifier types.

Spain deliberately does **not** map generic `person` to one identifier type because natural persons may use different schemes such as DNI-based NIF or NIE. In that situation, callers must choose the identifier type explicitly.

### Restrict an optional category

Some identifier schemes encode a meaningful jurisdiction-specific category. Spain's entity NIF is one example: its first letter identifies an entity class.

Without a category restriction, any supported entity category is accepted:

```php
$result = $validator->validateFor(
    countryCode: 'ES',
    value: $value,
    subject: 'company',
);
```

To require a specific official category:

```php
$result = $validator->validateFor(
    countryCode: 'ES',
    value: $value,
    subject: 'company',
    category: 'B',
);
```

When available, the resolved category is exposed in result metadata:

```php
$result->metadata['category']; // 'B'
```

A category is a restriction on an identifier scheme; it is not a separate identifier type. If category validation is requested for a scheme that has no category model, the result is `not_supported` rather than pretending the restriction was checked.

## Default subject configuration

Applications that predominantly work with one kind of subject can configure a default once instead of repeating it on every validation call.

```php
use FiscalIdentifiers\Configuration\IdentifierResolutionConfiguration;

$validator = new FiscalIdentifierValidator(
    $countries,
    new IdentifierResolutionConfiguration(defaultSubject: 'company'),
);
```

Then:

```php
$validator->validate('BR', $cnpj);   // company -> cnpj
$validator->validate('PT', $nipc);   // company -> nipc
$validator->validate('ES', $nif);    // company -> entity_nif
$validator->validate('DE', $widnr);  // company -> widnr
$validator->validate('GB', $utr);    // company -> utr
```

The default subject is only a resolution convenience. It does not stop callers from explicitly requesting another identifier type:

```php
$validator->validate('DE', $vatNumber, 'ust_idnr');
$validator->validate('GB', $vatNumber, 'vat_registration_number');
```

A country may also define its own fallback identifier type when that is unambiguous. Explicit identifier type always remains the clearest choice when context matters.

## Validation results

A validation result keeps support and acceptance separate:

```php
$result->isSupported();
$result->isAccepted();
$result->toArray();
```

The decision can be:

- `accepted` — all implemented local checks required by that definition passed;
- `rejected` — an implemented local rule failed;
- `not_supported` — the requested jurisdiction, identifier type, subject/category restriction or validation layer is not implemented.

Unsupported never means valid or invalid.

Unknown ISO country codes such as `XX` are invalid input and raise an exception. A real ISO jurisdiction that the package does not implement, such as `AF`, returns `not_supported`.

## Validation is a sequence of local questions

| Layer | Question | What a pass does **not** establish |
| --- | --- | --- |
| **Normalization** | Can permitted presentation differences be converted into a canonical string? | That the identifier is valid. |
| **Format / structure** | Does the value have the expected characters, length and component positions? | That a checksum or registry status is valid. |
| **Category** | If applicable, does an encoded jurisdiction-specific category satisfy the requested restriction? | That the entity exists or is active. |
| **Checksum / check digit** | Does the control value agree with the published deterministic calculation? | Assignment, ownership or current registration. |
| **Semantic rules** | Are known jurisdiction-specific ranges or restrictions satisfied? | Authoritative registry status. |

Not every scheme exposes every layer. If an authoritative source documents structure but not a checksum algorithm clearly enough, the package reports the checksum step as `not_supported` rather than inventing one.

Normalization is deliberately conservative: it handles documented presentation differences but should not silently rewrite arbitrary malformed input, infer a country from digits, or guess an identifier type from value length.

## Validation and authoritative verification are different concerns

This package treats these as separate questions:

```text
Validation
  "Does this identifier conform to the known deterministic rules for this jurisdiction and scheme?"

Authoritative verification
  "Does an external authority or registry confirm a particular assignment, registration or status?"
```

A successful checksum does not prove that an identifier exists, is active, belongs to a particular subject or has a VAT registration.

Likewise, VIES is not a generic fiscal-identifier validator. It answers a narrower EU VAT-registration question. Future authority integrations therefore belong to a separate verification layer and will not silently alter the meaning of local validation.

## What is a fiscal identifier?

"Fiscal identifier" is an umbrella term used here for identifiers relevant to tax administration. Names alone do not determine their purpose or validation rules.

| Term | Meaning and relationship |
| --- | --- |
| **Tax number** | Broad informal label for a number used in a tax context. |
| **TIN — Tax Identification Number** | Identifier used by a jurisdiction to identify taxpayers; structures differ by jurisdiction and subject. |
| **VAT number / VAT identification number** | Identifies a VAT registration. It may reuse, extend or differ from another domestic identifier. |
| **GST identifier** | Identifier for goods and services tax in jurisdictions using that system. |
| **Business/company registration number** | Identifies a legal entity in a business register; it is not universally the same as a tax identifier. |
| **Employer, payroll, social security or customs identifier** | Administrative identifiers that may overlap with tax processes but remain distinct schemes. |

One person or organization can therefore have several identifiers at the same time. Always retain the jurisdiction, identifier type and intended use alongside the value.

See the [OECD jurisdiction-specific TIN guidance](https://www.oecd.org/en/networks/global-forum-tax-transparency/resources/aeoi-implementation-portal/tax-identification-numbers.html) and the [European Commission VAT identification overview](https://taxation-customs.ec.europa.eu/taxation/vat/vat-directive/vat-identification-numbers_en) for broader context.

## Development setup

The core targets **PHP 8.3, 8.4 and 8.5**, with Composer 2 and no runtime dependency on a framework. PHP 8.3 is the minimum so the package can serve more than the newest PHP release while using Pest 4 for development.

For now, work from a source checkout; this is not a claim of Packagist availability:

```sh
git clone https://github.com/luiscoutinh/fiscal-identifiers.git
cd fiscal-identifiers
composer install
composer check
```

| Command | Purpose |
| --- | --- |
| `composer test` | Run Pest tests. |
| `composer analyse` | Run PHPStan at maximum level on `src/`. |
| `composer lint` | Check PHP style without modifying files. |
| `composer format` | Apply PHP-CS-Fixer formatting. |
| `composer check` | Strict Composer validation, tests, analysis and style. |
| `composer test:coverage` | Require 100% line coverage and write Clover XML / HTML reports; enable Xdebug or PCOV first. |

CI tests highest dependencies on PHP 8.3–8.5 and lowest supported dependencies on PHP 8.3. The quality job performs Composer validation, PHPStan and PHP-CS-Fixer, then runs the test suite once with PCOV to produce the coverage report.

## Code coverage

[![Code coverage summary](https://raw.githubusercontent.com/luiscoutinh/fiscal-identifiers/coverage-badges/coverage-summary.svg)](https://github.com/luiscoutinh/fiscal-identifiers/actions/workflows/ci.yml)

Coverage measures executable line coverage for all production code under `src/`. The project currently enforces **100% line coverage** through `composer test:coverage`; a coverage regression therefore fails CI immediately rather than merely changing a badge.

The badge and summary card track the latest successful `main` measurement. Metrics are generated from the Clover report produced by the existing CI quality job, so no additional test run or external reporting service is required.

After a successful trusted `main` CI run, a separate presentation workflow renders `coverage.svg`, `coverage-summary.svg` and `coverage.json` and publishes only those assets to the dedicated `coverage-badges` branch. Raw Clover and HTML reports remain available as workflow artifacts for 30 days.

```text
src/                    Core source (FiscalIdentifiers\)
tests/                  Pest test suite
docs/jurisdictions/     Jurisdiction-specific semantics and sources
.github/workflows/      CI checks and coverage presentation
composer.json           Package metadata, autoloading and development commands
phpstan.neon            Static analysis configuration
phpunit.xml             Test suite and coverage source configuration
.php-cs-fixer.php        PHP formatting rules
```

## Contributing, security and license

Read [CONTRIBUTING.md](CONTRIBUTING.md) for setup, fixture conventions, coverage and dependency policy. Report vulnerabilities as described in [SECURITY.md](SECURITY.md).

The package uses the [MIT license](LICENSE). Release notes will be published through [GitHub Releases](https://github.com/luiscoutinh/fiscal-identifiers/releases).
