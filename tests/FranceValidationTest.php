<?php

declare(strict_types=1);

use FiscalIdentifiers\Configuration\IdentifierResolutionConfiguration;
use FiscalIdentifiers\Countries\FR\France;
use FiscalIdentifiers\Countries\FR\FranceNumberNormalizer;
use FiscalIdentifiers\Countries\FR\FranceSirenChecksumValidator;
use FiscalIdentifiers\Countries\FR\FranceVatNumberNormalizer;
use FiscalIdentifiers\Enums\ValidationStatus;
use FiscalIdentifiers\FiscalIdentifierValidator;
use FiscalIdentifiers\Registry\CountryRegistry;

function franceValidator(?IdentifierResolutionConfiguration $configuration = null): FiscalIdentifierValidator
{
    $countries = new CountryRegistry();
    $countries->register(France::definition());

    return new FiscalIdentifierValidator(
        $countries,
        $configuration ?? new IdentifierResolutionConfiguration(),
    );
}

test('validates a French personal fiscal number structurally', function (): void {
    $result = franceValidator()->validate('FR', ' 0000 000 000 000 ', 'numero_fiscal');

    expect($result->normalized)->toBe('0000000000000')
        ->and($result->isAccepted())->toBeTrue()
        ->and($result->steps['format']->status)->toBe(ValidationStatus::Passed)
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::NotSupported);
});

test('validates a French SIREN with its Luhn control digit', function (): void {
    $result = franceValidator()->validate('FR', '000 000 000', 'siren');

    expect($result->normalized)->toBe('000000000')
        ->and($result->isAccepted())->toBeTrue()
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::Passed);
});

test('rejects a French SIREN with an invalid control digit', function (): void {
    $result = franceValidator()->validate('FR', '000000001', 'siren');

    expect($result->isAccepted())->toBeFalse()
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::Failed);
});

test('validates a French VAT number structurally', function (): void {
    $result = franceValidator()->validate('FR', ' FR 00 000 000 000 ', 'vat_number');

    expect($result->normalized)->toBe('00000000000')
        ->and($result->isAccepted())->toBeTrue()
        ->and($result->steps['format']->status)->toBe(ValidationStatus::Passed)
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::NotSupported);
});

test('resolves French person and company subjects', function (): void {
    $person = franceValidator()->validateFor('FR', '0000000000000', 'person');
    $company = franceValidator()->validateFor('FR', '000000000', 'company');

    expect($person->normalized)->toBe('0000000000000')
        ->and($company->normalized)->toBe('000000000')
        ->and($company->steps['checksum']->status)->toBe(ValidationStatus::Passed);
});

test('uses SIREN when company is configured as the default subject', function (): void {
    $validator = franceValidator(new IdentifierResolutionConfiguration(defaultSubject: 'company'));
    $result = $validator->validate('FR', '000000000');

    expect($result->isAccepted())->toBeTrue()
        ->and($result->normalized)->toBe('000000000');
});

test('requires explicit context when France has no country default type', function (): void {
    expect(fn () => franceValidator()->validate('FR', '000000000'))
        ->toThrow(InvalidArgumentException::class);
});

test('rejects malformed French fiscal identifiers', function (): void {
    $person = franceValidator()->validate('FR', '000000000000', 'numero_fiscal');
    $siren = franceValidator()->validate('FR', '00000000', 'siren');
    $vat = franceValidator()->validate('FR', 'FR0000000000', 'vat_number');

    expect($person->steps['format']->status)->toBe(ValidationStatus::Failed)
        ->and($siren->steps['format']->status)->toBe(ValidationStatus::Failed)
        ->and($vat->steps['format']->status)->toBe(ValidationStatus::Failed);
});

test('normalizes French identifier presentation forms', function (): void {
    expect((new FranceNumberNormalizer())->normalize(' 000 000 000 '))->toBe('000000000')
        ->and((new FranceVatNumberNormalizer())->normalize(' fr 00 000 000 000 '))->toBe('00000000000')
        ->and((new FranceVatNumberNormalizer())->normalize('00000000000'))->toBe('00000000000');
});

test('French SIREN checksum validator rejects malformed input directly', function (): void {
    expect((new FranceSirenChecksumValidator())->validate('123'))->toBeFalse();
});
