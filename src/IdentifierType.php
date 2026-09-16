<?php

declare(strict_types=1);

namespace FiscalIdentifiers;

use InvalidArgumentException;

final readonly class IdentifierType
{
    private function __construct(public string $value)
    {
    }

    public static function from(string $value): self
    {
        $value = strtolower(trim($value));

        if (preg_match('/\A[a-z][a-z0-9_]*\z/', $value) !== 1) {
            throw new InvalidArgumentException("Invalid identifier type: {$value}");
        }

        return new self($value);
    }
}
