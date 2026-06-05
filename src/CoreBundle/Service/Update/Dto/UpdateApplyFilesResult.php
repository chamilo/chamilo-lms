<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update\Dto;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class UpdateApplyFilesResult
{
    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[] $errors
     * @param string[] $warnings
     * @param array<string, mixed> $details
     */
    public function __construct(
        private bool $valid,
        private ?string $stagingPath = null,
        private ?string $backupPath = null,
        private ?string $lockPath = null,
        private ?string $auditPath = null,
        private array $checks = [],
        private array $errors = [],
        private array $warnings = [],
        private array $details = [],
    ) {}

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[] $warnings
     * @param array<string, mixed> $details
     */
    public static function success(
        string $stagingPath,
        string $backupPath,
        string $lockPath,
        string $auditPath,
        array $checks = [],
        array $warnings = [],
        array $details = [],
    ): self {
        return new self(true, $stagingPath, $backupPath, $lockPath, $auditPath, $checks, [], $warnings, $details);
    }

    /**
     * @param string[] $errors
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[] $warnings
     * @param array<string, mixed> $details
     */
    public static function failure(array $errors, array $checks = [], array $warnings = [], array $details = []): self
    {
        return new self(false, null, null, null, null, $checks, $errors, $warnings, $details);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getStagingPath(): ?string
    {
        return $this->stagingPath;
    }

    public function getBackupPath(): ?string
    {
        return $this->backupPath;
    }

    public function getLockPath(): ?string
    {
        return $this->lockPath;
    }

    public function getAuditPath(): ?string
    {
        return $this->auditPath;
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
     *     backupPath: ?string,
     *     lockPath: ?string,
     *     auditPath: ?string,
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
            'backupPath' => $this->backupPath,
            'lockPath' => $this->lockPath,
            'auditPath' => $this->auditPath,
            'checks' => $this->checks,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'details' => $this->details,
        ];
    }
}
