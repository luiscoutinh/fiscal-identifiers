<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Countries\FR;

use FiscalIdentifiers\Contracts\Normalizer;

final class FranceVatNumberNormalizer implements Normalizer
{
    public function normalize(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = preg_replace('/\s+/', '', $value) ?? $value;

        return str_starts_with($value, 'FR') ? substr($value, 2) : $value;
    }
}
