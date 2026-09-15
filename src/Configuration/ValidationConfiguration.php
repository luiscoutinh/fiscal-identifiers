<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Configuration;

final readonly class ValidationConfiguration
{
    /**
     * @param array<string, bool> $providers
     * @param array<string, bool> $countryExternalValidation
     */
    public function __construct(
        private array $providers = [],
        private array $countryExternalValidation = [],
    ) {}

    public function isProviderEnabled(string $provider): bool
    {
        return $this->providers[$provider] ?? true;
    }

    public function isExternalValidationEnabledFor(string $countryCode): bool
    {
        return $this->countryExternalValidation[strtoupper($countryCode)] ?? true;
    }
}
