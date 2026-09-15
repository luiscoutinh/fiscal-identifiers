<?php

declare(strict_types=1);

namespace LuisCoutinho\FiscalIdentifiers\Enums;

enum IdentifierType: string
{
    case Vat = 'vat';
    case BusinessTax = 'business_tax';
}
