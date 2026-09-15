<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Registry;

use FiscalIdentifiers\Contracts\ExternalVerificationProvider;

final class ProviderRegistry
{
    /** @var array<string, ExternalVerificationProvider> */
    private array $providers = [];

    public function register(ExternalVerificationProvider $provider): void
    {
        $this->providers[$provider->key()] = $provider;
    }

    public function get(string $key): ?ExternalVerificationProvider
    {
        return $this->providers[$key] ?? null;
    }
}
