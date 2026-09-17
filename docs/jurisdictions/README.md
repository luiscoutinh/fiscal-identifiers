# Jurisdiction documentation

Each jurisdiction document describes the fiscal identifier schemes currently implemented by the package, the subject mappings used by the convenience API, known limitations, and the authoritative sources reviewed for the implementation.

| Country | Documentation | Current identifier types |
| --- | --- | --- |
| Portugal (`PT`) | [PT.md](PT.md) | `nif`, `nipc` |
| Brazil (`BR`) | [BR.md](BR.md) | `cpf`, `cnpj` |
| Spain (`ES`) | [ES.md](ES.md) | `dni_nif`, `nie`, `entity_nif` |
| Germany (`DE`) | [DE.md](DE.md) | `idnr`, `widnr`, `ust_idnr`, `steuernummer` |
| United Kingdom (`GB`) | [GB.md](GB.md) | `utr`, `vat_registration_number` |

## Scope

The package currently focuses on identifiers whose primary purpose is tax identification or tax registration. The intended core scope includes taxpayer identifiers, VAT/GST identifiers and comparable jurisdiction-specific fiscal identifiers.

Identifiers whose primary purpose is corporate registration, payroll/employer administration, social security, customs or another administrative domain are deliberately deferred even when they interact with tax processes. They should only be added later through an explicit scope decision rather than because they are adjacent to fiscal administration.

## Documentation contract

Jurisdiction documentation should make the following distinctions explicit:

- the official meaning and intended subject of each identifier scheme;
- why the identifier belongs inside the package's fiscal scope;
- normalization rules accepted by the package;
- structural and checksum rules actually implemented;
- subject mappings such as `person -> cpf` or `company -> cnpj`;
- optional category semantics when an identifier encodes a jurisdiction-specific class;
- checks that remain `not_supported` because no sufficiently authoritative deterministic rule has been implemented;
- the difference between local validation and authoritative registry/status verification;
- adjacent administrative identifiers that were intentionally excluded when that boundary is relevant.

A successful local validation result must never be documented as proof that an identifier was assigned, is currently active, belongs to a particular subject, or has a particular VAT/registry status.

When adding or changing a jurisdiction, prefer current primary sources from the relevant tax authority, government, standards body or official legislation, and record those sources in the jurisdiction document.
