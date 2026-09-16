# Fiscal Identifiers

[![CI](https://github.com/luiscoutinh/fiscal-identifiers/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/luiscoutinh/fiscal-identifiers/actions/workflows/ci.yml) [![Coverage](https://raw.githubusercontent.com/luiscoutinh/fiscal-identifiers/coverage-badges/coverage.svg)](https://github.com/luiscoutinh/fiscal-identifiers/actions/workflows/ci.yml) [![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A framework-agnostic PHP core being developed for fiscal identifier normalization
and validation across jurisdictions.

> **Status: early development.** The core API and the first Portuguese VAT-oriented
> local validator are implemented. Portugal currently supports normalization, format
> and NIF checksum validation. Authoritative registry checks such as VIES are
> deliberately outside the validation pipeline. The public API may still change
> before the first stable release.

## What is a fiscal identifier?

"Fiscal identifier" is an umbrella term used here for identifiers relevant to tax
administration. Names alone do not determine their purpose or validation rules.

| Term | Meaning and relationship |
| --- | --- |
| **Tax number** | An informal, broad label for a number used in a tax context. It may mean a general taxpayer identifier or a particular tax registration. |
| **TIN — Tax Identification Number** | An identifier used by a jurisdiction to identify taxpayers. Issuance, structure and use differ for individuals and entities; some jurisdictions use functional equivalents. |
| **VAT number / VAT identification number** | Identifies a registration for value-added tax. It can reuse a domestic TIN, add a prefix, or follow a separate scheme. A TIN does not automatically imply VAT registration. |
| **GST identifier** | An identifier used for goods and services tax in jurisdictions using that system. It is not automatically interchangeable with a TIN or an EU VAT number. |
| **Business or company registration number** | Identifies a legal entity in a business register. It may coincide with a tax identifier in a jurisdiction, but that relationship is not universal. |
| **Employer, payroll, social security or customs identifier** | Identifies another administrative role or registration. These schemes can overlap with tax administration without being interchangeable. |

Always retain the **jurisdiction, identifier type and intended use**, alongside
the identifier string. One entity can have several identifiers and registrations.
Country prefixes are scheme-specific; they should not be treated as a universal
country-detection mechanism. See the [OECD's jurisdiction-specific TIN guidance](https://www.oecd.org/en/networks/global-forum-tax-transparency/resources/aeoi-implementation-portal/tax-identification-numbers.html)
and the [European Commission's VAT identification overview](https://taxation-customs.ec.europa.eu/taxation/vat/vat-directive/vat-identification-numbers_en).

## Validation is a sequence of local questions

| Layer | Question | What a pass does **not** establish |
| --- | --- | --- |
| **Normalization** | Can permitted presentation differences be converted into a canonical string, such as trimming outer spaces or uppercasing an allowed prefix? | That the input was valid. Do not silently remove arbitrary characters, convert letters into digits or lose leading zeroes. |
| **Format / regex** | Does the input use the allowed characters and basic pattern? | That the number satisfies a checksum, has been assigned or is active. |
| **Length / structure** | Are the length, prefix and component positions correct for this scheme? | That the component values are semantically permitted. Regex may cover some of this layer. |
| **Checksum / check digit** | Does the control digit agree with the scheme's calculation? | Existence or ownership. A checksum helps detect some transcription errors; it is not authentication. |
| **Semantic / jurisdiction rules** | Are identifier categories, reserved ranges or other contextual rules permitted under the relevant rules? | Current registration. Rules may depend on identifier type and date. |

Not every scheme has every layer or a checksum. Normalization policies must be
explicit, and an unsupported scheme must not be reported as invalid merely because
it is unsupported. The validation result describes only the deterministic/local
checks that the package actually performed.

## Validation and authoritative verification are different concerns

This package treats these as two separate questions:

```text
Validation
  "Does this identifier conform to the known rules for this jurisdiction/type?"

Authoritative verification
  "Does an external authority or registry confirm a specific registration or status?"
```

An authoritative lookup must not silently change the meaning of local validation.
A registry can answer a narrower question than "does this fiscal identifier exist?".
For example, VIES concerns the relevant intra-EU VAT registration status; a negative
VIES result is not the same statement as "this domestic tax identifier is
structurally invalid".

Future integrations may therefore expose explicit operations such as a VIES
registration check or a jurisdiction-specific authority lookup, where a suitable
official service exists. Those integrations belong to a separate verification
layer with their own statuses and semantics; they are not validation steps and do
not participate in the current `FiscalIdentifierValidator` decision.

## Portugal: NIF, NIPC and intra-EU VAT

**NIF** means *Número de Identificação Fiscal*. **NIPC** means *Número de
Identificação de Pessoa Coletiva* and is used for legal entities; it also serves
as their fiscal identifier in the relevant Portuguese context. These are related
administrative concepts, not two interchangeable labels for every person.
Portuguese TINs have nine digits, including a final check digit.
See the [OECD Portugal TIN sheet](https://www.oecd.org/content/dam/oecd/en/topics/policy-issue-focus/aeoi/portugal-tin.pdf)
and the [Portuguese guidance on NIPC](https://www.dgo.gov.pt/instrucoes/Instrucoes/ca1410.pdf).

For a Portuguese VAT-form identifier, the `PT` prefix can accompany the domestic
nine-digit number. The current local validation sequence is:

```text
Input + explicit context: Portugal, VAT-oriented local validation
    -> apply the normalization policy
    -> check exactly nine ASCII digits after normalization
    -> verify the domestic check digit
```

For example, `/\A[0-9]{9}\z/` describes only the basic domestic shape;
`/\APT[0-9]{9}\z/` describes only the prefixed shape. Neither regex validates
the checksum or registration. `PT123456789` is an illustrative string that fits
the latter pattern; it is **not** presented as a valid or assigned identifier.
The local checksum calculation compares a derived control digit with the final
digit. Passing it establishes mathematical plausibility, not assignment.

VIES, when supported in the future, will be documented and exposed as a separate
registry-verification capability rather than as a step in this local validation
sequence. See [Your Europe's explanation of VIES results](https://europa.eu/youreurope/business/finance-and-tax/vat/check-vat-number-vies/index_en.htm).

## Current API — early development

The package exposes the local validation core, but the API is not considered
stable yet. Applications explicitly register country definitions and inspect the
validation decision and individual local steps.

```php
use FiscalIdentifiers\Countries\PT\Portugal;
use FiscalIdentifiers\FiscalIdentifierValidator;
use FiscalIdentifiers\Registry\CountryRegistry;

$countries = new CountryRegistry();
$countries->register(Portugal::definition());

$validator = new FiscalIdentifierValidator($countries);

$result = $validator->validate('PT', 'PT 123 456 789');

$result->isAccepted(); // local validation decision
$result->toArray();    // normalization / format / checksum details
```

No network request or registry lookup is performed by `validate()`. If an
application later needs an authoritative status, it should request that explicitly
through a dedicated verification integration rather than infer it from the local
validation result.

## Development setup

The core targets **PHP 8.3, 8.4 and 8.5**, with Composer 2 and no runtime dependency
on a framework. PHP 8.3 is the minimum so the package can serve more than the newest
PHP release while using Pest 4 for development. Future PHP versions will be added
after verification; see [PHP's support calendar](https://www.php.net/supported-versions.php)
and [Pest's requirements](https://pestphp.com/docs/installation).

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

CI tests highest dependencies on PHP 8.3–8.5 and lowest supported dependencies on
PHP 8.3. The quality job performs Composer validation, PHPStan and PHP-CS-Fixer,
then runs the test suite once with PCOV to produce the coverage report. This avoids
running the same tests twice inside the quality job while preserving the compatibility
matrix.

## Code coverage

[![Code coverage summary](https://raw.githubusercontent.com/luiscoutinh/fiscal-identifiers/coverage-badges/coverage-summary.svg)](https://github.com/luiscoutinh/fiscal-identifiers/actions/workflows/ci.yml)

Coverage measures executable line coverage for all production code under `src/`.
The project currently enforces **100% line coverage** through `composer test:coverage`;
a coverage regression therefore fails CI immediately rather than merely changing a
badge.

The badge and summary card track the latest successful `main` measurement. Line
coverage is the primary gate, while method and class coverage are shown as supporting
diagnostics. Metrics are generated from the Clover report produced by the existing
CI quality job, so no additional test run and no external reporting service such as
Codecov or Coveralls is required.

After a successful trusted `main` CI run, a separate presentation workflow renders
`coverage.svg`, `coverage-summary.svg` and `coverage.json` and publishes only those
presentation assets to the dedicated `coverage-badges` branch. Because the package
repository is public, the README can render those raw branch assets directly. This
keeps generated coverage commits out of `main`, avoids CI loops, and remains
independent of future `main` branch-protection rules. Raw Clover and HTML reports
remain available as workflow artifacts for 30 days.

```text
src/                    Core source (FiscalIdentifiers\)
tests/                  Pest test suite
.github/workflows/      CI checks and coverage presentation
composer.json           Package metadata, autoloading and development commands
phpstan.neon            Static analysis configuration
phpunit.xml             Test suite and coverage source configuration
.php-cs-fixer.php        PHP formatting rules
```

## Contributing, security and license

Read [CONTRIBUTING.md](CONTRIBUTING.md) for setup, fixture conventions, coverage and
dependency policy. Report vulnerabilities as described in [SECURITY.md](SECURITY.md).
The package uses the [MIT license](LICENSE). Release notes will be published through
[GitHub Releases](https://github.com/luiscoutinh/fiscal-identifiers/releases).
