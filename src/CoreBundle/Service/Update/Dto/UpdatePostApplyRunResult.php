<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update\Dto;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class UpdatePostApplyRunResult
{
    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}>                                               $checks
     * @param array<int, array{key: string, title: string, command: string, status: string, exitCode?: int|null, durationSeconds?: float, advanced?: bool}> $actions
     * @param string[]                                                                                                                                      $errors
     * @param string[]                                                                                                                                      $warnings
     * @param array<string, mixed>                                                                                                                          $details
     */
    public function __construct(
        private bool $valid,
        private ?string $stagingPath = null,
        private ?string $metadataPath = null,
        private ?string $operationId = null,
        private array $checks = [],
        private array $actions = [],
        private array $errors = [],
        private array $warnings = [],
        private array $details = [],
    ) {}

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}>                                               $checks
     * @param array<int, array{key: string, title: string, command: string, status: string, exitCode?: int|null, durationSeconds?: float, advanced?: bool}> $actions
     * @param string[]                                                                                                                                      $warnings
     * @param array<string, mixed>                                                                                                                          $details
     */
    public static function success(
        string $stagingPath,
        string $metadataPath,
        string $operationId,
        array $checks = [],
        array $actions = [],
        array $warnings = [],
        array $details = []
    ): self {
        return new self(true, $stagingPath, $metadataPath, $operationId, $checks, $actions, [], $warnings, $details);
    }

    /**
     * @param string[]                                                                                                                                      $errors
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}>                                               $checks
     * @param array<int, array{key: string, title: string, command: string, status: string, exitCode?: int|null, durationSeconds?: float, advanced?: bool}> $actions
     * @param string[]                                                                                                                                      $warnings
     * @param array<string, mixed>                                                                                                                          $details
     */
    public static function failure(
        array $errors,
        ?string $stagingPath = null,
        ?string $metadataPath = null,
        ?string $operationId = null,
        array $checks = [],
        array $actions = [],
        array $warnings = [],
        array $details = []
    ): self {
        return new self(false, $stagingPath, $metadataPath, $operationId, $checks, $actions, $errors, $warnings, $details);
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

    public function getOperationId(): ?string
    {
        return $this->operationId;
    }

    /**
     * @return array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}>
     */
    public function getChecks(): array
    {
        return $this->checks;
    }

    /**
     * @return array<int, array{key: string, title: string, command: string, status: string, exitCode?: int|null, durationSeconds?: float, advanced?: bool}>
     */
    public function getActions(): array
    {
        return $this->actions;
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
     *     metadataPath: ?string,
     *     operationId: ?string,
     *     checks: array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}>,
     *     actions: array<int, array{key: string, title: string, command: string, status: string, exitCode?: int|null, durationSeconds?: float, advanced?: bool}>,
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
            'metadataPath' => $this->metadataPath,
            'operationId' => $this->operationId,
            'checks' => $this->checks,
            'actions' => $this->actions,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'details' => $this->details,
        ];
    }
}
