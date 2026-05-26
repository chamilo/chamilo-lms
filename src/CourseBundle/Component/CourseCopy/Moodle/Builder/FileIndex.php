<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder;

/**
 * Lightweight runtime registry for exported files.
 *
 * This helper is intentionally simple:
 * - it allows physical file copy deduplication by contenthash
 * - it keeps optional logical lookup hooks available for future use
 */
class FileIndex
{
    /**
     * @var array<string,string>
     */
    private static array $subdirByContenthash = [];

    /**
     * @var array<int,array<string,mixed>>
     */
    private static array $fileById = [];

    /**
     * Reset in-memory registry.
     */
    public static function reset(): void
    {
        self::$subdirByContenthash = [];
        self::$fileById = [];
    }

    /**
     * Register one logical file row.
     *
     * @param array<string,mixed> $file
     */
    public static function register(array $file): void
    {
        $contenthash = (string) ($file['contenthash'] ?? '');
        $fileId = (int) ($file['id'] ?? 0);

        if ('' !== $contenthash) {
            self::$subdirByContenthash[$contenthash] = substr($contenthash, 0, 2);
        }

        if ($fileId > 0) {
            self::$fileById[$fileId] = $file;
        }
    }

    /**
     * Resolve the export subdirectory for a contenthash.
     */
    public static function resolveSubdirByContenthash(string $contenthash): string
    {
        return self::$subdirByContenthash[$contenthash] ?? substr($contenthash, 0, 2);
    }

    /**
     * Get a registered file by id.
     *
     * @return array<string,mixed>|null
     */
    public static function getById(int $fileId): ?array
    {
        return self::$fileById[$fileId] ?? null;
    }
}
