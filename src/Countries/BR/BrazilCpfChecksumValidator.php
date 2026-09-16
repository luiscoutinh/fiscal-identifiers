<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Countries\BR;

use FiscalIdentifiers\Contracts\LocalValidator;

final class BrazilCpfChecksumValidator implements LocalValidator
{
    public function validate(string $value): bool
    {
        if (preg_match('/^\d{11}$/', $value) !== 1) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $value) === 1) {
            return false;
        }

        $base = substr($value, 0, 9);
        $first = $this->digit($base, 10);
        $second = $this->digit($base.$first, 11);

        return $value === $base.$first.$second;
    }

    private function digit(string $value, int $initialWeight): int
    {
        $sum = 0;

        foreach (str_split($value) as $index => $digit) {
            $sum += (int) $digit * ($initialWeight - $index);
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
