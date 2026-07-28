<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use RuntimeException;
use ZipArchive;

final class UpdateArchiveInspector
{
    private const int ZIP_OPSYS_UNIX = 3;

    /**
     * @return array{file_count: int, top_level_entries: string[]}
     */
    public function inspect(string $packagePath): array
    {
        if (!is_file($packagePath) || !is_readable($packagePath)) {
            throw new RuntimeException('Update package is not readable: '.$packagePath);
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($packagePath);

        if (true !== $openResult) {
            throw new RuntimeException('Update package is not a valid ZIP archive.');
        }

        $topLevelEntries = [];

        try {
            if (0 === $zip->numFiles) {
                throw new RuntimeException('Update package ZIP archive is empty.');
            }

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entryName = $zip->getNameIndex($index);

                if (false === $entryName || '' === $entryName) {
                    throw new RuntimeException('Update package contains an unreadable ZIP entry.');
                }

                $this->assertSafeEntryName($entryName);
                $this->assertEntryIsNotSymlink($zip, $index, $entryName);

                $firstSegment = explode('/', trim($entryName, '/'))[0] ?? '';
                if ('' !== $firstSegment) {
                    $topLevelEntries[$firstSegment] = true;
                }
            }

            return [
                'file_count' => $zip->numFiles,
                'top_level_entries' => array_keys($topLevelEntries),
            ];
        } finally {
            $zip->close();
        }
    }

    private function assertSafeEntryName(string $entryName): void
    {
        $normalized = str_replace('\\', '/', $entryName);

        if (str_starts_with($normalized, '/') || str_contains($normalized, "\0")) {
            throw new RuntimeException('Update package contains an unsafe absolute path: '.$entryName);
        }

        $segments = explode('/', $normalized);

        foreach ($segments as $segment) {
            if ('..' === $segment) {
                throw new RuntimeException('Update package contains a path traversal entry: '.$entryName);
            }
        }
    }

    private function assertEntryIsNotSymlink(ZipArchive $zip, int $index, string $entryName): void
    {
        if (!method_exists($zip, 'getExternalAttributesIndex')) {
            return;
        }

        $opsys = 0;
        $attributes = 0;

        if (!$zip->getExternalAttributesIndex($index, $opsys, $attributes)) {
            return;
        }

        if (self::ZIP_OPSYS_UNIX !== $opsys) {
            return;
        }

        $fileType = ($attributes >> 16) & 0170000;

        if (0120000 === $fileType) {
            throw new RuntimeException('Update package contains a symbolic link: '.$entryName);
        }
    }
}
