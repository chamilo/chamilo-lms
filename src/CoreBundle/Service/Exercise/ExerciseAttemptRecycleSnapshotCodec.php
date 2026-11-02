<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use JsonException;
use RuntimeException;

use const JSON_THROW_ON_ERROR;

/**
 * Serializes raw database values without losing binary UUIDs or legacy encodings.
 */
final class ExerciseAttemptRecycleSnapshotCodec
{
    /**
     * @param array<string, list<array<string, mixed>>> $tables
     */
    public function encode(array $tables): string
    {
        $encoded = [];
        foreach ($tables as $table => $rows) {
            $encoded[$table] = [];
            foreach ($rows as $row) {
                $values = [];
                foreach ($row as $column => $value) {
                    $values[$column] = null === $value ? null : base64_encode((string) $value);
                }
                $encoded[$table][] = $values;
            }
        }

        return json_encode(['version' => 1, 'tables' => $encoded], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, list<array<string, null|string>>>
     */
    public function decode(string $snapshot): array
    {
        try {
            $data = json_decode($snapshot, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('The recycle archive is not valid JSON.', 0, $exception);
        }

        if (!\is_array($data) || 1 !== ($data['version'] ?? null) || !\is_array($data['tables'] ?? null)) {
            throw new RuntimeException('Unsupported recycle archive format.');
        }

        $result = [];
        foreach ($data['tables'] as $table => $rows) {
            if (!\is_string($table) || !\is_array($rows)) {
                throw new RuntimeException('Invalid recycle archive table.');
            }
            $result[$table] = [];
            foreach ($rows as $row) {
                if (!\is_array($row)) {
                    throw new RuntimeException('Invalid recycle archive row.');
                }
                $decoded = [];
                foreach ($row as $column => $value) {
                    if (!\is_string($column) || (null !== $value && !\is_string($value))) {
                        throw new RuntimeException('Invalid recycle archive column.');
                    }
                    $plain = null === $value ? null : base64_decode($value, true);
                    if (false === $plain) {
                        throw new RuntimeException('Invalid recycle archive value.');
                    }
                    $decoded[$column] = $plain;
                }
                $result[$table][] = $decoded;
            }
        }

        return $result;
    }
}
