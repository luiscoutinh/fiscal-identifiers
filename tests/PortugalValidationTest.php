<?php

declare(strict_types=1);

use LuisCoutinho\FiscalIdentifiers\Configuration\ValidationConfiguration;
use LuisCoutinho\FiscalIdentifiers\Countries\PT\Portugal;
use LuisCoutinho\FiscalIdentifiers\FiscalIdentifierValidator;
use LuisCoutinho\FiscalIdentifiers\Registry\CountryRegistry;
use LuisCoutinho\FiscalIdentifiers\Registry\ProviderRegistry;

function portugalValidator(?ValidationConfiguration $configuration = null): FiscalIdentifierValidator
{
    $countries = new CountryRegistry();
    $countries->register(Portugal::definition());

    return new FiscalIdentifierValidator(
        $countries,
        new ProviderRegistry(),
        $configuration ?? new ValidationConfiguration(),
    );
}

test('normalizes and accepts a structurally valid Portuguese fiscal identifier when VIES is unavailable', function (): void {
    $result = portugalValidator()->validate('PT', 'PT 501 964 843');

    expect($result->normalized)->toBe('501964843')
        ->and($result->isAccepted())->toBeTrue()
        ->and($result->isVerified())->toBeFalse()
        ->and($result->steps['external']->status->value)->toBe('unavailable');
});

test('rejects a Portuguese fiscal identifier with an invalid checksum', function (): void {
    $result = portugalValidator()->validate('PT', '501964844');

    expect($result->isAccepted())->toBeFalse()
        ->and($result->steps['checksum']->status->value)->toBe('failed');
});

test('can disable external verification for one country', function (): void {
    $configuration = new ValidationConfiguration(countryExternalValidation: ['PT' => false]);
    $result = portugalValidator($configuration)->validate('PT', '501964843');

    expect($result->isAccepted())->toBeTrue()
        ->and($result->steps['external']->status->value)->toBe('disabled');
});

test('maps the acceptance decision to custom output values', function (): void {
    $result = portugalValidator()->validate('PT', '501964843');

    expect($result->toConfiguredValue('valid', 'invalid'))->toBe('valid')
        ->and($result->toConfiguredValue(1, 0))->toBe(1);
});
