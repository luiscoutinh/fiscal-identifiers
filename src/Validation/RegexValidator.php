<?php

declare(strict_types=1);

namespace LuisCoutinho\FiscalIdentifiers\Validation;

use LuisCoutinho\FiscalIdentifiers\Contracts\LocalValidator;

final readonly class RegexValidator implements LocalValidator
{
    public function __construct(private string $pattern) {}

    public function validate(string $value): bool
    {
        return preg_match($this->pattern, $value) === 1;
    }
}
