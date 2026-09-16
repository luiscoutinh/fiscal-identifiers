<?php

declare(strict_types=1);

use FiscalIdentifiers\Configuration\IdentifierResolutionConfiguration;
use FiscalIdentifiers\Countries\ES\Spain;
use FiscalIdentifiers\Countries\ES\SpainDniNifChecksumValidator;
use FiscalIdentifiers\Countries\ES\SpainIdentifierNormalizer;
use FiscalIdentifiers\Countries\ES\SpainNieChecksumValidator;
use FiscalIdentifiers\Enums\ValidationStatus;
use FiscalIdentifiers\FiscalIdentifierValidator;
use FiscalIdentifiers\Registry\CountryRegistry;

function spainValidator(?IdentifierResolutionConfiguration $configuration = null): FiscalIdentifierValidator
{
    $countries = new CountryRegistry();
    $countries->register(Spain::definition());

    return new FiscalIdentifierValidator(
        $countries,
        $configuration ?? new IdentifierResolutionConfiguration(),
    );
}

test('validates a Spanish citizen DNI-based NIF explicitly', function (): void {
    $result = spainValidator()->validate(' es ', ' 12.345.678-z ', 'dni_nif');

    expect($result->normalized)->toBe('12345678Z')
        ->and($result->isAccepted())->toBeTrue()
        ->and($result->steps['format']->status)->toBe(ValidationStatus::Passed)
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::Passed);
});

test('rejects an invalid DNI-based NIF control letter', function (): void {
    $result = spainValidator()->validate('ES', '12345678A', 'dni_nif');

    expect($result->isAccepted())->toBeFalse()
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::Failed);
});

test('validates a Spanish NIE explicitly', function (): void {
    $result = spainValidator()->validate('ES', ' x-1234567-l ', 'nie');

    expect($result->normalized)->toBe('X1234567L')
        ->and($result->isAccepted())->toBeTrue()
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::Passed);
});

test('rejects an invalid NIE control letter', function (): void {
    $result = spainValidator()->validate('ES', 'X1234567A', 'nie');

    expect($result->isAccepted())->toBeFalse()
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::Failed);
});

test('resolves company subject to the entity NIF scheme', function (): void {
    $result = spainValidator()->validateFor('ES', ' b-1234567-a ', 'company');

    expect($result->normalized)->toBe('B1234567A')
        ->and($result->isAccepted())->toBeTrue()
        ->and($result->steps['format']->status)->toBe(ValidationStatus::Passed)
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::NotSupported);
});

test('uses company as the configurable default subject for Spain', function (): void {
    $validator = spainValidator(new IdentifierResolutionConfiguration(defaultSubject: 'company'));
    $result = $validator->validate('ES', 'B1234567A');

    expect($result->isAccepted())->toBeTrue()
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::NotSupported);
});

test('does not pretend that person subject uniquely selects a Spanish identifier scheme', function (): void {
    $result = spainValidator()->validateFor('ES', '12345678Z', 'person');

    expect($result->isSupported())->toBeFalse()
        ->and($result->isAccepted())->toBeFalse();
});

test('requires an explicit type when Spain has no default subject or country type', function (): void {
    expect(fn () => spainValidator()->validate('ES', '12345678Z'))
        ->toThrow(\InvalidArgumentException::class);
});

test('rejects malformed Spanish identifier formats', function (): void {
    $dni = spainValidator()->validate('ES', '1234', 'dni_nif');
    $nie = spainValidator()->validate('ES', 'A1234567Z', 'nie');
    $entity = spainValidator()->validate('ES', 'K1234567A', 'entity_nif');

    expect($dni->steps['format']->status)->toBe(ValidationStatus::Failed)
        ->and($nie->steps['format']->status)->toBe(ValidationStatus::Failed)
        ->and($entity->steps['format']->status)->toBe(ValidationStatus::Failed);
});

test('covers Spanish normalization and checksum building blocks', function (): void {
    $normalizer = new SpainIdentifierNormalizer();
    $dniChecksum = new SpainDniNifChecksumValidator();
    $nieChecksum = new SpainNieChecksumValidator();

    expect($normalizer->normalize(' 12.345.678-z '))->toBe('12345678Z')
        ->and($dniChecksum->validate('invalid'))->toBeFalse()
        ->and($dniChecksum->validate('12345678Z'))->toBeTrue()
        ->and($nieChecksum->validate('invalid'))->toBeFalse()
        ->and($nieChecksum->validate('X1234567L'))->toBeTrue();
});
