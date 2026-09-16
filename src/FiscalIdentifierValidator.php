<?php

declare(strict_types=1);

namespace FiscalIdentifiers;

use FiscalIdentifiers\Configuration\IdentifierResolutionConfiguration;
use FiscalIdentifiers\Definitions\CountryDefinition;
use FiscalIdentifiers\Definitions\IdentifierDefinition;
use FiscalIdentifiers\Enums\ValidationDecision;
use FiscalIdentifiers\Enums\ValidationStatus;
use FiscalIdentifiers\Registry\CountryRegistry;
use FiscalIdentifiers\Results\ValidationResult;
use FiscalIdentifiers\Results\ValidationStepResult;
use InvalidArgumentException;
use Symfony\Component\Intl\Countries;

final readonly class FiscalIdentifierValidator
{
    public function __construct(
        private CountryRegistry $countries,
        private IdentifierResolutionConfiguration $configuration = new IdentifierResolutionConfiguration(),
    ) {
    }

    public function validate(
        string $countryCode,
        string $value,
        IdentifierType|string|null $type = null,
    ): ValidationResult {
        [$countryCode, $country] = $this->country($countryCode);

        if ($country === null) {
            return $this->unsupported($countryCode, $value);
        }

        $definition = $type === null
            ? $this->defaultDefinition($country)
            : $country->identifier($type);

        return $definition === null
            ? $this->unsupported($countryCode, $value)
            : $this->validateDefinition($countryCode, $value, $definition);
    }

    public function validateFor(
        string $countryCode,
        string $value,
        IdentifierSubject|string $subject,
        IdentifierCategory|string|null $category = null,
    ): ValidationResult {
        [$countryCode, $country] = $this->country($countryCode);

        if ($country === null) {
            return $this->unsupported($countryCode, $value);
        }

        $definition = $country->identifierForSubject($subject);
        $category = is_string($category) ? IdentifierCategory::from($category) : $category;

        return $definition === null
            ? $this->unsupported($countryCode, $value)
            : $this->validateDefinition($countryCode, $value, $definition, $category);
    }

    /** @return array{string, CountryDefinition|null} */
    private function country(string $countryCode): array
    {
        $countryCode = strtoupper(trim($countryCode));

        if (!Countries::exists($countryCode)) {
            throw new InvalidArgumentException("Unknown ISO 3166-1 alpha-2 country code: {$countryCode}");
        }

        return [$countryCode, $this->countries->get($countryCode)];
    }

    private function defaultDefinition(CountryDefinition $country): ?IdentifierDefinition
    {
        if ($this->configuration->defaultSubject !== null) {
            $definition = $country->identifierForSubject($this->configuration->defaultSubject);

            if ($definition !== null) {
                return $definition;
            }
        }

        return $country->identifier();
    }

    private function validateDefinition(
        string $countryCode,
        string $value,
        IdentifierDefinition $definition,
        ?IdentifierCategory $requiredCategory = null,
    ): ValidationResult {
        $normalized = $definition->normalizer->normalize($value);
        $steps = [
            'normalization' => new ValidationStepResult(ValidationStatus::Passed),
        ];
        $metadata = [];

        if (!$definition->formatValidator->validate($normalized)) {
            $steps['format'] = new ValidationStepResult(ValidationStatus::Failed);

            return new ValidationResult($countryCode, $value, $normalized, ValidationDecision::Rejected, $steps);
        }

        $steps['format'] = new ValidationStepResult(ValidationStatus::Passed);

        if ($definition->categoryResolver !== null) {
            $resolvedCategory = $definition->categoryResolver->resolve($normalized);

            if ($resolvedCategory !== null) {
                $metadata['category'] = $resolvedCategory->value;
            }

            if ($requiredCategory !== null) {
                if ($resolvedCategory === null || !$resolvedCategory->equals($requiredCategory)) {
                    $steps['category'] = new ValidationStepResult(ValidationStatus::Failed);

                    return new ValidationResult(
                        $countryCode,
                        $value,
                        $normalized,
                        ValidationDecision::Rejected,
                        $steps,
                        $metadata,
                    );
                }

                $steps['category'] = new ValidationStepResult(ValidationStatus::Passed);
            }
        } elseif ($requiredCategory !== null) {
            $steps['category'] = new ValidationStepResult(ValidationStatus::NotSupported);

            return new ValidationResult(
                $countryCode,
                $value,
                $normalized,
                ValidationDecision::NotSupported,
                $steps,
                $metadata,
            );
        }

        if ($definition->checksumValidator !== null) {
            if (!$definition->checksumValidator->validate($normalized)) {
                $steps['checksum'] = new ValidationStepResult(ValidationStatus::Failed);

                return new ValidationResult(
                    $countryCode,
                    $value,
                    $normalized,
                    ValidationDecision::Rejected,
                    $steps,
                    $metadata,
                );
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
            $metadata,
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
