<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use Stringable;

/**
 * Resolves the username cell of a session user-import CSV row.
 *
 * user_export.php writes the header "UserName"; the session import example
 * documents "username". League\Csv keeps those keys as-is (exact case).
 */
final class SessionUserImportUsernameHelper
{
    /**
     * @param array<array-key, mixed> $row A single CSV row, keyed by header
     */
    public static function fromCsvRow(array $row): ?string
    {
        $fallback = null;

        foreach ($row as $key => $value) {
            if (!\is_string($key)) {
                continue;
            }

            $header = self::stripBom($key);
            if (0 !== strcasecmp($header, 'username')) {
                continue;
            }

            $username = self::normalizeValue($value);
            if (null === $username) {
                continue;
            }

            // The documented lowercase header wins over "UserName" when both are filled.
            if ('username' === $header) {
                return $username;
            }

            $fallback ??= $username;
        }

        return $fallback;
    }

    private static function normalizeValue(mixed $value): ?string
    {
        if (!\is_scalar($value) && !$value instanceof Stringable) {
            return null;
        }

        $username = trim((string) $value);

        return '' === $username ? null : $username;
    }

    private static function stripBom(string $header): string
    {
        return str_starts_with($header, "\xEF\xBB\xBF") ? substr($header, 3) : $header;
    }
}
