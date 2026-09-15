<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Countries\PT;

use FiscalIdentifiers\Definitions\CountryDefinition;
use FiscalIdentifiers\Definitions\IdentifierDefinition;
use FiscalIdentifiers\Enums\IdentifierType;
use FiscalIdentifiers\Validation\RegexValidator;

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
