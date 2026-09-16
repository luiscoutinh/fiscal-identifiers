# Spain (`ES`)

Spain is intentionally modeled with multiple identifier schemes because `NIF` is an umbrella fiscal concept rather than one universal syntax.

Current package types:

- `dni_nif` — the general NIF form for Spanish natural persons, based on DNI: eight digits plus one control letter;
- `nie` — the general NIF form for foreign natural persons when they have a NIE: `X`, `Y` or `Z`, seven digits and one control letter;
- `entity_nif` — the NIF form for legal persons and entities: one entity-class letter, seven digits and one control character.

## Subject resolution

Spain demonstrates why subject and identifier type must remain separate concepts.

A company/entity can be resolved unambiguously to `entity_nif`:

```text
company -> entity_nif
```

A generic `person` subject is deliberately **not** mapped to one identifier type. A natural person can have a DNI-based NIF, a NIE, or one of the tax-administration-assigned K/L/M NIF forms described by AEAT. Callers therefore need explicit identifier context for natural persons.

A company-oriented application can still configure:

```php
new IdentifierResolutionConfiguration(defaultSubject: 'company')
```

so `validate('ES', $value)` resolves to `entity_nif`.

## Optional entity category restriction

The initial letter of a Spanish entity NIF identifies the official legal-form/entity category. The package exposes that code as result metadata and lets callers optionally require a specific category while keeping `entity_nif` as the identifier scheme.

Any supported entity category:

```php
$validator->validateFor(
    countryCode: 'ES',
    value: $value,
    subject: 'company',
);
```

A specific official category, for example `B`:

```php
$validator->validateFor(
    countryCode: 'ES',
    value: $value,
    subject: 'company',
    category: 'B',
);
```

When no category is supplied, any structurally supported entity category is acceptable. When a category is supplied, the identifier must resolve to that category. The resolved official code is also exposed as:

```php
$result->metadata['category'];
```

Category is deliberately modeled as a restriction on an identifier, not as another identifier type. This keeps legal form/entity class separate from fiscal identifier scheme and leaves the mechanism reusable for jurisdictions that encode comparable classifications differently.

## DNI-based NIF

For Spanish natural persons, AEAT states that the general NIF is the DNI number followed by an uppercase verification character. The format has nine characters: eight digits, including possible leading zeroes, plus the control letter.

The local validator normalizes case and common presentation separators, checks that format, and validates the modulo-23 control letter.

Passing these checks establishes only local mathematical consistency. It does not prove that the DNI/NIF was issued, remains valid, or belongs to a particular person.

## NIE

For foreign natural persons, AEAT states that the general NIF is normally the NIE. The current NIE composition has nine characters:

- initial `X`, then `Y`, then `Z` series;
- seven decimal digits;
- one alphabetic verification character.

The validator normalizes case/presentation and applies the same modulo-23 control-letter mechanism after converting the initial `X`, `Y` or `Z` series to its numeric equivalent.

## Entity NIF

AEAT and Order EHA/451/2008 define the NIF for legal persons and entities without legal personality as nine characters:

- one initial letter identifying legal form or entity class;
- seven random digits;
- one control character.

The currently recognized initial entity keys include `A`, `B`, `C`, `D`, `E`, `F`, `G`, `H`, `J`, `N`, `P`, `Q`, `R`, `S`, `U`, `V` and `W`.

This first implementation validates that official structural composition but **does not yet validate the final entity control character**. The result therefore reports the checksum step as `not_supported` rather than pretending that the control character was verified. A later change can add that algorithm once its authoritative basis is documented to the same standard as the format rules.

The old term `CIF` is not used as the canonical package type. Current AEAT material refers to these identifiers as NIF for legal persons and entities.

## Not yet supported

AEAT also documents tax-administration-assigned natural-person NIF forms beginning with `K`, `L` and `M` for specific cases. They are intentionally not folded into `dni_nif` or `nie`; they should be added as explicit schemes if/when implemented.

VAT-registration status (`NIF-IVA`) is also a separate authoritative/registration concern and is not inferred from local NIF validation.

## Sources

Authoritative sources reviewed on 2026-09-16:

- AEAT — NIF composition for natural persons: https://sede.agenciatributaria.gob.es/Sede/ayuda/manuales-videos-folletos/manuales-practicos/guia-practica-cumplimentacion-modelo-censal-036/anexos/anexo-01-solicitud-nif-documentacion-aportar/informacion-sobre-numero-identificacion-fiscal/composicion-nif/personas-fisicas.html
- AEAT — NIF composition for legal persons and entities: https://sede.agenciatributaria.gob.es/Sede/ayuda/manuales-videos-folletos/manuales-practicos/guia-practica-cumplimentacion-modelo-censal-036/anexos/anexo-01-solicitud-nif-documentacion-aportar/informacion-sobre-numero-identificacion-fiscal/composicion-nif/personas-juridicas-entidades.html
- AEAT — applying for NIF / NIF-IVA: https://sede.agenciatributaria.gob.es/Sede/censos-nif-domicilio-fiscal/solicitar-nif.html
- BOE — consolidated Order EHA/451/2008 on the composition of NIF for legal persons and entities: https://www.boe.es/buscar/act.php?id=BOE-A-2008-3580

The package performs no AEAT registry/existence check in `validate()`.
