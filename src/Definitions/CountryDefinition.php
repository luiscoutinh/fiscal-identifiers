<?php

declare(strict_types=1);

namespace LuisCoutinho\FiscalIdentifiers\Definitions;

use InvalidArgumentException;
use LuisCoutinho\FiscalIdentifiers\Enums\IdentifierType;
use Symfony\Component\Intl\Countries;

final readonly class CountryDefinition
{
    /** @param array<string, IdentifierDefinition> $identifiers */
    public function __construct(
        public string $countryCode,
        public array $identifiers,
    ) {
        if (! Countries::exists($countryCode)) {
            throw new InvalidArgumentException("Unknown ISO 3166-1 alpha-2 country code: {$countryCode}");
        }
    }

    public function identifier(IdentifierType $type): ?IdentifierDefinition
    {
        return $this->identifiers[$type->value] ?? null;
    }
}
