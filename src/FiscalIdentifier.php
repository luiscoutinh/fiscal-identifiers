<?php

declare(strict_types=1);

namespace FiscalIdentifiers;

use FiscalIdentifiers\Enums\IdentifierType;

final readonly class FiscalIdentifier
{
    public function __construct(
        public string $countryCode,
        public IdentifierType $type,
        public string $value,
    ) {
    }
}
