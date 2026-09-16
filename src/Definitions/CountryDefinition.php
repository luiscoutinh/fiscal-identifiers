<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Definitions;

use FiscalIdentifiers\IdentifierSubject;
use FiscalIdentifiers\IdentifierType;
use InvalidArgumentException;
use Symfony\Component\Intl\Countries;

final readonly class CountryDefinition
{
    public string $countryCode;

    /** @var array<string, IdentifierDefinition> */
    public array $identifiers;

    public ?IdentifierType $defaultIdentifierType;

    /** @var array<string, IdentifierType> */
    public array $subjectIdentifierTypes;

    /**
     * @param array<string, IdentifierDefinition> $identifiers
     * @param array<string, IdentifierType|string> $subjectIdentifierTypes
     */
    public function __construct(
        string $countryCode,
        array $identifiers,
        IdentifierType|string|null $defaultIdentifierType = null,
        array $subjectIdentifierTypes = [],
    ) {
        $countryCode = strtoupper(trim($countryCode));

        if (!Countries::exists($countryCode)) {
            throw new InvalidArgumentException("Unknown ISO 3166-1 alpha-2 country code: {$countryCode}");
        }

        foreach ($identifiers as $key => $definition) {
            if ($key !== $definition->type->value) {
                throw new InvalidArgumentException("Identifier definition key '{$key}' does not match type '{$definition->type->value}'.");
            }
        }

        $default = is_string($defaultIdentifierType)
            ? IdentifierType::from($defaultIdentifierType)
            : $defaultIdentifierType;

        if ($default !== null && !isset($identifiers[$default->value])) {
            throw new InvalidArgumentException("Default identifier type '{$default->value}' is not defined for {$countryCode}.");
        }

        $subjects = [];
        foreach ($subjectIdentifierTypes as $subject => $type) {
            $subject = IdentifierSubject::from($subject)->value;
            $type = is_string($type) ? IdentifierType::from($type) : $type;

            if (!isset($identifiers[$type->value])) {
                throw new InvalidArgumentException("Identifier type '{$type->value}' mapped from subject '{$subject}' is not defined for {$countryCode}.");
            }

            $subjects[$subject] = $type;
        }

        $this->countryCode = $countryCode;
        $this->identifiers = $identifiers;
        $this->defaultIdentifierType = $default;
        $this->subjectIdentifierTypes = $subjects;
    }

    public function identifier(IdentifierType|string|null $type = null): ?IdentifierDefinition
    {
        if ($type === null) {
            if ($this->defaultIdentifierType === null) {
                throw new InvalidArgumentException("Identifier type is required for {$this->countryCode}.");
            }

            $type = $this->defaultIdentifierType;
        } elseif (is_string($type)) {
            $type = IdentifierType::from($type);
        }

        return $this->identifiers[$type->value] ?? null;
    }

    public function identifierForSubject(IdentifierSubject|string $subject): ?IdentifierDefinition
    {
        $subject = is_string($subject) ? IdentifierSubject::from($subject) : $subject;
        $type = $this->subjectIdentifierTypes[$subject->value] ?? null;

        return $type === null ? null : $this->identifier($type);
    }
}
