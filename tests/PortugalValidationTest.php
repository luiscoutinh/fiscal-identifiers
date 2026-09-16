<?php

declare(strict_types=1);

use FiscalIdentifiers\Contracts\LocalValidator;
use FiscalIdentifiers\Contracts\Normalizer;
use FiscalIdentifiers\Countries\PT\Portugal;
use FiscalIdentifiers\Countries\PT\PortugalNifChecksumValidator;
use FiscalIdentifiers\Countries\PT\PortugalVatNormalizer;
use FiscalIdentifiers\Definitions\CountryDefinition;
use FiscalIdentifiers\Definitions\IdentifierDefinition;
use FiscalIdentifiers\Enums\IdentifierType;
use FiscalIdentifiers\Enums\ValidationStatus;
use FiscalIdentifiers\FiscalIdentifierValidator;
use FiscalIdentifiers\Registry\CountryRegistry;
use FiscalIdentifiers\Validation\RegexValidator;

function portugalValidator(): FiscalIdentifierValidator
{
    $countries = new CountryRegistry();
    $countries->register(Portugal::definition());

    return new FiscalIdentifierValidator($countries);
}

test('normalizes and accepts a checksum-consistent Portuguese identifier', function (): void {
    $result = portugalValidator()->validate('pt', ' PT 999.999-990 ');

    expect($result->normalized)->toBe('999999990')
        ->and($result->isAccepted())->toBeTrue()
        ->and($result->steps['normalization']->status)->toBe(ValidationStatus::Passed)
        ->and($result->steps['format']->status)->toBe(ValidationStatus::Passed)
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::Passed)
        ->and($result->toConfiguredValue('valid', 'invalid'))->toBe('valid')
        ->and($result->toConfiguredValue(1, 0))->toBe(1)
        ->and($result->toArray()['decision'])->toBe('accepted')
        ->and($result->toArray())->not->toHaveKey('verified')
        ->and($result->toArray()['steps'])->not->toHaveKey('external');
});

test('rejects invalid format and checksum', function (): void {
    $format = portugalValidator()->validate('PT', 'abc');
    $checksum = portugalValidator()->validate('PT', '999999991');

    expect($format->isAccepted())->toBeFalse()
        ->and($format->toConfiguredValue('valid', 'invalid'))->toBe('invalid')
        ->and($format->steps['format']->status)->toBe(ValidationStatus::Failed)
        ->and($checksum->isAccepted())->toBeFalse()
        ->and($checksum->steps['checksum']->status)->toBe(ValidationStatus::Failed);
});

test('represents unsupported validation without pretending that checks ran', function (): void {
    $validator = new FiscalIdentifierValidator(new CountryRegistry());
    $result = $validator->validate('AF', ' 123 ');

    expect($result->isAccepted())->toBeTrue()
        ->and($result->normalized)->toBe('123')
        ->and($result->steps['format']->status)->toBe(ValidationStatus::NotSupported)
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::NotSupported);
});

test('supports definitions without checksum validation', function (): void {
    $countries = new CountryRegistry();
    $countries->register(new CountryDefinition('US', [
        IdentifierType::BusinessTax->value => new IdentifierDefinition(
            IdentifierType::BusinessTax,
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
    ]));

    $result = (new FiscalIdentifierValidator($countries))
        ->validate('us', ' 123 ', IdentifierType::BusinessTax);

    expect($result->isAccepted())->toBeTrue()
        ->and($result->steps['format']->status)->toBe(ValidationStatus::Passed)
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::NotSupported);
});

test('validates supporting building blocks and invalid country definitions', function (): void {
    $normalizer = new PortugalVatNormalizer();
    $checksum = new PortugalNifChecksumValidator();
    $regex = new RegexValidator('/^\d+$/');

    expect($normalizer->normalize('999999990'))->toBe('999999990')
        ->and($checksum->validate('invalid'))->toBeFalse()
        ->and($checksum->validate('999999990'))->toBeTrue()
        ->and($regex->validate('123'))->toBeTrue()
        ->and($regex->validate('abc'))->toBeFalse()
        ->and(fn () => new RegexValidator('/[/'))->toThrow(\InvalidArgumentException::class)
        ->and(fn () => new CountryDefinition('XX', []))->toThrow(\InvalidArgumentException::class);
});
