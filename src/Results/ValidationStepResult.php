<?php

declare(strict_types=1);

namespace LuisCoutinho\FiscalIdentifiers\Results;

use LuisCoutinho\FiscalIdentifiers\Enums\ValidationStatus;

final readonly class ValidationStepResult
{
    public function __construct(
        public ValidationStatus $status,
        public ?string $message = null,
    ) {}
}
