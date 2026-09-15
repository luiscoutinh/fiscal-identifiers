<?php

declare(strict_types=1);

namespace LuisCoutinho\FiscalIdentifiers;

use LuisCoutinho\FiscalIdentifiers\Configuration\ValidationConfiguration;
use LuisCoutinho\FiscalIdentifiers\Enums\IdentifierType;
use LuisCoutinho\FiscalIdentifiers\Enums\ValidationDecision;
use LuisCoutinho\FiscalIdentifiers\Enums\ValidationStatus;
use LuisCoutinho\FiscalIdentifiers\Registry\CountryRegistry;
use LuisCoutinho\FiscalIdentifiers\Registry\ProviderRegistry;
use LuisCoutinho\FiscalIdentifiers\Results\ExternalVerificationResult;
use LuisCoutinho\FiscalIdentifiers\Results\ValidationResult;
use LuisCoutinho\FiscalIdentifiers\Results\ValidationStepResult;

final readonly class FiscalIdentifierValidator
{
    public function __construct(
        private CountryRegistry $countries,
        private ProviderRegistry $providers,
        private ValidationConfiguration $configuration = new ValidationConfiguration(),
    ) {}

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
                    'external' => new ValidationStepResult(ValidationStatus::NotSupported),
                ],
            );
        }

        $normalized = $definition->normalizer->normalize($value);
        $steps = [
            'normalization' => new ValidationStepResult(ValidationStatus::Passed),
        ];

        if (! $definition->formatValidator->validate($normalized)) {
            $steps['format'] = new ValidationStepResult(ValidationStatus::Failed);

            return new ValidationResult($countryCode, $value, $normalized, ValidationDecision::Rejected, $steps);
        }

        $steps['format'] = new ValidationStepResult(ValidationStatus::Passed);

        if ($definition->checksumValidator !== null) {
            if (! $definition->checksumValidator->validate($normalized)) {
                $steps['checksum'] = new ValidationStepResult(ValidationStatus::Failed);

                return new ValidationResult($countryCode, $value, $normalized, ValidationDecision::Rejected, $steps);
            }

            $steps['checksum'] = new ValidationStepResult(ValidationStatus::Passed);
        } else {
            $steps['checksum'] = new ValidationStepResult(ValidationStatus::NotSupported);
        }

        if ($definition->externalProvider === null) {
            $steps['external'] = new ValidationStepResult(ValidationStatus::NotSupported);
        } elseif (! $this->configuration->isExternalValidationEnabledFor($countryCode)
            || ! $this->configuration->isProviderEnabled($definition->externalProvider)) {
            $steps['external'] = new ValidationStepResult(ValidationStatus::Disabled);
        } else {
            $provider = $this->providers->get($definition->externalProvider);
            $identifier = new FiscalIdentifier($countryCode, $type, $normalized);
            $steps['external'] = $provider === null
                ? new ExternalVerificationResult(ValidationStatus::Unavailable, message: 'External provider is not registered.')
                : $provider->verify($identifier);
        }

        $decision = $steps['external']->status === ValidationStatus::Failed
            ? ValidationDecision::Rejected
            : ValidationDecision::Accepted;

        return new ValidationResult($countryCode, $value, $normalized, $decision, $steps);
    }
}
