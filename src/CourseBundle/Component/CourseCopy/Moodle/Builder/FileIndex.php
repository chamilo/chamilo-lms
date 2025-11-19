<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder;

final class FileIndex
{
    /**
     * @var array<string,int>
     */
    private static array $byHash = [];

    /**
     * @var array<string,string>
     */
    private static array $subdirByHash = [];

    /**
     * @param array{id:int, contenthash?:string} $file
     */
    public static function register(array $file): void
    {
        $ch = (string) ($file['contenthash'] ?? '');
        if ('' === $ch) {
            return;
        }
        if (!isset(self::$byHash[$ch])) {
            self::$byHash[$ch] = (int) $file['id'];
        }
        $subdir = substr($ch, 0, 2);
        self::$subdirByHash[$ch] = $subdir;
    }

    /**
     * @param array<int,array{id:int, contenthash?:string}> $files
     */
    public static function registerMany(array $files): void
    {
        foreach ($files as $f) {
            self::register($f);
        }
    }

    public static function resolveByContenthash(?string $contenthash): ?int
    {
        if (!$contenthash) {
            return null;
        }

        return self::$byHash[$contenthash] ?? null;
    }

    public static function resolveSubdirByContenthash(?string $contenthash): ?string
    {
        if (!$contenthash) {
            return null;
        }

        return self::$subdirByHash[$contenthash] ?? null;
    }

    public static function reset(): void
    {
        self::$byHash = [];
        self::$subdirByHash = [];
    }
}
