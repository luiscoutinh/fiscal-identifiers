<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Validation;

use FiscalIdentifiers\Contracts\LocalValidator;
use InvalidArgumentException;

final readonly class RegexValidator implements LocalValidator
{
    public function __construct(private string $pattern)
    {
        $result = false;
        set_error_handler(static fn (int $severity, string $message, string $file, int $line): bool => true);

        try {
            $result = preg_match($pattern, '');
        } finally {
            restore_error_handler();
        }

        if ($result === false) {
            throw new InvalidArgumentException('Invalid regular expression.');
        }
    }

    public function validate(string $value): bool
    {
        return preg_match($this->pattern, $value) === 1;
    }
}
