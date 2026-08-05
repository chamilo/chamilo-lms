<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

use const FILE_APPEND;
use const FILE_IGNORE_NEW_LINES;
use const FILE_SKIP_EMPTY_LINES;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const LOCK_EX;
use const PHP_EOL;

final readonly class UpdateOperationLogger
{
    private const string OPERATION_ID_PATTERN = '/^[A-Za-z0-9._-]{8,80}$/';

    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private string $projectDir,
    ) {}

    public function create(?string $operationId = null): string
    {
        $operationId = null === $operationId || '' === trim($operationId)
            ? gmdate('YmdHis').'-'.bin2hex(random_bytes(6))
            : trim($operationId);

        $this->assertValidOperationId($operationId);
        $this->ensureDirectory($this->getOperationDirectory());

        $path = $this->getOperationPath($operationId);
        if (!is_file($path)) {
            $this->append($operationId, 'info', 'operation', 'Update operation log created.');
        }

        return $operationId;
    }

    /**
     * @param array<string, mixed> $details
     */
    public function append(string $operationId, string $level, string $step, string $message, array $details = []): void
    {
        $this->assertValidOperationId($operationId);
        $this->ensureDirectory($this->getOperationDirectory());

        $event = [
            'time' => gmdate('c'),
            'level' => $this->normalizeLevel($level),
            'step' => $this->normalizeStep($step),
            'message' => $message,
        ];

        if ([] !== $details) {
            $event['details'] = $this->sanitizeDetails($details);
        }

        $encoded = json_encode($event, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (false === file_put_contents($this->getOperationPath($operationId), $encoded.PHP_EOL, FILE_APPEND | LOCK_EX)) {
            throw new RuntimeException('Unable to write update operation log.');
        }
    }

    /**
     * @return array{operationId: string, exists: bool, events: array<int, array<string, mixed>>, completed: bool}
     */
    public function read(string $operationId): array
    {
        $this->assertValidOperationId($operationId);

        $path = $this->getOperationPath($operationId);
        if (!is_file($path)) {
            return [
                'operationId' => $operationId,
                'exists' => false,
                'events' => [],
                'completed' => false,
            ];
        }

        $events = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            try {
                $event = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                continue;
            }

            if (\is_array($event)) {
                $events[] = $event;
            }
        }

        $lastEvent = [] !== $events ? $events[array_key_last($events)] : [];
        $lastStep = \is_array($lastEvent) ? (string) ($lastEvent['step'] ?? '') : '';
        $lastLevel = \is_array($lastEvent) ? (string) ($lastEvent['level'] ?? '') : '';

        return [
            'operationId' => $operationId,
            'exists' => true,
            'events' => $events,
            'completed' => \in_array($lastStep, ['done', 'failed'], true) || \in_array($lastLevel, ['success', 'error'], true),
        ];
    }

    public function safeAppend(string $operationId, string $level, string $step, string $message): void
    {
        try {
            $this->append($operationId, $level, $step, $message);
        } catch (Throwable) {
        }
    }

    private function getOperationDirectory(): string
    {
        return $this->projectDir.'/var/update/operations';
    }

    private function getOperationPath(string $operationId): string
    {
        return $this->getOperationDirectory().'/'.$operationId.'.jsonl';
    }

    private function assertValidOperationId(string $operationId): void
    {
        if (1 !== preg_match(self::OPERATION_ID_PATTERN, $operationId)) {
            throw new InvalidArgumentException('Invalid update operation id.');
        }
    }

    private function normalizeLevel(string $level): string
    {
        $level = strtolower(trim($level));

        return \in_array($level, ['info', 'warning', 'error', 'success'], true) ? $level : 'info';
    }

    private function normalizeStep(string $step): string
    {
        $step = strtolower(trim($step));

        return preg_replace('/[^a-z0-9_-]/', '_', $step) ?: 'update';
    }

    /**
     * @param array<string, mixed> $details
     *
     * @return array<string, mixed>
     */
    private function sanitizeDetails(array $details): array
    {
        unset($details['trustedPublicKey'], $details['trusted_public_key'], $details['password'], $details['token']);

        return $details;
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            if (!is_writable($directory)) {
                throw new RuntimeException('Directory is not writable: '.$directory);
            }

            return;
        }

        if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create directory: '.$directory);
        }
    }
}
