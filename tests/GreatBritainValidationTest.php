<?php

declare(strict_types=1);

use FiscalIdentifiers\Configuration\IdentifierResolutionConfiguration;
use FiscalIdentifiers\Countries\GB\GreatBritain;
use FiscalIdentifiers\Countries\GB\GreatBritainEmployerPayeReferenceNormalizer;
use FiscalIdentifiers\Countries\GB\GreatBritainUtrNormalizer;
use FiscalIdentifiers\Countries\GB\GreatBritainVatRegistrationNumberNormalizer;
use FiscalIdentifiers\Enums\ValidationStatus;
use FiscalIdentifiers\FiscalIdentifierValidator;
use FiscalIdentifiers\Registry\CountryRegistry;

function greatBritainValidator(?IdentifierResolutionConfiguration $configuration = null): FiscalIdentifierValidator
{
    $countries = new CountryRegistry();
    $countries->register(GreatBritain::definition());

    return new FiscalIdentifierValidator(
        $countries,
        $configuration ?? new IdentifierResolutionConfiguration(),
    );
}

test('validates a United Kingdom UTR structurally', function (): void {
    $result = greatBritainValidator()->validate('GB', ' 12345 67890 ', 'utr');

    expect($result->normalized)->toBe('1234567890')
        ->and($result->isAccepted())->toBeTrue()
        ->and($result->steps['format']->status)->toBe(ValidationStatus::Passed)
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::NotSupported);
});

test('resolves both person and company subjects to UTR', function (): void {
    $person = greatBritainValidator()->validateFor('GB', '1234567890', 'person');
    $company = greatBritainValidator()->validateFor('GB', '1234567890', 'company');

    expect($person->isAccepted())->toBeTrue()
        ->and($company->isAccepted())->toBeTrue();
});

test('uses UTR when company is configured as the default subject', function (): void {
    $validator = greatBritainValidator(new IdentifierResolutionConfiguration(defaultSubject: 'company'));
    $result = $validator->validate('GB', '1234567890');

    expect($result->isAccepted())->toBeTrue()
        ->and($result->normalized)->toBe('1234567890');
});

test('requires explicit context when no default subject is configured', function (): void {
    expect(fn () => greatBritainValidator()->validate('GB', '1234567890'))
        ->toThrow(InvalidArgumentException::class);
});

test('validates a standard UK VAT registration number structurally', function (): void {
    $result = greatBritainValidator()->validate('GB', ' GB 123 456 789 ', 'vat_registration_number');

    expect($result->normalized)->toBe('123456789')
        ->and($result->isAccepted())->toBeTrue()
        ->and($result->steps['format']->status)->toBe(ValidationStatus::Passed)
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::NotSupported);
});

test('validates an employer PAYE reference structurally', function (): void {
    $result = greatBritainValidator()->validate('GB', ' 123 / ab456 ', 'employer_paye_reference');

    expect($result->normalized)->toBe('123/AB456')
        ->and($result->isAccepted())->toBeTrue()
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::NotSupported);
});

test('rejects malformed Great Britain identifiers', function (): void {
    $utr = greatBritainValidator()->validate('GB', '123456789', 'utr');
    $vat = greatBritainValidator()->validate('GB', 'GB12345678', 'vat_registration_number');
    $paye = greatBritainValidator()->validate('GB', '12/AB456', 'employer_paye_reference');

    expect($utr->steps['format']->status)->toBe(ValidationStatus::Failed)
        ->and($vat->steps['format']->status)->toBe(ValidationStatus::Failed)
        ->and($paye->steps['format']->status)->toBe(ValidationStatus::Failed);
});

test('normalizes Great Britain identifier presentation forms', function (): void {
    expect((new GreatBritainUtrNormalizer())->normalize(' 12345 67890 '))->toBe('1234567890')
        ->and((new GreatBritainVatRegistrationNumberNormalizer())->normalize(' gb 123 456 789 '))->toBe('123456789')
        ->and((new GreatBritainVatRegistrationNumberNormalizer())->normalize('123456789'))->toBe('123456789')
        ->and((new GreatBritainEmployerPayeReferenceNormalizer())->normalize(' 123 / ab456 '))->toBe('123/AB456');
});
