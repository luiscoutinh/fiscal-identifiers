<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Countries\BR;

use FiscalIdentifiers\Contracts\Normalizer;

final class BrazilCpfNormalizer implements Normalizer
{
    public function normalize(string $value): string
    {
        $value = trim($value);

        return preg_replace('/[\s.\-]+/', '', $value) ?? $value;
    }
}
