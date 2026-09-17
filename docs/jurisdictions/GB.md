# United Kingdom (`GB`)

The package models UK fiscal identifiers separately because one taxpayer or company can hold multiple references for different tax purposes.

Current package types:

- `utr` — Unique Taxpayer Reference, a 10-digit HMRC tax reference used for Self Assessment and companies registered for Corporation Tax;
- `vat_registration_number` — UK VAT registration number, normally nine digits and often presented with an optional `GB` prefix.

## Scope

This package currently focuses on identifiers whose primary purpose is fiscal or tax-registration related.

That means UK identifiers such as employer PAYE references, National Insurance numbers and Companies House company registration numbers are deliberately outside the current package scope. They may be relevant to tax, payroll or business administration, but they are not treated here as general fiscal identifiers.

## Subject resolution

Both natural persons and companies can have a UTR, so the convenience mappings are:

```text
person  -> utr
company -> utr
```

This does **not** mean that every person or company necessarily has a UTR. It only identifies the package's general tax-reference scheme for those subjects when such a reference exists.

A company-oriented application can configure:

```php
new IdentifierResolutionConfiguration(defaultSubject: 'company')
```

so `validate('GB', $value)` resolves to `utr`.

VAT registration remains a purpose-specific fiscal identifier and is requested explicitly:

```php
$validator->validate('GB', $value, 'vat_registration_number');
```

## UTR

GOV.UK states that a Unique Taxpayer Reference is a 10-digit number. A person may receive one through Self Assessment, while a limited company receives a Corporation Tax UTR.

The current local validator:

1. trims outer whitespace;
2. removes presentation whitespace;
3. requires exactly 10 decimal digits.

No checksum is currently implemented because the package does not have a sufficiently authoritative public specification of a deterministic UTR check-digit algorithm. The checksum step is therefore `not_supported`.

## VAT registration number

HMRC guidance states that registered traders receive a nine-digit VAT registration number. GOV.UK also describes the VAT registration number as nine digits.

The package accepts the common optional `GB` presentation prefix and spaces, normalizing a value such as:

```text
GB 123 456 789
```

to the domestic canonical representation:

```text
123456789
```

and requires exactly nine decimal digits.

The package deliberately does not implement an undocumented or inferred checksum algorithm. Registry/status verification also remains a separate concern.

Special government/public-body presentation series are not implemented as separate local schemes in this first version.

## Explicit exclusions

The following UK identifiers are intentionally not implemented in the current fiscal-only scope:

- employer PAYE references — payroll/employer administration;
- National Insurance numbers — social-security/personal administration;
- Companies House company numbers — corporate registration.

Applications that need those identifiers should retain them separately from HMRC fiscal identifiers.

## Sources

Authoritative sources reviewed on 2026-09-17:

- GOV.UK — Find your UTR number: https://www.gov.uk/find-utr-number
- HMRC VAT Registration Manual — VATREG03700: https://www.gov.uk/hmrc-internal-manuals/vat-registration-manual/vatreg03700
- GOV.UK — Register for VAT: https://www.gov.uk/register-for-vat/how-register-for-vat

As with all jurisdictions in this package, successful local validation does not prove assignment, ownership, current tax status or VAT registration.
