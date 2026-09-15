# Architecture

## Core principles

### Worldwide scope

The library models fiscal identifiers by ISO 3166-1 alpha-2 country and identifier type. Every country can be represented even when only partial validation capability is available.

### Validation capability and acceptance policy are separate concerns

A validation step reports what happened. The acceptance decision describes whether the current policy accepts the identifier. `not_supported`, `disabled`, and `unavailable` are deliberately different from `passed`.

### Validation pipeline

1. **Normalization** — canonicalize input (for example uppercase, remove separators, strip a country prefix when appropriate).
2. **Format validation** — validate deterministic structural rules.
3. **Checksum/local validation** — execute country-specific deterministic algorithms when available.
4. **External verification** — query an authoritative provider when configured, supported, and enabled.
5. **Acceptance decision** — derive the consumer-facing decision without erasing the technical result of each step.

### External providers

Country definitions declare the provider key they use, for example `PT -> vies`. A provider registry resolves that key to an implementation.

External verification can be disabled globally for a provider or overridden per country. Country overrides take effect independently, so disabling external verification for one country does not affect other countries using the same provider.

### Failure semantics

- `passed`: the step ran and succeeded.
- `failed`: the step ran and determined the identifier is invalid.
- `not_supported`: the library has no implementation for this step.
- `disabled`: the capability exists but configuration intentionally skipped it.
- `unavailable`: the capability was expected to run but could not, for example because a provider was unavailable.

Unsupported validation is not successful validation. An identifier can nevertheless be **accepted** by policy when a non-required capability is unsupported, disabled, or temporarily unavailable.

### Output is presentation

The internal result remains strongly typed. Consumers can map the final decision to any representation (`true/false`, `1/0`, `valid/invalid`, etc.) without changing validation semantics.

### Authoritative sources

Country-specific fiscal rules should be based on official specifications whenever available. Third-party libraries and repositories may be monitored as implementation references, but must not silently become the source of truth.
