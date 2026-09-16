<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Countries\ES;

use FiscalIdentifiers\Contracts\CategoryResolver;
use FiscalIdentifiers\IdentifierCategory;

final class SpainEntityNifCategoryResolver implements CategoryResolver
{
    public function resolve(string $value): ?IdentifierCategory
    {
        if (preg_match('/\A[ABCDEFGHJNPQRSUVW][0-9]{7}[A-Z0-9]\z/', $value) !== 1) {
            return null;
        }

        return IdentifierCategory::from($value[0]);
    }
}
