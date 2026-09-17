<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Countries\FR;

use FiscalIdentifiers\Definitions\CountryDefinition;
use FiscalIdentifiers\Definitions\IdentifierDefinition;
use FiscalIdentifiers\IdentifierType;
use FiscalIdentifiers\Validation\RegexValidator;

final class France
{
    public static function definition(): CountryDefinition
    {
        $numeroFiscal = IdentifierType::from('numero_fiscal');
        $siren = IdentifierType::from('siren');
        $vatNumber = IdentifierType::from('vat_number');
        $numberNormalizer = new FranceNumberNormalizer();

        return new CountryDefinition(
            'FR',
            [
                $numeroFiscal->value => new IdentifierDefinition(
                    type: $numeroFiscal,
                    normalizer: $numberNormalizer,
                    formatValidator: new RegexValidator('/^\d{13}$/'),
                ),
                $siren->value => new IdentifierDefinition(
                    type: $siren,
                    normalizer: $numberNormalizer,
                    formatValidator: new RegexValidator('/^\d{9}$/'),
                    checksumValidator: new FranceSirenChecksumValidator(),
                ),
                $vatNumber->value => new IdentifierDefinition(
                    type: $vatNumber,
                    normalizer: new FranceVatNumberNormalizer(),
                    formatValidator: new RegexValidator('/^\d{11}$/'),
                ),
            ],
            subjectIdentifierTypes: [
                'person' => $numeroFiscal,
                'company' => $siren,
            ],
        );
    }
}
