# United Kingdom (`GB`)

The package models several UK fiscal identifiers separately because a company can hold multiple references for different tax purposes.

Current package types:

- `utr` — Unique Taxpayer Reference, a 10-digit HMRC tax reference used for Self Assessment and companies registered for Corporation Tax;
- `vat_registration_number` — UK VAT registration number, normally nine digits and often presented with an optional `GB` prefix;
- `employer_paye_reference` — HMRC employer PAYE reference, formatted as a three-digit office number, `/`, and 1 to 10 alphanumeric characters.

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

VAT and PAYE remain purpose-specific identifiers and are requested explicitly:

```php
$validator->validate('GB', $value, 'vat_registration_number');
$validator->validate('GB', $value, 'employer_paye_reference');
```

## UTR

GOV.UK states that a Unique Taxpayer Reference is a 10-digit number. A person may receive one through Self Assessment, while a limited company receives a Corporation Tax UTR.

The current local validator:

1. trims outer whitespace;
2. removes presentation whitespace;
3. requires exactly 10 decimal digits.

No checksum is currently implemented because the package does not have a sufficiently authoritative public specification of a deterministic UTR check-digit algorithm. The checksum step is therefore `not_supported`.

## VAT registration number

HMRC guidance describes the standard UK VAT registration number as nine digits, optionally presented with `GB` at the start. HMRC service-design guidance explicitly instructs services to remove spaces and the `GB` prefix before validation.

The package therefore normalizes a value such as:

```text
GB 123 456 789
```

to the domestic canonical representation:

```text
123456789
```

and requires exactly nine decimal digits.

HMRC material confirms that the final two digits of standard UK VAT registration numbers are calculated from the first seven using a standard formula, but the detailed formula is not publicly disclosed in the cited HMRC material. The package therefore deliberately does not implement a guessed checksum. Registry/status verification also remains a separate concern.

Special government/public-body presentation series are not implemented as separate local schemes in this first version.

## Employer PAYE reference

HMRC's design guidance specifies an employer PAYE reference as:

- three numbers;
- `/`;
- between 1 and 10 letters or numbers.

The package uppercases letters, removes presentation whitespace and validates that structure. It does not claim that a structurally valid reference has been assigned to an employer or is currently active.

## Companies House company number

A Companies House company registration number is intentionally **not** implemented as a fiscal identifier. It identifies a company in the corporate register rather than a tax registration. Applications that need both should retain the company-registration identifier separately from HMRC fiscal identifiers.

## Sources

Authoritative sources reviewed on 2026-09-16:

- GOV.UK — Find your UTR number: https://www.gov.uk/find-utr-number
- GOV.UK — Add Corporation Tax services to your business tax account: https://www.gov.uk/limited-company-formation/add-corporation-tax-services-to-business-tax-account
- HMRC Design Patterns — VAT registration number: https://design.tax.service.gov.uk/hmrc-design-patterns/vat-registration-number/
- HMRC VAT Registration Manual — VATREG03700: https://www.gov.uk/hmrc-internal-manuals/vat-registration-manual/vatreg03700
- HMRC Enquiry Manual — EM3058: https://www.gov.uk/hmrc-internal-manuals/enquiry-manual/em3058
- HMRC Design Patterns — Employer PAYE reference: https://design.tax.service.gov.uk/hmrc-design-patterns/employer-paye-reference/
- Companies House — company details on documents: https://www.gov.uk/government/publications/company-details-on-documents/company-details-on-documents

As with all jurisdictions in this package, successful local validation does not prove assignment, ownership, current tax status, VAT registration or Companies House status.
