<?php

declare(strict_types=1);

namespace LuisCoutinho\FiscalIdentifiers\Registry;

use LuisCoutinho\FiscalIdentifiers\Definitions\CountryDefinition;

final class CountryRegistry
{
    /** @var array<string, CountryDefinition> */
    private array $countries = [];

    public function register(CountryDefinition $definition): void
    {
        $this->countries[$definition->countryCode] = $definition;
    }

    public function get(string $countryCode): ?CountryDefinition
    {
        return $this->countries[strtoupper($countryCode)] ?? null;
    }
}
