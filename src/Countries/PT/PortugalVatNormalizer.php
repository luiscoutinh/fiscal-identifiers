<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Countries\PT;

use FiscalIdentifiers\Contracts\Normalizer;

final class PortugalVatNormalizer implements Normalizer
{
    public function normalize(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = preg_replace('/[\s\-.]+/', '', $value) ?? $value;

        return str_starts_with($value, 'PT') ? substr($value, 2) : $value;
    }
}
