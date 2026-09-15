# Fiscal Identifiers

[![CI](https://github.com/luiscoutinh/fiscal-identifiers/actions/workflows/ci.yml/badge.svg)](https://github.com/luiscoutinh/fiscal-identifiers/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A framework-agnostic PHP core being developed for fiscal identifier normalization
and validation across jurisdictions.

> **Status: infrastructure only.** No country validators, normalization API,
> checksum implementations or external verification providers exist yet. There is
> no stable public API or production-ready release. Portugal below is a teaching
> example, not a supported jurisdiction.

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

## Validation is a sequence of different questions

| Layer | Question | What a pass does **not** establish |
| --- | --- | --- |
| **Normalization** | Can permitted presentation differences be converted into a canonical string, such as trimming outer spaces or uppercasing an allowed prefix? | That the input was valid. Do not silently remove arbitrary characters, convert letters into digits or lose leading zeroes. |
| **Format / regex** | Does the input use the allowed characters and basic pattern? | That the number satisfies a checksum, has been assigned or is active. |
| **Length / structure** | Are the length, prefix and component positions correct for this scheme? | That the component values are semantically permitted. Regex may cover some of this layer. |
| **Checksum / check digit** | Does the control digit agree with the scheme's calculation? | Existence or ownership. A checksum helps detect some transcription errors; it is not authentication. |
| **Semantic / jurisdiction rules** | Are identifier categories, reserved ranges or other contextual rules permitted under the relevant rules? | Current registration. Rules may depend on identifier type and date. |
| **External verification** | Does an authoritative registry confirm the relevant registration or status at this time? | Universal validity, identity, ownership or validity for a different purpose. Availability and response semantics matter. |

Not every scheme has every layer or a checksum. Normalization policies must be
explicit, and an unsupported scheme must not be reported as invalid merely because
it is unsupported. External results should distinguish **not checked**, **confirmed**,
**not confirmed** and **unavailable/error**, rather than forcing every outcome into
a single boolean. These are design principles, not an implemented result model.

## Portugal: NIF, NIPC and intra-EU VAT

**NIF** means *Número de Identificação Fiscal*. **NIPC** means *Número de
Identificação de Pessoa Coletiva* and is used for legal entities; it also serves
as their fiscal identifier in the relevant Portuguese context. These are related
administrative concepts, not two interchangeable labels for every person.
Portuguese TINs have nine digits, including a final check digit.
See the [OECD Portugal TIN sheet](https://www.oecd.org/content/dam/oecd/en/topics/policy-issue-focus/aeoi/portugal-tin.pdf)
and the [Portuguese guidance on NIPC](https://www.dgo.gov.pt/instrucoes/Instrucoes/ca1410.pdf).

For a Portuguese VAT identifier, the `PT` prefix accompanies the domestic nine-digit
number. A useful conceptual sequence is:

```text
Input + explicit context: Portugal, VAT, intra-EU transactions
    -> apply the agreed normalization policy
    -> check PT prefix and exactly nine ASCII digits
    -> check domestic structure and applicable category rules
    -> verify the domestic check digit
    -> consult VIES if intra-EU VAT registration must be checked
```

For example, `/\A[0-9]{9}\z/` describes only the basic domestic shape;
`/\APT[0-9]{9}\z/` describes only the prefixed shape. Neither regex validates
the checksum or registration. `PT123456789` is an illustrative string that fits
the latter pattern; it is **not** presented as a valid or assigned identifier.
The local checksum calculation compares a derived control digit with the final
digit. Passing it establishes mathematical plausibility, not assignment.

**VIES complements local validation; it does not replace it.** VIES checks the
relevant intra-EU VAT registration information supplied by national systems.
Not every NIF belongs to a VAT-registered taxpayer, and not every domestic VAT
registration is activated for intra-EU transactions. A negative VIES response can
reflect missing activation or incomplete registration; a service failure means
verification could not be completed. Keep the purpose and time of verification
with any result. See [Your Europe's explanation of VIES results](https://europa.eu/youreurope/business/finance-and-tax/vat/check-vat-number-vies/index_en.htm).

## Conceptual usage — not a callable package API

These examples describe application decisions only. No classes or methods for this
flow are implemented, and no live registry is contacted by this package.

```text
Example A: domestic Portuguese identifier on a form
  context = { jurisdiction: PT, scheme: domestic tax identifier }
  input = user-supplied string
  normalize under an explicit policy
  evaluate format, structure, checksum and applicable local rules
  output = local plausibility result; external status = not checked

Example B: intra-EU VAT registration check
  context = { jurisdiction: PT, scheme: VAT, purpose: intra-EU trade }
  perform the applicable local checks
  if locally plausible, request external verification via VIES
  retain the external response and verification time
  on timeout, report verification unavailable; do not claim invalid registration
```

Neither example establishes ownership or constitutes a complete tax-compliance
decision. Application acceptance policies and external service integration remain
separate concerns from deterministic local checks.

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

The temporary `FiscalIdentifiers\Internal\Package` stub is not public API. Its
single smoke test exercises PSR-4 loading and the test/coverage pipeline. **100%
coverage at this stage measures that stub only, not functional validation support.**
CI tests highest dependencies on PHP 8.3–8.5 and lowest dependencies on PHP 8.3;
it also runs quality checks and uploads coverage reports for 14 days.

```text
src/                    Core source (FiscalIdentifiers\)
tests/                  Pest test suite
.github/workflows/      CI checks and coverage reports
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
