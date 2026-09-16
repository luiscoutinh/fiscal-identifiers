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
        $nipc = IdentifierType::from('nipc');
        $normalizer = new PortugalNifNormalizer();
        $checksum = new PortugalNifChecksumValidator();

        return new CountryDefinition(
            'PT',
            [
                $nif->value => new IdentifierDefinition(
                    type: $nif,
                    normalizer: $normalizer,
                    formatValidator: new RegexValidator('/^\d{9}$/'),
                    checksumValidator: $checksum,
                ),
                $nipc->value => new IdentifierDefinition(
                    type: $nipc,
                    normalizer: $normalizer,
                    formatValidator: new RegexValidator('/^\d{9}$/'),
                    checksumValidator: $checksum,
                ),
            ],
            defaultIdentifierType: $nif,
            subjectIdentifierTypes: [
                'person' => $nif,
                'company' => $nipc,
            ],
        );
    }
}
