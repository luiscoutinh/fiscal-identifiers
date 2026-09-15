<?php

declare(strict_types=1);

namespace LuisCoutinho\FiscalIdentifiers\Contracts;

interface Normalizer
{
    public function normalize(string $value): string;
}
