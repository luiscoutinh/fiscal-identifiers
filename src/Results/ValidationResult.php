<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Results;

use FiscalIdentifiers\Enums\ValidationDecision;

final readonly class ValidationResult
{
    /** @param array<string, ValidationStepResult> $steps */
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
            'decision' => $this->decision->value,
            'steps' => array_map(
                static fn (ValidationStepResult $step): array => [
                    'status' => $step->status->value,
                    'message' => $step->message,
                ],
                $this->steps,
            ),
        ];
    }
}
