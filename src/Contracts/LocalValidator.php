<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Contracts;

interface LocalValidator
{
    public function validate(string $value): bool;
}
