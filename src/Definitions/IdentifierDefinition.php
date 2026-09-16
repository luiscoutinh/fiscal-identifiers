<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Definitions;

use FiscalIdentifiers\Contracts\LocalValidator;
use FiscalIdentifiers\Contracts\Normalizer;
use FiscalIdentifiers\Enums\IdentifierType;

final readonly class IdentifierDefinition
{
    public function __construct(
        public IdentifierType $type,
        public Normalizer $normalizer,
        public LocalValidator $formatValidator,
        public ?LocalValidator $checksumValidator = null,
    ) {
    }
}
