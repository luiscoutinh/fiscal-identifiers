<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Countries\GB;

use FiscalIdentifiers\Definitions\CountryDefinition;
use FiscalIdentifiers\Definitions\IdentifierDefinition;
use FiscalIdentifiers\IdentifierType;
use FiscalIdentifiers\Validation\RegexValidator;

final class GreatBritain
{
    public static function definition(): CountryDefinition
    {
        $utr = IdentifierType::from('utr');
        $vatRegistrationNumber = IdentifierType::from('vat_registration_number');
        $employerPayeReference = IdentifierType::from('employer_paye_reference');

        return new CountryDefinition(
            'GB',
            [
                $utr->value => new IdentifierDefinition(
                    type: $utr,
                    normalizer: new GreatBritainUtrNormalizer(),
                    formatValidator: new RegexValidator('/^\d{10}$/'),
                ),
                $vatRegistrationNumber->value => new IdentifierDefinition(
                    type: $vatRegistrationNumber,
                    normalizer: new GreatBritainVatRegistrationNumberNormalizer(),
                    formatValidator: new RegexValidator('/^\d{9}$/'),
                ),
                $employerPayeReference->value => new IdentifierDefinition(
                    type: $employerPayeReference,
                    normalizer: new GreatBritainEmployerPayeReferenceNormalizer(),
                    formatValidator: new RegexValidator('/^\d{3}\/[A-Z0-9]{1,10}$/'),
                ),
            ],
            subjectIdentifierTypes: [
                'person' => $utr,
                'company' => $utr,
            ],
        );
    }
}
