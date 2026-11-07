<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Admin;

use InvalidArgumentException;

/**
 * Normalizes the legacy course CSV format before any database writes occur.
 */
final class CourseCsvImportValidator
{
    private const array REQUIRED_COLUMNS = ['Code', 'Title', 'CourseCategory'];
    private const array OPTIONAL_COLUMNS = ['CourseCategoryName', 'Teacher', 'Language'];

    /**
     * @param array<int|string, array<string, mixed>> $records
     *
     * @return list<array{Code: string, Title: string, CourseCategory: string, CourseCategoryName: string, Teacher: string, Language: string}>
     */
    public function normalizeRows(array $records): array
    {
        if ([] === $records) {
            throw new InvalidArgumentException('The CSV file contains no courses.');
        }

        $normalized = [];
        $headersChecked = false;
        $seenCodes = [];
        $line = 1;

        foreach ($records as $record) {
            ++$line;
            if (!\is_array($record)) {
                throw new InvalidArgumentException("Invalid CSV record on line {$line}.");
            }

            $row = [];
            foreach ($record as $name => $value) {
                $key = trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $name));
                if (isset($row[$key])) {
                    throw new InvalidArgumentException("Duplicate CSV column '{$key}'.");
                }
                if (!\is_scalar($value) && null !== $value) {
                    throw new InvalidArgumentException("Invalid CSV value on line {$line}.");
                }
                $row[$key] = trim((string) $value);
            }

            if (!$headersChecked) {
                foreach (self::REQUIRED_COLUMNS as $column) {
                    if (!\array_key_exists($column, $row)) {
                        throw new InvalidArgumentException("Missing required CSV column: {$column}.");
                    }
                }
                $headersChecked = true;
            }

            if (0 === \count(array_filter($row, static fn (string $value): bool => '' !== $value))) {
                continue;
            }

            foreach (self::REQUIRED_COLUMNS as $column) {
                if ('' === ($row[$column] ?? '')) {
                    throw new InvalidArgumentException("Line {$line}: {$column} is required.");
                }
            }

            $codeKey = strtoupper($row['Code']);
            if (isset($seenCodes[$codeKey])) {
                throw new InvalidArgumentException("Line {$line}: duplicate course code '{$row['Code']}'.");
            }
            $seenCodes[$codeKey] = true;

            $normalizedRow = [];
            foreach (self::REQUIRED_COLUMNS as $column) {
                $normalizedRow[$column] = $row[$column];
            }
            foreach (self::OPTIONAL_COLUMNS as $column) {
                $normalizedRow[$column] = $row[$column] ?? '';
            }
            $normalized[] = $normalizedRow;
        }

        if ([] === $normalized) {
            throw new InvalidArgumentException('The CSV file contains no courses.');
        }

        return $normalized;
    }
}
