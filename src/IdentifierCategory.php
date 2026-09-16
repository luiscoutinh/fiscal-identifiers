<?php

declare(strict_types=1);

namespace FiscalIdentifiers;

use InvalidArgumentException;

final readonly class IdentifierCategory
{
    private function __construct(public string $value)
    {
    }

    public static function from(string $value): self
    {
        $value = trim($value);

        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9_.-]*\z/', $value) !== 1) {
            throw new InvalidArgumentException("Invalid identifier category: {$value}");
        }

        return new self($value);
    }

    public function equals(self|string $other): bool
    {
        $other = is_string($other) ? self::from($other) : $other;

        return strcasecmp($this->value, $other->value) === 0;
    }
}
