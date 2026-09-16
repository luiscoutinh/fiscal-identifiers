<?php

declare(strict_types=1);

use FiscalIdentifiers\Configuration\IdentifierResolutionConfiguration;
use FiscalIdentifiers\Countries\DE\Germany;
use FiscalIdentifiers\Countries\DE\GermanyIdentifierNormalizer;
use FiscalIdentifiers\Enums\ValidationStatus;
use FiscalIdentifiers\FiscalIdentifierValidator;
use FiscalIdentifiers\Registry\CountryRegistry;

function germanyValidator(?IdentifierResolutionConfiguration $configuration = null): FiscalIdentifierValidator
{
    $countries = new CountryRegistry();
    $countries->register(Germany::definition());

    return new FiscalIdentifierValidator(
        $countries,
        $configuration ?? new IdentifierResolutionConfiguration(),
    );
}

test('resolves a German person to the personal tax IdNr', function (): void {
    $result = germanyValidator()->validateFor('DE', ' 12345678910 ', 'person');

    expect($result->normalized)->toBe('12345678910')
        ->and($result->isAccepted())->toBeTrue()
        ->and($result->steps['format']->status)->toBe(ValidationStatus::Passed)
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::NotSupported);
});

test('resolves a German company to the W-IdNr', function (): void {
    $result = germanyValidator()->validateFor(' de ', ' de123456789-00001 ', 'company');

    expect($result->normalized)->toBe('DE123456789-00001')
        ->and($result->isAccepted())->toBeTrue()
        ->and($result->steps['format']->status)->toBe(ValidationStatus::Passed)
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::NotSupported);
});

test('uses company as the configurable default subject for Germany', function (): void {
    $validator = germanyValidator(new IdentifierResolutionConfiguration(defaultSubject: 'company'));
    $result = $validator->validate('DE', 'DE123456789-00001');

    expect($result->isAccepted())->toBeTrue()
        ->and($result->normalized)->toBe('DE123456789-00001');
});

test('keeps German VAT ID and federal tax number as explicit schemes', function (): void {
    $vat = germanyValidator()->validate('DE', ' de 123456789 ', 'ust_idnr');
    $taxNumber = germanyValidator()->validate('DE', '1234567890123', 'steuernummer');

    expect($vat->normalized)->toBe('DE123456789')
        ->and($vat->isAccepted())->toBeTrue()
        ->and($vat->steps['checksum']->status)->toBe(ValidationStatus::NotSupported)
        ->and($taxNumber->isAccepted())->toBeTrue()
        ->and($taxNumber->steps['checksum']->status)->toBe(ValidationStatus::NotSupported);
});

test('does not confuse W-IdNr with USt-IdNr', function (): void {
    $widnrAsVat = germanyValidator()->validate('DE', 'DE123456789-00001', 'ust_idnr');
    $vatAsWidnr = germanyValidator()->validate('DE', 'DE123456789', 'widnr');

    expect($widnrAsVat->isAccepted())->toBeFalse()
        ->and($widnrAsVat->steps['format']->status)->toBe(ValidationStatus::Failed)
        ->and($vatAsWidnr->isAccepted())->toBeFalse()
        ->and($vatAsWidnr->steps['format']->status)->toBe(ValidationStatus::Failed);
});

test('requires identifier context when no default subject is configured for Germany', function (): void {
    expect(fn () => germanyValidator()->validate('DE', 'DE123456789-00001'))
        ->toThrow(\InvalidArgumentException::class);
});

test('rejects malformed German identifiers', function (): void {
    $idnr = germanyValidator()->validate('DE', '1234', 'idnr');
    $widnr = germanyValidator()->validate('DE', 'DE123456789-0001', 'widnr');
    $vat = germanyValidator()->validate('DE', 'DE12345678', 'ust_idnr');
    $taxNumber = germanyValidator()->validate('DE', '12/345/67890', 'steuernummer');

    expect($idnr->steps['format']->status)->toBe(ValidationStatus::Failed)
        ->and($widnr->steps['format']->status)->toBe(ValidationStatus::Failed)
        ->and($vat->steps['format']->status)->toBe(ValidationStatus::Failed)
        ->and($taxNumber->steps['format']->status)->toBe(ValidationStatus::Failed);
});

test('covers German normalization directly', function (): void {
    $normalizer = new GermanyIdentifierNormalizer();

    expect($normalizer->normalize(' de 123 456 789 - 00001 '))->toBe('DE123456789-00001');
});
