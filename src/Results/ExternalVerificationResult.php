<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Results;

use FiscalIdentifiers\Enums\ValidationStatus;

final readonly class ExternalVerificationResult
{
    /** @param array<string, scalar|null> $metadata */
    public function __construct(
        public ValidationStatus $status,
        public array $metadata = [],
        public ?string $message = null,
    ) {}
}
