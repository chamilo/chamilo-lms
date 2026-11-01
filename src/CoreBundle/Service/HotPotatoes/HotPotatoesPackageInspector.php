<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\HotPotatoes;

use RuntimeException;
use ZipArchive;

use const PATHINFO_EXTENSION;
use const SORT_STRING;

/**
 * Validates a Hot Potatoes export before any file is written to course storage.
 *
 * This class does not extract files or authorize publishing HTML in a browser.
 */
final class HotPotatoesPackageInspector
{
    private const MAX_ARCHIVE_BYTES = 25 * 1024 * 1024;
    private const MAX_UNCOMPRESSED_BYTES = 100 * 1024 * 1024;
    private const MAX_FILE_BYTES = 20 * 1024 * 1024;
    private const MAX_FILES = 500;
    private const MAX_DEPTH = 12;
    private const MAX_COMPRESSION_RATIO = 200;

    private const ALLOWED_EXTENSIONS = [
        'htm', 'html', 'css', 'js',
        'png', 'jpg', 'jpeg', 'gif', 'webp',
        'mp3', 'ogg', 'wav', 'mp4', 'webm',
        'woff', 'woff2', 'ttf', 'ico', 'txt',
    ];

    /**
     * @return array{format: string, entrypoints: list<string>, files: int, uncompressedBytes: int}
     */
    public function inspect(string $filePath, string $originalFilename): array
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new RuntimeException('The Hot Potatoes upload cannot be read.');
        }

        $size = filesize($filePath);
        if (false === $size || $size <= 0 || $size > self::MAX_ARCHIVE_BYTES) {
            throw new RuntimeException('The Hot Potatoes upload exceeds the supported size limit or is empty.');
        }

        $extension = strtolower((string) pathinfo($originalFilename, PATHINFO_EXTENSION));
        if (\in_array($extension, ['htm', 'html'], true)) {
            $prefix = file_get_contents($filePath, false, null, 0, 8192);
            if (false === $prefix || !preg_match('/<html(?:\s|>)/i', $prefix)) {
                throw new RuntimeException('The uploaded file does not contain an HTML activity.');
            }

            return [
                'format' => 'html',
                'entrypoints' => ['index.'.$extension],
                'files' => 1,
                'uncompressedBytes' => $size,
            ];
        }

        if ('zip' !== $extension) {
            throw new RuntimeException('Only HTML and ZIP Hot Potatoes exports are supported.');
        }

        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP ZIP extension is required.');
        }

        return $this->inspectZip($filePath);
    }

    /**
     * @return array{format: string, entrypoints: list<string>, files: int, uncompressedBytes: int}
     */
    private function inspectZip(string $filePath): array
    {
        $zip = new ZipArchive();
        if (true !== $zip->open($filePath, ZipArchive::CHECKCONS)) {
            throw new RuntimeException('The Hot Potatoes ZIP archive is invalid.');
        }

        try {
            if ($zip->numFiles < 1 || $zip->numFiles > self::MAX_FILES) {
                throw new RuntimeException('The Hot Potatoes ZIP contains too many entries or is empty.');
            }

            $entrypoints = [];
            $seen = [];
            $totalBytes = 0;
            $files = 0;

            for ($index = 0; $index < $zip->numFiles; ++$index) {
                $entry = $zip->statIndex($index);
                if (!\is_array($entry)) {
                    throw new RuntimeException('The ZIP contains an unreadable entry.');
                }

                $name = (string) ($entry['name'] ?? '');
                $isDirectory = str_ends_with($name, '/');
                $this->assertSafePath($name, $isDirectory);
                $canonical = strtolower($name);
                if (isset($seen[$canonical])) {
                    throw new RuntimeException('The ZIP contains duplicate or case-conflicting paths.');
                }
                $seen[$canonical] = true;

                if ($zip->getExternalAttributesIndex($index, $operatingSystem, $attributes)
                    && ZipArchive::OPSYS_UNIX === $operatingSystem
                ) {
                    $fileType = ($attributes >> 16) & 0170000;
                    if (0 !== $fileType
                        && ($isDirectory ? 0040000 !== $fileType : 0100000 !== $fileType)
                    ) {
                        throw new RuntimeException('The ZIP contains a symbolic link or special file.');
                    }
                }

                if ($isDirectory) {
                    continue;
                }

                $extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
                if (!\in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                    throw new RuntimeException('The ZIP includes a prohibited file type.');
                }

                $uncompressed = (int) ($entry['size'] ?? 0);
                $compressed = (int) ($entry['comp_size'] ?? 0);
                if ($uncompressed < 0 || $uncompressed > self::MAX_FILE_BYTES
                    || $compressed < 0
                    || ($uncompressed > 0 && (0 === $compressed
                        || $uncompressed > $compressed * self::MAX_COMPRESSION_RATIO))
                ) {
                    throw new RuntimeException('The ZIP contains an oversized or excessively compressed file.');
                }
                $totalBytes += $uncompressed;
                if ($totalBytes > self::MAX_UNCOMPRESSED_BYTES) {
                    throw new RuntimeException('The ZIP uncompressed size exceeds the limit.');
                }

                ++$files;
                if (\in_array($extension, ['htm', 'html'], true)) {
                    if (0 === $uncompressed) {
                        throw new RuntimeException('The ZIP contains an empty HTML activity.');
                    }
                    $entrypoints[] = $name;
                }
            }

            if ([] === $entrypoints) {
                throw new RuntimeException('The ZIP does not contain an HTML activity.');
            }

            sort($entrypoints, SORT_STRING);

            return [
                'format' => 'zip',
                'entrypoints' => $entrypoints,
                'files' => $files,
                'uncompressedBytes' => $totalBytes,
            ];
        } finally {
            $zip->close();
        }
    }

    private function assertSafePath(string $path, bool $isDirectory): void
    {
        if ('' === $path || \strlen($path) > 512 || str_starts_with($path, '/')
            || str_contains($path, '\\') || str_contains($path, ':')
            || str_contains($path, '?') || str_contains($path, '#')
            || str_contains($path, '%') || preg_match('/[\x00-\x1f\x7f]/', $path)
        ) {
            throw new RuntimeException('The ZIP contains an unsafe file path.');
        }

        $parts = explode('/', $isDirectory ? substr($path, 0, -1) : $path);
        if (\count($parts) > self::MAX_DEPTH) {
            throw new RuntimeException('The ZIP contains an excessively nested path.');
        }

        foreach ($parts as $part) {
            if ('' === $part || '.' === $part || '..' === $part || str_starts_with($part, '.')) {
                throw new RuntimeException('The ZIP contains an unsafe file path.');
            }
        }
    }
}
