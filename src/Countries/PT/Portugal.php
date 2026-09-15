<?php

declare(strict_types=1);

namespace LuisCoutinho\FiscalIdentifiers\Countries\PT;

use LuisCoutinho\FiscalIdentifiers\Definitions\CountryDefinition;
use LuisCoutinho\FiscalIdentifiers\Definitions\IdentifierDefinition;
use LuisCoutinho\FiscalIdentifiers\Enums\IdentifierType;
use LuisCoutinho\FiscalIdentifiers\Validation\RegexValidator;

final class Portugal
{
    public static function definition(): CountryDefinition
    {
        return new CountryDefinition('PT', [
            IdentifierType::Vat->value => new IdentifierDefinition(
                type: IdentifierType::Vat,
                normalizer: new PortugalVatNormalizer(),
                formatValidator: new RegexValidator('/^\d{9}$/'),
                checksumValidator: new PortugalNifChecksumValidator(),
                externalProvider: 'vies',
            ),
        ]);
    }
}
