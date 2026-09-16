# Jurisdiction documentation

Each jurisdiction document describes the fiscal identifier schemes currently implemented by the package, the subject mappings used by the convenience API, known limitations, and the authoritative sources reviewed for the implementation.

| Country | Documentation | Current identifier types |
| --- | --- | --- |
| Portugal (`PT`) | [PT.md](PT.md) | `nif`, `nipc` |
| Brazil (`BR`) | [BR.md](BR.md) | `cpf`, `cnpj` |
| Spain (`ES`) | [ES.md](ES.md) | `dni_nif`, `nie`, `entity_nif` |
| Germany (`DE`) | [DE.md](DE.md) | `idnr`, `widnr`, `ust_idnr`, `steuernummer` |
| United Kingdom (`GB`) | [GB.md](GB.md) | `utr`, `vat_registration_number`, `employer_paye_reference` |

## Documentation contract

Jurisdiction documentation should make the following distinctions explicit:

- the official meaning and intended subject of each identifier scheme;
- normalization rules accepted by the package;
- structural and checksum rules actually implemented;
- subject mappings such as `person -> cpf` or `company -> cnpj`;
- optional category semantics when an identifier encodes a jurisdiction-specific class;
- checks that remain `not_supported` because no sufficiently authoritative deterministic rule has been implemented;
- the difference between local validation and authoritative registry/status verification.

A successful local validation result must never be documented as proof that an identifier was assigned, is currently active, belongs to a particular subject, or has a particular VAT/registry status.

When adding or changing a jurisdiction, prefer current primary sources from the relevant tax authority, government, standards body or official legislation, and record those sources in the jurisdiction document.
