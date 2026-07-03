<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update\Dto;

use Symfony\Component\DependencyInjection\Attribute\Exclude;
#[Exclude]
final readonly class UpdatePackageVerificationResult
{
    /**
     * @param string[] $errors
     * @param string[] $warnings
     * @param array<string, mixed> $details
     */
    public function __construct(
        private bool $valid,
        private array $errors = [],
        private array $warnings = [],
        private array $details = [],
    ) {}

    /**
     * @param array<string, mixed> $details
     */
    public static function success(array $details = [], array $warnings = []): self
    {
        return new self(true, [], $warnings, $details);
    }

    /**
     * @param string[] $errors
     * @param array<string, mixed> $details
     */
    public static function failure(array $errors, array $details = [], array $warnings = []): self
    {
        return new self(false, $errors, $warnings, $details);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return string[]
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @return array{valid: bool, errors: string[], warnings: string[], details: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'details' => $this->details,
        ];
    }
}
