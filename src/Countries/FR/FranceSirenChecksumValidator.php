<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Countries\FR;

use FiscalIdentifiers\Contracts\LocalValidator;

final class FranceSirenChecksumValidator implements LocalValidator
{
    public function validate(string $value): bool
    {
        if (preg_match('/^\d{9}$/', $value) !== 1) {
            return false;
        }

        $sum = 0;

        for ($index = 0; $index < 9; ++$index) {
            $digit = (int) $value[$index];

            if ($index % 2 === 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return $sum % 10 === 0;
    }
}
