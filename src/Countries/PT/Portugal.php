<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Countries\PT;

use FiscalIdentifiers\Definitions\CountryDefinition;
use FiscalIdentifiers\Definitions\IdentifierDefinition;
use FiscalIdentifiers\IdentifierType;
use FiscalIdentifiers\Validation\RegexValidator;

final class Portugal
{
    public static function definition(): CountryDefinition
    {
        $nif = IdentifierType::from('nif');

        return new CountryDefinition(
            'PT',
            [
                $nif->value => new IdentifierDefinition(
                    type: $nif,
                    normalizer: new PortugalNifNormalizer(),
                    formatValidator: new RegexValidator('/^\d{9}$/'),
                    checksumValidator: new PortugalNifChecksumValidator(),
                ),
            ],
            defaultIdentifierType: $nif,
        );
    }
}
