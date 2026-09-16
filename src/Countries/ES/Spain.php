<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Countries\ES;

use FiscalIdentifiers\Definitions\CountryDefinition;
use FiscalIdentifiers\Definitions\IdentifierDefinition;
use FiscalIdentifiers\IdentifierType;
use FiscalIdentifiers\Validation\RegexValidator;

final class Spain
{
    public static function definition(): CountryDefinition
    {
        $dniNif = IdentifierType::from('dni_nif');
        $nie = IdentifierType::from('nie');
        $entityNif = IdentifierType::from('entity_nif');
        $normalizer = new SpainIdentifierNormalizer();

        return new CountryDefinition(
            'ES',
            [
                $dniNif->value => new IdentifierDefinition(
                    type: $dniNif,
                    normalizer: $normalizer,
                    formatValidator: new RegexValidator('/^\d{8}[A-Z]$/'),
                    checksumValidator: new SpainDniNifChecksumValidator(),
                ),
                $nie->value => new IdentifierDefinition(
                    type: $nie,
                    normalizer: $normalizer,
                    formatValidator: new RegexValidator('/^[XYZ]\d{7}[A-Z]$/'),
                    checksumValidator: new SpainNieChecksumValidator(),
                ),
                $entityNif->value => new IdentifierDefinition(
                    type: $entityNif,
                    normalizer: $normalizer,
                    formatValidator: new RegexValidator('/^[ABCDEFGHJNPQRSUVW]\d{7}[A-Z0-9]$/'),
                ),
            ],
            subjectIdentifierTypes: [
                'company' => $entityNif,
            ],
        );
    }
}
