<?php

declare(strict_types=1);

namespace FiscalIdentifiers;

use FiscalIdentifiers\Enums\IdentifierType;
use FiscalIdentifiers\Enums\ValidationDecision;
use FiscalIdentifiers\Enums\ValidationStatus;
use FiscalIdentifiers\Registry\CountryRegistry;
use FiscalIdentifiers\Results\ValidationResult;
use FiscalIdentifiers\Results\ValidationStepResult;

final readonly class FiscalIdentifierValidator
{
    public function __construct(private CountryRegistry $countries)
    {
    }

    public function validate(string $countryCode, string $value, IdentifierType $type = IdentifierType::Vat): ValidationResult
    {
        $countryCode = strtoupper($countryCode);
        $country = $this->countries->get($countryCode);
        $definition = $country?->identifier($type);

        if ($definition === null) {
            return new ValidationResult(
                $countryCode,
                $value,
                trim($value),
                ValidationDecision::Accepted,
                [
                    'format' => new ValidationStepResult(ValidationStatus::NotSupported),
                    'checksum' => new ValidationStepResult(ValidationStatus::NotSupported),
                ],
            );
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
}
