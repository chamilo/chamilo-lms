<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update\Dto;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class UpdateStagingResult
{
    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[]                                                                                        $errors
     * @param string[]                                                                                        $warnings
     * @param array<string, mixed>                                                                            $details
     */
    public function __construct(
        private bool $valid,
        private ?string $stagingPath = null,
        private ?string $applicationPath = null,
        private array $checks = [],
        private array $errors = [],
        private array $warnings = [],
        private array $details = [],
    ) {}

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[]                                                                                        $warnings
     * @param array<string, mixed>                                                                            $details
     */
    public static function success(string $stagingPath, string $applicationPath, array $checks = [], array $warnings = [], array $details = []): self
    {
        return new self(true, $stagingPath, $applicationPath, $checks, [], $warnings, $details);
    }

    /**
     * @param string[]                                                                                        $errors
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[]                                                                                        $warnings
     * @param array<string, mixed>                                                                            $details
     */
    public static function failure(array $errors, array $checks = [], array $warnings = [], array $details = []): self
    {
        return new self(false, null, null, $checks, $errors, $warnings, $details);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getStagingPath(): ?string
    {
        return $this->stagingPath;
    }

    public function getApplicationPath(): ?string
    {
        return $this->applicationPath;
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
     *     stagingPath: ?string,
     *     applicationPath: ?string,
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
            'stagingPath' => $this->stagingPath,
            'applicationPath' => $this->applicationPath,
            'checks' => $this->checks,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'details' => $this->details,
        ];
    }
}
