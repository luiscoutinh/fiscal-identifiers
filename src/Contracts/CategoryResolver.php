<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Contracts;

use FiscalIdentifiers\IdentifierCategory;

interface CategoryResolver
{
    public function resolve(string $value): ?IdentifierCategory;
}
