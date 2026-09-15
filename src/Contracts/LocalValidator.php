<?php

declare(strict_types=1);

namespace LuisCoutinho\FiscalIdentifiers\Contracts;

interface LocalValidator
{
    public function validate(string $value): bool;
}
