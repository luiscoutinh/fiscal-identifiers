<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Results;

use FiscalIdentifiers\Enums\ValidationDecision;
use FiscalIdentifiers\Enums\ValidationStatus;

final readonly class ValidationResult
{
    /** @param array<string, ValidationStepResult|ExternalVerificationResult> $steps */
    public function __construct(
        public string $countryCode,
        public string $original,
        public string $normalized,
        public ValidationDecision $decision,
        public array $steps,
    ) {
    }

    public function isAccepted(): bool
    {
        return $this->decision === ValidationDecision::Accepted;
    }

    public function isVerified(): bool
    {
        return isset($this->steps['external'])
            && $this->steps['external']->status === ValidationStatus::Passed;
    }

    public function toConfiguredValue(mixed $accepted = true, mixed $rejected = false): mixed
    {
        return $this->isAccepted() ? $accepted : $rejected;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'country' => $this->countryCode,
            'original' => $this->original,
            'normalized' => $this->normalized,
            'accepted' => $this->isAccepted(),
            'verified' => $this->isVerified(),
            'decision' => $this->decision->value,
            'steps' => array_map(
                static fn (ValidationStepResult|ExternalVerificationResult $step): array => [
                    'status' => $step->status->value,
                    'message' => $step->message,
                ],
                $this->steps,
            ),
        ];
    }
}
