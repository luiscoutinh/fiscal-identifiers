<?php

declare(strict_types=1);

namespace LuisCoutinho\FiscalIdentifiers\Definitions;

use LuisCoutinho\FiscalIdentifiers\Contracts\LocalValidator;
use LuisCoutinho\FiscalIdentifiers\Contracts\Normalizer;
use LuisCoutinho\FiscalIdentifiers\Enums\IdentifierType;

final readonly class IdentifierDefinition
{
    public function __construct(
        public IdentifierType $type,
        public Normalizer $normalizer,
        public LocalValidator $formatValidator,
        public ?LocalValidator $checksumValidator = null,
        public ?string $externalProvider = null,
    ) {}
}
