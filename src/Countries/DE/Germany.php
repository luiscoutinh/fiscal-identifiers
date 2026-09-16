<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Countries\DE;

use FiscalIdentifiers\Definitions\CountryDefinition;
use FiscalIdentifiers\Definitions\IdentifierDefinition;
use FiscalIdentifiers\IdentifierType;
use FiscalIdentifiers\Validation\RegexValidator;

final class Germany
{
    public static function definition(): CountryDefinition
    {
        $idnr = IdentifierType::from('idnr');
        $widnr = IdentifierType::from('widnr');
        $ustIdnr = IdentifierType::from('ust_idnr');
        $steuernummer = IdentifierType::from('steuernummer');
        $normalizer = new GermanyIdentifierNormalizer();

        return new CountryDefinition(
            'DE',
            [
                $idnr->value => new IdentifierDefinition(
                    type: $idnr,
                    normalizer: $normalizer,
                    formatValidator: new RegexValidator('/\A[0-9]{11}\z/'),
                ),
                $widnr->value => new IdentifierDefinition(
                    type: $widnr,
                    normalizer: $normalizer,
                    formatValidator: new RegexValidator('/\ADE[0-9]{9}-[0-9]{5}\z/'),
                ),
                $ustIdnr->value => new IdentifierDefinition(
                    type: $ustIdnr,
                    normalizer: $normalizer,
                    formatValidator: new RegexValidator('/\ADE[0-9]{9}\z/'),
                ),
                $steuernummer->value => new IdentifierDefinition(
                    type: $steuernummer,
                    normalizer: $normalizer,
                    formatValidator: new RegexValidator('/\A[0-9]{13}\z/'),
                ),
            ],
            subjectIdentifierTypes: [
                'person' => $idnr,
                'company' => $widnr,
            ],
        );
    }
}
