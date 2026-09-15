<?php

declare(strict_types=1);

use FiscalIdentifiers\Configuration\ValidationConfiguration;
use FiscalIdentifiers\Contracts\ExternalVerificationProvider;
use FiscalIdentifiers\Contracts\LocalValidator;
use FiscalIdentifiers\Contracts\Normalizer;
use FiscalIdentifiers\Countries\PT\Portugal;
use FiscalIdentifiers\Countries\PT\PortugalNifChecksumValidator;
use FiscalIdentifiers\Countries\PT\PortugalVatNormalizer;
use FiscalIdentifiers\Definitions\CountryDefinition;
use FiscalIdentifiers\Definitions\IdentifierDefinition;
use FiscalIdentifiers\Enums\IdentifierType;
use FiscalIdentifiers\Enums\ValidationStatus;
use FiscalIdentifiers\FiscalIdentifier;
use FiscalIdentifiers\FiscalIdentifierValidator;
use FiscalIdentifiers\Registry\CountryRegistry;
use FiscalIdentifiers\Registry\ProviderRegistry;
use FiscalIdentifiers\Results\ExternalVerificationResult;
use FiscalIdentifiers\Validation\RegexValidator;
use InvalidArgumentException;

function portugalValidator(?ValidationConfiguration $configuration = null, ?ProviderRegistry $providers = null): FiscalIdentifierValidator
{
    $countries = new CountryRegistry();
    $countries->register(Portugal::definition());

    return new FiscalIdentifierValidator(
        $countries,
        $providers ?? new ProviderRegistry(),
        $configuration ?? new ValidationConfiguration(),
    );
}

test('normalizes and accepts a structurally valid Portuguese fiscal identifier when VIES is unavailable', function (): void {
    $result = portugalValidator()->validate('pt', ' PT 501.964-843 ');

    expect($result->normalized)->toBe('501964843')
        ->and($result->isAccepted())->toBeTrue()
        ->and($result->isVerified())->toBeFalse()
        ->and($result->steps['external']->status)->toBe(ValidationStatus::Unavailable)
        ->and($result->toConfiguredValue('valid', 'invalid'))->toBe('valid')
        ->and($result->toConfiguredValue(1, 0))->toBe(1)
        ->and($result->toArray()['decision'])->toBe('accepted');
});

test('rejects invalid format and checksum', function (): void {
    $format = portugalValidator()->validate('PT', 'abc');
    $checksum = portugalValidator()->validate('PT', '501964844');

    expect($format->isAccepted())->toBeFalse()
        ->and($format->toConfiguredValue('valid', 'invalid'))->toBe('invalid')
        ->and($checksum->isAccepted())->toBeFalse()
        ->and($checksum->steps['checksum']->status)->toBe(ValidationStatus::Failed);
});

test('supports country and provider external validation overrides', function (): void {
    $countryDisabled = portugalValidator(new ValidationConfiguration(countryExternalValidation: ['PT' => false]))
        ->validate('PT', '501964843');
    $providerDisabled = portugalValidator(new ValidationConfiguration(providers: ['vies' => false]))
        ->validate('PT', '501964843');

    expect($countryDisabled->steps['external']->status)->toBe(ValidationStatus::Disabled)
        ->and($providerDisabled->steps['external']->status)->toBe(ValidationStatus::Disabled);
});

test('uses registered external providers and keeps verification distinct from acceptance', function (): void {
    $providers = new ProviderRegistry();
    $providers->register(new class implements ExternalVerificationProvider {
        public function key(): string
        {
            return 'vies';
        }

        public function verify(FiscalIdentifier $identifier): ExternalVerificationResult
        {
            return new ExternalVerificationResult(
                ValidationStatus::Passed,
                ['country' => $identifier->countryCode],
                'verified',
            );
        }
    });

    $result = portugalValidator(providers: $providers)->validate('PT', '501964843');

    expect($result->isAccepted())->toBeTrue()
        ->and($result->isVerified())->toBeTrue()
        ->and($result->toArray()['steps']['external']['message'])->toBe('verified');

    $failedProviders = new ProviderRegistry();
    $failedProviders->register(new class implements ExternalVerificationProvider {
        public function key(): string
        {
            return 'vies';
        }

        public function verify(FiscalIdentifier $identifier): ExternalVerificationResult
        {
            return new ExternalVerificationResult(ValidationStatus::Failed);
        }
    });

    expect(portugalValidator(providers: $failedProviders)->validate('PT', '501964843')->isAccepted())->toBeFalse();
});

test('represents unsupported validation without rejecting the identifier', function (): void {
    $validator = new FiscalIdentifierValidator(new CountryRegistry(), new ProviderRegistry());
    $result = $validator->validate('AF', ' 123 ');

    expect($result->isAccepted())->toBeTrue()
        ->and($result->normalized)->toBe('123')
        ->and($result->steps['format']->status)->toBe(ValidationStatus::NotSupported);
});

test('supports definitions without checksum or external providers', function (): void {
    $countries = new CountryRegistry();
    $countries->register(new CountryDefinition('US', [
        IdentifierType::BusinessTax->value => new IdentifierDefinition(
            IdentifierType::BusinessTax,
            new class implements Normalizer {
                public function normalize(string $value): string
                {
                    return trim($value);
                }
            },
            new class implements LocalValidator {
                public function validate(string $value): bool
                {
                    return $value === '123';
                }
            },
        ),
    ]));

    $result = (new FiscalIdentifierValidator($countries, new ProviderRegistry()))
        ->validate('us', ' 123 ', IdentifierType::BusinessTax);

    expect($result->steps['checksum']->status)->toBe(ValidationStatus::NotSupported)
        ->and($result->steps['external']->status)->toBe(ValidationStatus::NotSupported);
});

test('validates supporting building blocks and invalid country definitions', function (): void {
    $normalizer = new PortugalVatNormalizer();
    $checksum = new PortugalNifChecksumValidator();
    $regex = new RegexValidator('/^\d+$/');

    expect($normalizer->normalize('501964843'))->toBe('501964843')
        ->and($checksum->validate('invalid'))->toBeFalse()
        ->and($checksum->validate('501964843'))->toBeTrue()
        ->and($regex->validate('123'))->toBeTrue()
        ->and($regex->validate('abc'))->toBeFalse()
        ->and(fn () => new RegexValidator('/[/'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new CountryDefinition('XX', []))->toThrow(InvalidArgumentException::class);
});
