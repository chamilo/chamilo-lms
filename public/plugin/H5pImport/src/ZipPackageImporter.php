<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\PluginBundle\H5pImport\H5pImporter;

use Exception;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

/**
 * ZIP importer for H5P packages.
 */
class ZipPackageImporter extends H5pPackageImporter
{
    private const ALLOWED_EXTENSIONS = [
        'json',
        'png',
        'jpg',
        'jpeg',
        'gif',
        'bmp',
        'tif',
        'tiff',
        'svg',
        'eot',
        'ttf',
        'woff',
        'woff2',
        'otf',
        'webm',
        'mp4',
        'ogg',
        'mp3',
        'm4a',
        'wav',
        'txt',
        'pdf',
        'xml',
        'csv',
        'md',
        'vtt',
        'webvtt',
        'js',
        'css',
    ];

    /**
     * @throws Exception
     */
    public function import(): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new Exception('ZipArchive extension is not available.');
        }

        $zipFile = new ZipArchive();
        $openResult = $zipFile->open($this->packageFileInfo['tmp_name']);

        if (true !== $openResult) {
            throw new Exception('Unable to open H5P package.');
        }

        $packageSize = 0;
        $hasH5pJson = false;
        $hasContentJson = false;

        for ($i = 0; $i < $zipFile->numFiles; ++$i) {
            $stat = $zipFile->statIndex($i);

            if (false === $stat || empty($stat['name'])) {
                continue;
            }

            $entryName = str_replace('\\', '/', (string) $stat['name']);

            $this->validateZipEntryName($entryName);

            if (str_ends_with($entryName, '/')) {
                continue;
            }

            if (preg_match('~\.(php[0-9]?|phtml|phar)$~i', $entryName)) {
                $zipFile->close();

                throw new Exception(sprintf('File "%s" contains a PHP script.', $entryName));
            }

            $extension = strtolower((string) pathinfo($entryName, PATHINFO_EXTENSION));

            if ('' !== $extension && !in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                $zipFile->close();

                throw new Exception(sprintf('File "%s" has an invalid extension.', $entryName));
            }

            if ('h5p.json' === $entryName) {
                $hasH5pJson = true;
            }

            if ('content/content.json' === $entryName || 'content.json' === $entryName) {
                $hasContentJson = true;
            }

            $packageSize += (int) ($stat['size'] ?? 0);
        }

        if (!$hasH5pJson || !$hasContentJson) {
            $zipFile->close();

            throw new Exception('Invalid H5P package.');
        }

        $this->validateEnoughSpace($packageSize);

        $pathInfo = pathinfo((string) ($this->packageFileInfo['name'] ?? 'package.h5p'));
        $packageName = $pathInfo['filename'] ?? ('package_'.uniqid());

        $packageDirectoryPath = $this->generatePackageDirectory($packageName);

        if (!$zipFile->extractTo($packageDirectoryPath)) {
            $zipFile->close();

            throw new Exception('Unable to extract H5P package.');
        }

        $zipFile->close();

        return $packageDirectoryPath;
    }

    /**
     * @throws Exception
     */
    protected function validateEnoughSpace(int $packageSize): void
    {
        $freeSpace = @disk_free_space($this->courseStoragePath);

        if (false !== $freeSpace && $packageSize > $freeSpace) {
            throw new Exception('Not enough disk space to store package.');
        }
    }

    /**
     * @throws Exception
     */
    private function validateZipEntryName(string $entryName): void
    {
        if (str_starts_with($entryName, '/')) {
            throw new Exception(sprintf('Invalid ZIP entry "%s".', $entryName));
        }

        if (str_contains($entryName, '../') || str_contains($entryName, '..\\')) {
            throw new Exception(sprintf('ZIP entry "%s" contains an invalid relative path.', $entryName));
        }
    }

    private function generatePackageDirectory(string $name): string
    {
        $baseDirectory = rtrim($this->courseStoragePath, '/').'/content';
        $safeName = api_replace_dangerous_char($name);
        $directoryPath = $baseDirectory.'/'.$safeName;

        $filesystem = new Filesystem();
        $filesystem->mkdir($baseDirectory, api_get_permissions_for_new_directories());
        $filesystem->mkdir(
            rtrim($this->courseStoragePath, '/').'/libraries',
            api_get_permissions_for_new_directories()
        );

        if ($filesystem->exists($directoryPath)) {
            $counter = 1;

            while ($filesystem->exists($directoryPath)) {
                $directoryPath = $baseDirectory.'/'.$safeName.'_'.$counter;
                ++$counter;
            }
        }

        $filesystem->mkdir($directoryPath, api_get_permissions_for_new_directories());

        return $directoryPath;
    }
}
