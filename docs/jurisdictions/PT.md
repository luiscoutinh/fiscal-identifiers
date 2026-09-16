# Portugal (`PT`)

Portugal exposes two semantic identifier types in the package:

- `nif` — Número de Identificação Fiscal
- `nipc` — Número de Identificação de Pessoa Coletiva

The subject mappings are:

```text
person  -> nif
company -> nipc
```

The country-level fallback remains `nif`. Applications whose dominant context is companies can configure `company` as the global default subject, in which case `validate('PT', $value)` resolves to `nipc`.

## NIF

`nif` is the general Portuguese fiscal identifier type used by the package for person-oriented fiscal identification.

The local validator:

1. trims presentation whitespace;
2. removes permitted spaces, dots and hyphens;
3. removes an optional `PT` presentation prefix;
4. requires exactly nine decimal digits;
5. validates the final check digit using the existing Portuguese modulo-11 calculation.

A successful result establishes only local structural/checksum consistency. It does not prove assignment, current tax status, ownership or VAT registration.

## NIPC

`nipc` represents the Número de Identificação de Pessoa Coletiva used to identify companies and other collective entities. Portuguese public registration services describe the NIPC as the identifier assigned during entity creation and used to identify the entity legally and fiscally.

The current local implementation deliberately reuses the same nine-digit normalization, format and checksum mechanics as the Portuguese NIF validator. The distinction between `nif` and `nipc` is therefore semantic at this layer: the caller states whether the subject is a person or a company/entity, and the package resolves the appropriate identifier type.

The package does not currently attempt to infer entity kind from digit prefixes or to claim that a checksum-consistent value has actually been assigned as a NIPC. Those checks would require authoritative rules or registry verification and should not be inferred from local validation alone.

## Resolution examples

```php
$validator->validateFor('PT', $value, 'person');  // -> nif
$validator->validateFor('PT', $value, 'company'); // -> nipc

$validator->validate('PT', $value, 'nif');
$validator->validate('PT', $value, 'nipc');
```

With a company-oriented default subject:

```php
new IdentifierResolutionConfiguration(defaultSubject: 'company');

$validator->validate('PT', $value); // -> nipc
```

Without a configured default subject, Portugal retains `nif` as its country-level fallback for backwards-compatible generic use.

## Sources

Authoritative sources reviewed on 2026-09-16:

- Justiça / Registos — Cartão da Empresa ou Pessoa Coletiva: https://registo.justica.gov.pt/Empresas/Pedir-Cartao-de-Empresa-ou-Pessoa-Coletiva/iniciar
- gov.pt — Pedir o Cartão da Empresa/Pessoa Coletiva: https://www.gov.pt/servicos/pedir-o-cartao-da-empresa-pessoa-coletiva
- Portal das Finanças — Portaria n.º 4/2009, conteúdo do Cartão de Empresa/Pessoa Coletiva: https://info.portaldasfinancas.gov.pt/pt/informacao_fiscal/legislacao/diplomas_legislativos/Documents/Portaria_4-2009.pdf

The package does not perform an IRN or Autoridade Tributária registry lookup in `validate()`; authoritative existence/status checks remain a separate concern.
