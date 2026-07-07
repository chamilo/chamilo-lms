<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update\Dto;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class UpdatePreflightResult
{
    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[]                                                                                        $errors
     * @param string[]                                                                                        $warnings
     * @param array<string, mixed>                                                                            $details
     */
    public function __construct(
        private bool $valid,
        private array $checks = [],
        private array $errors = [],
        private array $warnings = [],
        private array $details = [],
    ) {}

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[]                                                                                        $errors
     * @param string[]                                                                                        $warnings
     * @param array<string, mixed>                                                                            $details
     */
    public static function create(array $checks, array $errors = [], array $warnings = [], array $details = []): self
    {
        return new self([] === $errors, $checks, $errors, $warnings, $details);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * @return array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}>
     */
    public function getChecks(): array
    {
        return $this->checks;
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
     * @return array{
     *     valid: bool,
     *     checks: array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}>,
     *     errors: string[],
     *     warnings: string[],
     *     details: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'checks' => $this->checks,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'details' => $this->details,
        ];
    }
}
