<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy;

use PharData;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;
use UnexpectedValueException;
use ZipArchive;

use const PATHINFO_EXTENSION;

/**
 * Opens a course backup by its bytes, not by the filename.
 *
 * Moodle's .mbz is a gzip-compressed tar (magic 1f 8b). Older Moodle backups
 * and Chamilo's own Moodle export are ZIP (magic PK). The stored name is not
 * reliable: uploads used to be renamed to .zip before either check ran.
 */
final class BackupArchive
{
    public const FORMAT_ZIP = 'zip';
    public const FORMAT_GZIP = 'gzip';
    public const FORMAT_UNKNOWN = 'unknown';

    /**
     * @var list<string>
     */
    private const STORAGE_EXTENSIONS = ['zip', 'mbz', 'gz', 'tgz'];

    /**
     * @var list<string>
     */
    private const CHAMILO_INFO_PATHS = [
        'course_info.dat',
        'course/course_info.dat',
        'backup/course_info.dat',
    ];

    public static function detectFormat(string $path): string
    {
        $handle = fopen($path, 'rb');
        if (false === $handle) {
            return self::FORMAT_UNKNOWN;
        }

        $magic = fread($handle, 4);
        fclose($handle);
        if (!\is_string($magic) || \strlen($magic) < 2) {
            return self::FORMAT_UNKNOWN;
        }

        if (
            str_starts_with($magic, "PK\x03\x04")
            || str_starts_with($magic, "PK\x05\x06")
            || str_starts_with($magic, "PK\x07\x08")
        ) {
            return self::FORMAT_ZIP;
        }

        if (str_starts_with($magic, "\x1f\x8b")) {
            return self::FORMAT_GZIP;
        }

        return self::FORMAT_UNKNOWN;
    }

    /**
     * Extension used when storing an upload. Gzip payloads are never kept as .zip:
     * Phar refuses a .zip suffix, and ZipArchive cannot open them either.
     */
    public static function storageExtension(string $path, string $clientExtension): string
    {
        $clientExtension = strtolower($clientExtension);
        if (!\in_array($clientExtension, self::STORAGE_EXTENSIONS, true)) {
            $clientExtension = 'zip';
        }

        $format = self::detectFormat($path);
        if (self::FORMAT_GZIP === $format) {
            return \in_array($clientExtension, ['mbz', 'tgz', 'gz'], true) ? $clientExtension : 'mbz';
        }

        if (self::FORMAT_ZIP === $format) {
            return \in_array($clientExtension, ['zip', 'mbz'], true) ? $clientExtension : 'zip';
        }

        return $clientExtension;
    }

    /**
     * Which of the given root markers exist in the archive.
     * Keys are the requested marker names; missing archives report every marker absent.
     *
     * @param list<string> $markers
     *
     * @return array<string, bool>
     */
    public static function contains(string $path, array $markers): array
    {
        $found = [];
        foreach ($markers as $marker) {
            $found[$marker] = false;
        }

        if ([] === $markers || !is_file($path)) {
            return $found;
        }

        $format = self::detectFormat($path);
        if (self::FORMAT_ZIP === $format) {
            self::scanZip($path, $found);

            return $found;
        }

        if (self::FORMAT_GZIP === $format) {
            self::withGzip($path, static function (PharData $phar) use (&$found): void {
                self::scanPhar($phar, $found);
            });
        }

        return $found;
    }

    public static function extract(string $path, string $destination): void
    {
        if (!is_dir($destination) && !mkdir($destination, 0775, true) && !is_dir($destination)) {
            throw new RuntimeException('Cannot create destination directory');
        }

        $format = self::detectFormat($path);
        if (self::FORMAT_ZIP === $format) {
            self::extractZip($path, $destination);

            return;
        }

        if (self::FORMAT_GZIP === $format) {
            self::extractGzip($path, $destination);

            return;
        }

        throw new RuntimeException('Unsupported archive type');
    }

    /**
     * @param array<string, bool> $found
     */
    private static function scanZip(string $path, array &$found): void
    {
        $zip = new ZipArchive();
        if (true !== $zip->open($path)) {
            return;
        }

        try {
            for ($index = 0; $index < $zip->numFiles; ++$index) {
                $name = $zip->getNameIndex($index);
                if (!\is_string($name)) {
                    continue;
                }
                self::mark($found, $name);
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * @param array<string, bool> $found
     */
    private static function scanPhar(PharData $phar, array &$found): void
    {
        $entries = new RecursiveIteratorIterator($phar);
        foreach ($entries as $entry) {
            if (!$entry->isFile()) {
                continue;
            }
            self::mark($found, $entries->getSubPathname());
        }
    }

    /**
     * @param array<string, bool> $found
     */
    private static function mark(array &$found, string $entryName): void
    {
        $normalized = self::normalizeEntryName($entryName);
        foreach ($found as $marker => $already) {
            if ($already) {
                continue;
            }
            if (self::isMarker($normalized, $marker)) {
                $found[$marker] = true;
            }
        }
    }

    private static function isMarker(string $normalizedEntry, string $marker): bool
    {
        $marker = strtolower($marker);
        if ($normalizedEntry === $marker) {
            return true;
        }

        return 'course_info.dat' === $marker
            && \in_array($normalizedEntry, self::CHAMILO_INFO_PATHS, true);
    }

    private static function extractZip(string $path, string $destination): void
    {
        $zip = new ZipArchive();
        if (true !== $zip->open($path)) {
            throw new RuntimeException('Cannot open zip');
        }

        try {
            for ($index = 0; $index < $zip->numFiles; ++$index) {
                $name = $zip->getNameIndex($index);
                if (!\is_string($name)) {
                    continue;
                }
                self::assertSafeRelativePath($name);
            }

            if (!$zip->extractTo($destination)) {
                throw new RuntimeException('Cannot extract zip');
            }
        } finally {
            $zip->close();
        }
    }

    private static function extractGzip(string $path, string $destination): void
    {
        self::withGzip($path, static function (PharData $phar) use ($destination): void {
            $entries = new RecursiveIteratorIterator($phar, RecursiveIteratorIterator::SELF_FIRST);
            foreach ($entries as $entry) {
                if ($entry->isLink()) {
                    throw new RuntimeException('Malicious archive entry detected');
                }
                self::assertSafeRelativePath($entries->getSubPathname());
            }

            if (!$phar->extractTo($destination, null, true)) {
                throw new RuntimeException('Cannot extract gzip archive');
            }
        });
    }

    /**
     * @template T
     *
     * @param callable(PharData): T $callback
     *
     * @return T
     */
    private static function withGzip(string $path, callable $callback): mixed
    {
        [$phar, $staged] = self::openGzip($path);

        try {
            return $callback($phar);
        } finally {
            unset($phar);
            if (null !== $staged && is_file($staged)) {
                unlink($staged);
            }
        }
    }

    /**
     * Phar decides the format from the suffix. A gzip payload named .zip is
     * rejected ("already exists as a regular phar"), so those are staged under
     * a .tar.gz name. Any other suffix is opened in place, then staged only
     * if Phar still refuses it.
     *
     * @return array{0: PharData, 1: ?string}
     */
    private static function openGzip(string $path): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $staged = null;
        $openPath = $path;
        if ('zip' === $extension) {
            $staged = self::stageGzip($path);
            $openPath = $staged;
        }

        try {
            return [new PharData($openPath), $staged];
        } catch (UnexpectedValueException $e) {
            if (null !== $staged) {
                self::deleteStaged($staged);

                throw new RuntimeException('Cannot open gzip archive', 0, $e);
            }

            $staged = self::stageGzip($path);

            try {
                return [new PharData($staged), $staged];
            } catch (UnexpectedValueException $e2) {
                self::deleteStaged($staged);

                throw new RuntimeException('Cannot open gzip archive', 0, $e2);
            }
        } catch (Throwable $e) {
            if (null !== $staged) {
                self::deleteStaged($staged);
            }

            throw $e;
        }
    }

    private static function stageGzip(string $path): string
    {
        $staged = rtrim(sys_get_temp_dir(), '/').'/chamilo_mbz_'.bin2hex(random_bytes(8)).'.tar.gz';
        if (!copy($path, $staged)) {
            throw new RuntimeException('Cannot read gzip archive');
        }

        return $staged;
    }

    private static function deleteStaged(string $staged): void
    {
        if (is_file($staged)) {
            unlink($staged);
        }
    }

    private static function assertSafeRelativePath(string $name): void
    {
        $name = self::normalizeEntryName($name);
        if (str_contains($name, "\0") || str_starts_with($name, '/') || preg_match('/^[A-Za-z]:/', $name)) {
            throw new RuntimeException('Malicious archive entry detected');
        }

        foreach (explode('/', $name) as $part) {
            if ('..' === $part) {
                throw new RuntimeException('Malicious archive entry detected');
            }
        }
    }

    private static function normalizeEntryName(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        while (str_starts_with($name, './')) {
            $name = substr($name, 2);
        }

        return strtolower($name);
    }
}
