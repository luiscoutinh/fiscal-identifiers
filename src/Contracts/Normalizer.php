<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Contracts;

interface Normalizer
{
    public function normalize(string $value): string;
}
