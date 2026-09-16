<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Countries\ES;

use FiscalIdentifiers\Contracts\LocalValidator;

final class SpainDniNifChecksumValidator implements LocalValidator
{
    private const CONTROL_LETTERS = 'TRWAGMYFPDXBNJZSQVHLCKE';

    public function validate(string $value): bool
    {
        if (preg_match('/^\d{8}[A-Z]$/', $value) !== 1) {
            return false;
        }

        $number = (int) substr($value, 0, 8);
        $expected = self::CONTROL_LETTERS[$number % 23];

        return $value[8] === $expected;
    }
}
