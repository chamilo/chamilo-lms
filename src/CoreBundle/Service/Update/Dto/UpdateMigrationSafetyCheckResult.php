<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update\Dto;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class UpdateMigrationSafetyCheckResult
{
    /**
     * @param array<int, array{class: string, path: string, description: string, namespace?: string}>         $migrations
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[]                                                                                        $errors
     * @param string[]                                                                                        $warnings
     * @param array<string, mixed>                                                                            $details
     */
    public function __construct(
        private bool $valid,
        private ?string $stagingPath = null,
        private ?string $metadataPath = null,
        private array $migrations = [],
        private array $checks = [],
        private array $errors = [],
        private array $warnings = [],
        private array $details = [],
        private ?string $dryRunCommand = null,
        private ?int $dryRunExitCode = null,
        private string $dryRunOutput = '',
    ) {}

    /**
     * @param array<int, array{class: string, path: string, description: string, namespace?: string}>         $migrations
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[]                                                                                        $warnings
     * @param array<string, mixed>                                                                            $details
     */
    public static function success(
        string $stagingPath,
        string $metadataPath,
        array $migrations,
        array $checks = [],
        array $warnings = [],
        array $details = [],
        ?string $dryRunCommand = null,
        ?int $dryRunExitCode = null,
        string $dryRunOutput = ''
    ): self {
        return new self(true, $stagingPath, $metadataPath, $migrations, $checks, [], $warnings, $details, $dryRunCommand, $dryRunExitCode, $dryRunOutput);
    }

    /**
     * @param string[]                                                                                        $errors
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[]                                                                                        $warnings
     * @param array<string, mixed>                                                                            $details
     */
    public static function failure(
        array $errors,
        ?string $stagingPath = null,
        ?string $metadataPath = null,
        array $checks = [],
        array $warnings = [],
        array $details = [],
        ?string $dryRunCommand = null,
        ?int $dryRunExitCode = null,
        string $dryRunOutput = ''
    ): self {
        return new self(false, $stagingPath, $metadataPath, [], $checks, $errors, $warnings, $details, $dryRunCommand, $dryRunExitCode, $dryRunOutput);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getStagingPath(): ?string
    {
        return $this->stagingPath;
    }

    public function getMetadataPath(): ?string
    {
        return $this->metadataPath;
    }

    /**
     * @return array<int, array{class: string, path: string, description: string, namespace?: string}>
     */
    public function getMigrations(): array
    {
        return $this->migrations;
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

    public function getDryRunCommand(): ?string
    {
        return $this->dryRunCommand;
    }

    public function getDryRunExitCode(): ?int
    {
        return $this->dryRunExitCode;
    }

    public function getDryRunOutput(): string
    {
        return $this->dryRunOutput;
    }

    /**
     * @return array{
     *     valid: bool,
     *     stagingPath: ?string,
     *     metadataPath: ?string,
     *     migrations: array<int, array{class: string, path: string, description: string, namespace?: string}>,
     *     checks: array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}>,
     *     errors: string[],
     *     warnings: string[],
     *     details: array<string, mixed>,
     *     dryRunCommand: ?string,
     *     dryRunExitCode: ?int,
     *     dryRunOutput: string
     * }
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'stagingPath' => $this->stagingPath,
            'metadataPath' => $this->metadataPath,
            'migrations' => $this->migrations,
            'checks' => $this->checks,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'details' => $this->details,
            'dryRunCommand' => $this->dryRunCommand,
            'dryRunExitCode' => $this->dryRunExitCode,
            'dryRunOutput' => $this->dryRunOutput,
        ];
    }
}
