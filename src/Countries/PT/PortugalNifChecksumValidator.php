<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Countries\PT;

use FiscalIdentifiers\Contracts\LocalValidator;

final class PortugalNifChecksumValidator implements LocalValidator
{
    public function validate(string $value): bool
    {
        if (preg_match('/^\d{9}$/', $value) !== 1) {
            return false;
        }

        $sum = 0;

        for ($index = 0; $index < 8; ++$index) {
            $sum += ((int) $value[$index]) * (9 - $index);
        }

        $remainder = $sum % 11;
        $checkDigit = $remainder < 2 ? 0 : 11 - $remainder;

        return $checkDigit === (int) $value[8];
    }
}
