# France (`FR`)

France uses different fiscal identifiers for natural persons, legal/business entities and VAT registrations. The package models those purposes separately rather than inferring one from another.

Current package types:

- `numero_fiscal` — the 13-digit personal French tax identifier, also described by the OECD as the French individual TIN / `numéro SPI`;
- `siren` — the 9-digit SIREN identifier used for French entities and recognized by the OECD as the French entity TIN;
- `vat_number` — the French intra-Community VAT identification number, represented domestically as two key digits followed by the 9-digit SIREN and commonly presented with an `FR` prefix.

## Scope and SIREN

SIREN is a multi-purpose legal-unit identifier issued by INSEE, not a tax-only registry number. It is nevertheless included here because the OECD's French TIN documentation explicitly identifies SIREN as the tax identification number for entities. This is a jurisdiction-specific TIN role and does not broaden the package scope to arbitrary company-register identifiers in other countries.

Subject resolution is therefore:

```text
person  -> numero_fiscal
company -> siren
```

VAT registration remains explicit:

```php
$validator->validate('FR', $value, 'vat_number');
```

## Personal `numero_fiscal`

French tax administration guidance describes the personal fiscal number as a unique 13-digit identifier used for tax procedures. The package removes presentation whitespace and requires exactly 13 decimal digits.

No deterministic checksum is currently implemented because the reviewed authoritative sources establish the structure and tax role but do not publish a checksum algorithm suitable for local validation. The checksum step therefore remains `not_supported`.

## SIREN

INSEE defines SIREN as a 9-digit identifier for a legal unit. INSEE also documents that SIREN uses the Luhn parity-control mechanism.

The package therefore:

1. removes presentation whitespace;
2. requires exactly 9 decimal digits;
3. applies the documented Luhn control.

Passing the Luhn check establishes mathematical consistency only. It does not prove that INSEE assigned the SIREN or that the entity exists or is active.

## VAT number

French tax authority guidance describes the French intra-Community VAT number as:

```text
FR + 2 key digits + 9-digit SIREN
```

The package accepts the optional `FR` presentation prefix, removes presentation whitespace and stores the domestic 11-digit portion for validation.

The reviewed primary tax sources document the two-digit computer key and embedded SIREN but do not provide a sufficiently explicit current public calculation rule for that VAT key. The package therefore validates the documented structure only and leaves the checksum step as `not_supported` rather than implementing a formula from secondary sources.

Authoritative VAT-registry verification, including VIES, remains a separate future capability and is not part of local validation.

## Sources

Authoritative sources reviewed on 2026-09-17:

- OECD — France, Information on Tax Identification Numbers: https://www.oecd.org/tax/automatic-exchange/crs-implementation-and-assistance/tax-identification-numbers/france-tin.pdf
- impots.gouv.fr — personal tax-space guidance describing the 13-digit `numéro fiscal`: https://www.impots.gouv.fr/particulier/questions/comment-creer-mon-espace-finances-publiques
- INSEE — definition of SIREN: https://www.insee.fr/fr/metadonnees/definition/c2047
- INSEE — SIREN/NIC/SIRET Luhn control: https://xml.insee.fr/schema/siret.html
- impots.gouv.fr — identification numbers, including French intra-Community VAT structure: https://www.impots.gouv.fr/professionnel/les-numeros-didentification
- BOFiP — VAT individual identification number structure: https://bofip.impots.gouv.fr/bofip/1149-PGP.html/

As with all jurisdictions in this package, local validation does not establish assignment, ownership, legal existence, tax residency, active VAT registration or current registry status.
