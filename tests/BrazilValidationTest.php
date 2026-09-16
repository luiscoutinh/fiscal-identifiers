<?php

declare(strict_types=1);

use FiscalIdentifiers\Countries\BR\Brazil;
use FiscalIdentifiers\Countries\BR\BrazilCnpjChecksumValidator;
use FiscalIdentifiers\Countries\BR\BrazilCnpjNormalizer;
use FiscalIdentifiers\Countries\BR\BrazilCpfChecksumValidator;
use FiscalIdentifiers\Countries\BR\BrazilCpfNormalizer;
use FiscalIdentifiers\Enums\ValidationStatus;
use FiscalIdentifiers\FiscalIdentifierValidator;
use FiscalIdentifiers\Registry\CountryRegistry;

function brazilValidator(): FiscalIdentifierValidator
{
    $countries = new CountryRegistry();
    $countries->register(Brazil::definition());

    return new FiscalIdentifierValidator($countries);
}

test('requires an explicit Brazilian identifier type', function (): void {
    expect(fn () => brazilValidator()->validate('BR', '99999999050'))
        ->toThrow(\InvalidArgumentException::class);
});

test('normalizes and validates a checksum-consistent CPF', function (): void {
    $result = brazilValidator()->validate(' br ', ' 999.999.990-50 ', 'cpf');

    expect($result->normalized)->toBe('99999999050')
        ->and($result->isAccepted())->toBeTrue()
        ->and($result->steps['format']->status)->toBe(ValidationStatus::Passed)
        ->and($result->steps['checksum']->status)->toBe(ValidationStatus::Passed);
});

test('rejects malformed, checksum-invalid and repeated CPFs', function (): void {
    $format = brazilValidator()->validate('BR', 'abc', 'cpf');
    $checksum = brazilValidator()->validate('BR', '99999999051', 'cpf');
    $repeated = brazilValidator()->validate('BR', '111.111.111-11', 'cpf');

    expect($format->isAccepted())->toBeFalse()
        ->and($format->steps['format']->status)->toBe(ValidationStatus::Failed)
        ->and($checksum->steps['checksum']->status)->toBe(ValidationStatus::Failed)
        ->and($repeated->steps['checksum']->status)->toBe(ValidationStatus::Failed);
});

test('validates legacy numeric and current alphanumeric CNPJ forms', function (): void {
    $numeric = brazilValidator()->validate('BR', '99.999.999/0001-91', 'cnpj');
    $alpha = brazilValidator()->validate('BR', '12.abc.345/01de-35', 'cnpj');

    expect($numeric->normalized)->toBe('99999999000191')
        ->and($numeric->isAccepted())->toBeTrue()
        ->and($alpha->normalized)->toBe('12ABC34501DE35')
        ->and($alpha->isAccepted())->toBeTrue();
});

test('rejects malformed, checksum-invalid and repeated CNPJs', function (): void {
    $format = brazilValidator()->validate('BR', '12ABC34501DEAA', 'cnpj');
    $checksum = brazilValidator()->validate('BR', '12ABC34501DE36', 'cnpj');
    $repeated = brazilValidator()->validate('BR', '00000000000000', 'cnpj');

    expect($format->steps['format']->status)->toBe(ValidationStatus::Failed)
        ->and($checksum->steps['checksum']->status)->toBe(ValidationStatus::Failed)
        ->and($repeated->steps['checksum']->status)->toBe(ValidationStatus::Failed);
});

test('validates Brazilian building blocks directly', function (): void {
    $cpfNormalizer = new BrazilCpfNormalizer();
    $cnpjNormalizer = new BrazilCnpjNormalizer();
    $cpfChecksum = new BrazilCpfChecksumValidator();
    $cnpjChecksum = new BrazilCnpjChecksumValidator();

    expect($cpfNormalizer->normalize('999.999.990-50'))->toBe('99999999050')
        ->and($cnpjNormalizer->normalize('12.abc.345/01de-35'))->toBe('12ABC34501DE35')
        ->and($cpfChecksum->validate('invalid'))->toBeFalse()
        ->and($cpfChecksum->validate('99999999050'))->toBeTrue()
        ->and($cnpjChecksum->validate('invalid'))->toBeFalse()
        ->and($cnpjChecksum->validate('12ABC34501DE35'))->toBeTrue();
});
