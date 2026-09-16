<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Countries\BR;

use FiscalIdentifiers\Contracts\Normalizer;

final class BrazilCnpjNormalizer implements Normalizer
{
    public function normalize(string $value): string
    {
        $value = strtoupper(trim($value));

        return preg_replace('/[\s.\/\-]+/', '', $value) ?? $value;
    }
}
