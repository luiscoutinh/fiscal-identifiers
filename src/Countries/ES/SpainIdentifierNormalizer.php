<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Countries\ES;

use FiscalIdentifiers\Contracts\Normalizer;

final class SpainIdentifierNormalizer implements Normalizer
{
    public function normalize(string $value): string
    {
        $value = strtoupper(trim($value));

        return preg_replace('/[\s.\-]+/', '', $value) ?? $value;
    }
}
