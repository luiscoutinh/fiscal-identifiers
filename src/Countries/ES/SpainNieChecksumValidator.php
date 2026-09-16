<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Countries\ES;

use FiscalIdentifiers\Contracts\LocalValidator;

final class SpainNieChecksumValidator implements LocalValidator
{
    private const CONTROL_LETTERS = 'TRWAGMYFPDXBNJZSQVHLCKE';

    /** @var array<string, string> */
    private const PREFIX_DIGITS = [
        'X' => '0',
        'Y' => '1',
        'Z' => '2',
    ];

    public function validate(string $value): bool
    {
        if (preg_match('/^[XYZ]\d{7}[A-Z]$/', $value) !== 1) {
            return false;
        }

        $number = (int) (self::PREFIX_DIGITS[$value[0]].substr($value, 1, 7));
        $expected = self::CONTROL_LETTERS[$number % 23];

        return $value[8] === $expected;
    }
}
