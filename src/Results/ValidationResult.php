<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Results;

use FiscalIdentifiers\Enums\ValidationDecision;

final readonly class ValidationResult
{
    /**
     * @param array<string, ValidationStepResult> $steps
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $countryCode,
        public string $original,
        public string $normalized,
        public ValidationDecision $decision,
        public array $steps,
        public array $metadata = [],
    ) {
    }

    public function isAccepted(): bool
    {
        return $this->decision === ValidationDecision::Accepted;
    }

    public function isSupported(): bool
    {
        return $this->decision !== ValidationDecision::NotSupported;
    }

    public function toConfiguredValue(
        mixed $accepted = true,
        mixed $rejected = false,
        mixed $unsupported = null,
    ): mixed {
        return match ($this->decision) {
            ValidationDecision::Accepted => $accepted,
            ValidationDecision::Rejected => $rejected,
            ValidationDecision::NotSupported => $unsupported,
        };
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'country' => $this->countryCode,
            'original' => $this->original,
            'normalized' => $this->normalized,
            'accepted' => $this->isAccepted(),
            'supported' => $this->isSupported(),
            'decision' => $this->decision->value,
            'steps' => array_map(
                static fn (ValidationStepResult $step): array => [
                    'status' => $step->status->value,
                    'message' => $step->message,
                ],
                $this->steps,
            ),
            'metadata' => $this->metadata,
        ];
    }
}
