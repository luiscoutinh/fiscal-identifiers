# Fiscal Identifiers

Worldwide fiscal identifier validation for PHP, with normalization, country-specific rules, checksums, external verification providers, and configurable acceptance and output policies.

> **Status:** early development. The public API and country coverage are not yet stable.

## Why

Fiscal identifiers are jurisdiction-specific. A useful validator needs to distinguish deterministic local validation from authoritative online verification, while still allowing applications to decide which checks are required.

This package is designed for worldwide coverage rather than VAT-only or EU-only validation.

## Validation model

For each country and identifier type the library can provide:

1. **Normalization** — remove separators, normalize case and country prefixes.
2. **Format validation** — structural validation such as length and character rules.
3. **Checksum/local validation** — jurisdiction-specific deterministic algorithms when they exist.
4. **External verification** — authoritative verification through a country-defined provider such as VIES.

Each step reports its own status: `passed`, `failed`, `not_supported`, `disabled`, or `unavailable`.

The final acceptance decision is deliberately separate from those technical statuses. This means an application may accept an identifier when external verification is disabled or temporarily unavailable without pretending that authoritative verification succeeded.

## Country definitions and providers

A country definition declares which external provider applies:

```php
'PT' => [
    'vat' => [
        'format' => '/^\\d{9}$/',
        'checksum' => PortugalNifChecksumValidator::class,
        'external_provider' => 'vies',
    ],
];
```

Provider implementations are resolved separately through the provider registry. This allows several countries to share one provider while configuration can still disable external verification for a single country.

## Configuration model

Everything implemented is enabled by default. Consumers can disable an external provider or override external verification per country:

```php
new ValidationConfiguration(
    providers: [
        'vies' => true,
    ],
    countryExternalValidation: [
        'AF' => false,
    ],
);
```

Provider and country switches are separate concerns:

- disabling `vies` disables that provider wherever it is used;
- disabling external verification for `AF` affects only Afghanistan.

## Output mapping

The internal result stays strongly typed, but applications can map acceptance to the values they need:

```php
$result->toConfiguredValue(true, false);
$result->toConfiguredValue(1, 0);
$result->toConfiguredValue('valid', 'invalid');
```

Detailed results remain available through `toArray()` and the typed result objects.

## Current proof of concept

Portugal is the first country definition and currently demonstrates normalization, format validation and the Portuguese NIF checksum. It declares `vies` as its external provider; the concrete VIES integration is intentionally left behind the provider contract and registry.

## Development

```bash
composer install
composer test
composer analyse
```

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for the design principles and validation semantics.
