<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Validation;

use FiscalIdentifiers\Contracts\LocalValidator;
use InvalidArgumentException;

final readonly class RegexValidator implements LocalValidator
{
    public function __construct(private string $pattern)
    {
        if (@preg_match($pattern, '') === false) {
            throw new InvalidArgumentException('Invalid regular expression.');
        }
    }

    public function validate(string $value): bool
    {
        return preg_match($this->pattern, $value) === 1;
    }
}
