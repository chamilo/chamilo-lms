<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use RuntimeException;
use Stringable;

/**
 * Neutralize spreadsheet formulas when serializing values to CSV.
 * Stored values and exports in non-CSV formats are left untouched.
 */
final class CsvFormulaEscaper
{
    public static function escapeCell(mixed $value): mixed
    {
        if (!\is_string($value) && !$value instanceof Stringable) {
            return $value;
        }

        $text = (string) $value;
        if ('' === $text) {
            return $value;
        }

        // Leading tabs and line breaks are dangerous even without a formula.
        $first = $text[0];
        $significant = ltrim($text, " \t\r\n");
        if (\in_array($first, ["\t", "\r", "\n"], true)
            || ('' !== $significant && \in_array($significant[0], ['=', '+', '-', '@'], true))
        ) {
            return "'".$text;
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $row
     *
     * @return array<array-key, mixed>
     */
    public static function escapeRow(array $row, bool $escapeColumnNames = false): array
    {
        $safe = [];
        foreach ($row as $key => $value) {
            $safeKey = $escapeColumnNames ? self::escapeCell($key) : $key;
            $safe[$safeKey] = self::escapeCell($value);
        }

        return $safe;
    }

    /**
     * Convert legacy hand-built delimited text to safely quoted CSV cells.
     * Only use at a CSV download boundary, never for data consumed as HTML.
     */
    public static function escapeDelimitedContent(string $content, string $delimiter = ';'): string
    {
        $input = fopen('php://temp', 'w+');
        $output = fopen('php://temp', 'w+');
        if (false === $input || false === $output) {
            throw new RuntimeException('Unable to open the CSV transformation stream.');
        }

        fwrite($input, $content);
        rewind($input);

        while (false !== ($row = fgetcsv($input, 0, $delimiter, '"', ''))) {
            fputcsv($output, self::escapeRow($row), $delimiter, '"', '');
        }

        rewind($output);
        $result = stream_get_contents($output);
        fclose($input);
        fclose($output);

        if (false === $result) {
            throw new RuntimeException('Unable to read the CSV transformation stream.');
        }

        return $result;
    }
}
