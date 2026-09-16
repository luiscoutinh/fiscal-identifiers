<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Countries\BR;

use FiscalIdentifiers\Contracts\LocalValidator;

final class BrazilCnpjChecksumValidator implements LocalValidator
{
    private const FIRST_WEIGHTS = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    private const SECOND_WEIGHTS = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    public function validate(string $value): bool
    {
        if (preg_match('/^[A-Z0-9]{12}\d{2}$/', $value) !== 1) {
            return false;
        }

        if (preg_match('/^(\d)\1{13}$/', $value) === 1) {
            return false;
        }

        $base = substr($value, 0, 12);
        $first = $this->digit($base, self::FIRST_WEIGHTS);
        $second = $this->digit($base.$first, self::SECOND_WEIGHTS);

        return $value === $base.$first.$second;
    }

    /** @param list<int> $weights */
    private function digit(string $value, array $weights): int
    {
        $sum = 0;

        foreach (str_split($value) as $index => $character) {
            $sum += (ord($character) - 48) * $weights[$index];
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
