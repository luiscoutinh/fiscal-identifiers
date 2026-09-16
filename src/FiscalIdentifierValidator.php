<?php

declare(strict_types=1);

namespace FiscalIdentifiers;

use FiscalIdentifiers\Enums\ValidationDecision;
use FiscalIdentifiers\Enums\ValidationStatus;
use FiscalIdentifiers\Registry\CountryRegistry;
use FiscalIdentifiers\Results\ValidationResult;
use FiscalIdentifiers\Results\ValidationStepResult;
use InvalidArgumentException;
use Symfony\Component\Intl\Countries;

final readonly class FiscalIdentifierValidator
{
    public function __construct(private CountryRegistry $countries)
    {
    }

    public function validate(
        string $countryCode,
        string $value,
        IdentifierType|string|null $type = null,
    ): ValidationResult {
        $countryCode = strtoupper(trim($countryCode));

        if (!Countries::exists($countryCode)) {
            throw new InvalidArgumentException("Unknown ISO 3166-1 alpha-2 country code: {$countryCode}");
        }

        $country = $this->countries->get($countryCode);

        if ($country === null) {
            return $this->unsupported($countryCode, $value);
        }

        $definition = $country->identifier($type);

        if ($definition === null) {
            return $this->unsupported($countryCode, $value);
        }

        $normalized = $definition->normalizer->normalize($value);
        $steps = [
            'normalization' => new ValidationStepResult(ValidationStatus::Passed),
        ];

        if (!$definition->formatValidator->validate($normalized)) {
            $steps['format'] = new ValidationStepResult(ValidationStatus::Failed);

            return new ValidationResult($countryCode, $value, $normalized, ValidationDecision::Rejected, $steps);
        }

        $steps['format'] = new ValidationStepResult(ValidationStatus::Passed);

        if ($definition->checksumValidator !== null) {
            if (!$definition->checksumValidator->validate($normalized)) {
                $steps['checksum'] = new ValidationStepResult(ValidationStatus::Failed);

                return new ValidationResult($countryCode, $value, $normalized, ValidationDecision::Rejected, $steps);
            }

            $steps['checksum'] = new ValidationStepResult(ValidationStatus::Passed);
        } else {
            $steps['checksum'] = new ValidationStepResult(ValidationStatus::NotSupported);
        }

        return new ValidationResult(
            $countryCode,
            $value,
            $normalized,
            ValidationDecision::Accepted,
            $steps,
        );
    }

    private function unsupported(string $countryCode, string $value): ValidationResult
    {
        return new ValidationResult(
            $countryCode,
            $value,
            trim($value),
            ValidationDecision::NotSupported,
            [
                'format' => new ValidationStepResult(ValidationStatus::NotSupported),
                'checksum' => new ValidationStepResult(ValidationStatus::NotSupported),
            ],
        );
    }
}
