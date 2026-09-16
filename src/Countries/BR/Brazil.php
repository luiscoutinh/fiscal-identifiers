<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Countries\BR;

use FiscalIdentifiers\Definitions\CountryDefinition;
use FiscalIdentifiers\Definitions\IdentifierDefinition;
use FiscalIdentifiers\IdentifierType;
use FiscalIdentifiers\Validation\RegexValidator;

final class Brazil
{
    public static function definition(): CountryDefinition
    {
        $cpf = IdentifierType::from('cpf');
        $cnpj = IdentifierType::from('cnpj');

        return new CountryDefinition(
            'BR',
            [
                $cpf->value => new IdentifierDefinition(
                    type: $cpf,
                    normalizer: new BrazilCpfNormalizer(),
                    formatValidator: new RegexValidator('/^\d{11}$/'),
                    checksumValidator: new BrazilCpfChecksumValidator(),
                ),
                $cnpj->value => new IdentifierDefinition(
                    type: $cnpj,
                    normalizer: new BrazilCnpjNormalizer(),
                    formatValidator: new RegexValidator('/^[A-Z0-9]{12}\d{2}$/'),
                    checksumValidator: new BrazilCnpjChecksumValidator(),
                ),
            ],
            subjectIdentifierTypes: [
                'person' => $cpf,
                'company' => $cnpj,
            ],
        );
    }
}
