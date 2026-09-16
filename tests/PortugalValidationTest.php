<?php

declare(strict_types=1);

use FiscalIdentifiers\Configuration\IdentifierResolutionConfiguration;
use FiscalIdentifiers\Contracts\LocalValidator;
use FiscalIdentifiers\Contracts\Normalizer;
use FiscalIdentifiers\Countries\PT\Portugal;
use FiscalIdentifiers\Countries\PT\PortugalNifChecksumValidator;
use FiscalIdentifiers\Countries\PT\PortugalNifNormalizer;
use FiscalIdentifiers\Definitions\CountryDefinition;
use FiscalIdentifiers\Definitions\IdentifierDefinition;
use FiscalIdentifiers\Enums\ValidationStatus;
use FiscalIdentifiers\FiscalIdentifierValidator;
use FiscalIdentifiers\IdentifierType;
use FiscalIdentifiers\Registry\CountryRegistry;
use FiscalIdentifiers\Validation\RegexValidator;

function portugalValidator(?IdentifierResolutionConfiguration $configuration = null): FiscalIdentifierValidator
{
    $countries = new CountryRegistry();
    $countries->register(Portugal::definition());

    return new FiscalIdentifierValidator(
        $countries,
        $configuration ?? new IdentifierResolutionConfiguration(),
    );
}

test('normalizes and accepts a checksum-consistent Portuguese NIF using the country default type', function (): void {
    $result = portugalValidator()->validate(' pt ', ' PT 999.999-990 ');

    expect($result->normalized)->toBe('999999990')
        ->and($result->isAccepted())->toBeTrue()
        ->and($result->isSupported())->toBeTrue()
        ->and($result->steps['normalization']->status)->toBe(ValidationStatus::Passed)
        ->and($result->steps['format']->status)->toBe(ValidationStatus::Passed)
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::Passed)
        ->and($result->toConfiguredValue('valid', 'invalid', 'unsupported'))->toBe('valid')
        ->and($result->toArray()['decision'])->toBe('accepted')
        ->and($result->toArray()['supported'])->toBeTrue();
});

test('resolves Portuguese identifiers by subject', function (): void {
    $person = portugalValidator()->validateFor('PT', '999999990', 'person');
    $company = portugalValidator()->validateFor('PT', '500000000', 'company');

    expect($person->isAccepted())->toBeTrue()
        ->and($company->isAccepted())->toBeTrue();
});

test('uses company NIPC when company is configured as the default subject', function (): void {
    $validator = portugalValidator(new IdentifierResolutionConfiguration(defaultSubject: 'company'));
    $result = $validator->validate('PT', '500 000 000');

    expect($result->normalized)->toBe('500000000')
        ->and($result->isAccepted())->toBeTrue();
});

test('accepts explicit Portuguese NIF and NIPC identifier types', function (): void {
    $nif = portugalValidator()->validate('PT', '999999990', ' NIF ');
    $nipc = portugalValidator()->validate('PT', '500000000', ' NIPC ');

    expect($nif->isAccepted())->toBeTrue()
        ->and($nipc->isAccepted())->toBeTrue();
});

test('rejects invalid format and checksum', function (): void {
    $format = portugalValidator()->validate('PT', 'abc');
    $checksum = portugalValidator()->validate('PT', '999999991');

    expect($format->isAccepted())->toBeFalse()
        ->and($format->isSupported())->toBeTrue()
        ->and($format->toConfiguredValue('valid', 'invalid', 'unsupported'))->toBe('invalid')
        ->and($format->steps['format']->status)->toBe(ValidationStatus::Failed)
        ->and($checksum->isAccepted())->toBeFalse()
        ->and($checksum->steps['checksum']->status)->toBe(ValidationStatus::Failed);
});

test('distinguishes a valid unsupported jurisdiction from an invalid country code', function (): void {
    $validator = new FiscalIdentifierValidator(new CountryRegistry());
    $unsupported = $validator->validate('AF', ' 123 ');

    expect($unsupported->isAccepted())->toBeFalse()
        ->and($unsupported->isSupported())->toBeFalse()
        ->and($unsupported->normalized)->toBe('123')
        ->and($unsupported->toConfiguredValue('valid', 'invalid', 'unsupported'))->toBe('unsupported')
        ->and($unsupported->toArray()['decision'])->toBe('not_supported')
        ->and($unsupported->steps['format']->status)->toBe(ValidationStatus::NotSupported)
        ->and($unsupported->steps['checksum']->status)->toBe(ValidationStatus::NotSupported)
        ->and(fn () => $validator->validate('XX', '123'))->toThrow(\InvalidArgumentException::class);
});

test('represents a valid country with an unsupported identifier type as not supported', function (): void {
    $result = portugalValidator()->validate('PT', '999999990', 'vat');

    expect($result->isSupported())->toBeFalse()
        ->and($result->isAccepted())->toBeFalse();
});

test('supports extensible identifier types and definitions without checksum validation', function (): void {
    $ein = IdentifierType::from('ein');
    $countries = new CountryRegistry();
    $countries->register(new CountryDefinition(' us ', [
        $ein->value => new IdentifierDefinition(
            $ein,
            new class () implements Normalizer {
                public function normalize(string $value): string
                {
                    return trim($value);
                }
            },
            new class () implements LocalValidator {
                public function validate(string $value): bool
                {
                    return $value === '123';
                }
            },
        ),
    ], defaultIdentifierType: 'ein'));

    $result = (new FiscalIdentifierValidator($countries))->validate('us', ' 123 ', $ein);

    expect($result->isAccepted())->toBeTrue()
        ->and($result->steps['format']->status)->toBe(ValidationStatus::Passed)
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::NotSupported);
});

test('requires explicit identifier context when a country has no default type', function (): void {
    $ein = IdentifierType::from('ein');
    $definition = new IdentifierDefinition(
        $ein,
        new class () implements Normalizer {
            public function normalize(string $value): string
            {
                return trim($value);
            }
        },
        new RegexValidator('/^\d+$/'),
    );
    $country = new CountryDefinition('US', ['ein' => $definition]);

    expect(fn () => $country->identifier())->toThrow(\InvalidArgumentException::class);
});

test('rejects inconsistent country definitions and malformed identifier type keys', function (): void {
    $ein = IdentifierType::from('ein');
    $definition = new IdentifierDefinition(
        $ein,
        new PortugalNifNormalizer(),
        new RegexValidator('/^\d+$/'),
    );

    expect(fn () => IdentifierType::from('VAT number'))->toThrow(\InvalidArgumentException::class)
        ->and(fn () => new CountryDefinition('XX', []))->toThrow(\InvalidArgumentException::class)
        ->and(fn () => new CountryDefinition('US', ['wrong' => $definition]))->toThrow(\InvalidArgumentException::class)
        ->and(fn () => new CountryDefinition('US', ['ein' => $definition], 'vat'))->toThrow(\InvalidArgumentException::class);
});

test('validates supporting Portuguese building blocks', function (): void {
    $normalizer = new PortugalNifNormalizer();
    $checksum = new PortugalNifChecksumValidator();
    $regex = new RegexValidator('/^\d+$/');

    expect($normalizer->normalize('999999990'))->toBe('999999990')
        ->and($checksum->validate('invalid'))->toBeFalse()
        ->and($checksum->validate('999999990'))->toBeTrue()
        ->and($checksum->validate('500000000'))->toBeTrue()
        ->and($regex->validate('123'))->toBeTrue()
        ->and($regex->validate('abc'))->toBeFalse()
        ->and(fn () => new RegexValidator('/[/'))->toThrow(\InvalidArgumentException::class);
});
