<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Importer;

use DocumentManager;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

/**
 * Class ZipPackageImporter.
 */
class ZipPackageImporter extends PackageImporter
{
    public function import(): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new Exception('ZipArchive extension is not available.');
        }

        $zipFile = new ZipArchive();
        $openResult = $zipFile->open($this->packageFileInfo['tmp_name']);

        if (true !== $openResult) {
            throw new Exception('Unable to open ZIP package.');
        }

        $packageSize = 0;

        for ($i = 0; $i < $zipFile->numFiles; ++$i) {
            $stat = $zipFile->statIndex($i);

            if (false === $stat || empty($stat['name'])) {
                continue;
            }

            $entryName = (string) $stat['name'];

            $this->validateZipEntryName($entryName);

            if (preg_match('~\.(php[0-9]?|phtml|phar)$~i', $entryName)) {
                $zipFile->close();

                throw new Exception(sprintf('File "%s" contains a PHP script', $entryName));
            }

            $baseName = basename($entryName);

            if (\in_array($baseName, ['tincan.xml', 'cmi5.xml'], true)) {
                $this->packageType = explode('.', $baseName, 2)[0];
            }

            $packageSize += (int) ($stat['size'] ?? 0);
        }

        if (empty($this->packageType)) {
            $zipFile->close();

            throw new Exception('Invalid package');
        }

        $this->validateEnoughSpace($packageSize);

        $pathInfo = pathinfo($this->packageFileInfo['name']);
        $packageName = $pathInfo['filename'] ?? ('package_'.uniqid());

        $packageDirectoryPath = $this->generatePackageDirectory($packageName);

        if (!$zipFile->extractTo($packageDirectoryPath)) {
            $zipFile->close();

            throw new Exception('Unable to extract ZIP package.');
        }

        $zipFile->close();

        $manifestPath = $packageDirectoryPath.'/'.$this->packageType.'.xml';

        if (!is_file($manifestPath)) {
            throw new Exception(sprintf('Manifest file "%s.xml" not found after extraction.', $this->packageType));
        }

        return $manifestPath;
    }

    /**
     * @throws Exception
     */
    protected function validateEnoughSpace(int $packageSize): void
    {
        $baseDirectory = dirname($this->courseDirectoryPath);
        $freeSpace = @disk_free_space($baseDirectory);

        if (false !== $freeSpace && $packageSize > $freeSpace) {
            throw new Exception('Not enough disk space to store package.');
        }
    }

    /**
     * @throws Exception
     */
    private function validateZipEntryName(string $entryName): void
    {
        $normalized = str_replace('\\', '/', $entryName);

        if (str_starts_with($normalized, '/')) {
            throw new Exception(sprintf('Invalid ZIP entry "%s".', $entryName));
        }

        if (str_contains($normalized, '../') || str_contains($normalized, '..\\')) {
            throw new Exception(sprintf('ZIP entry "%s" contains an invalid relative path.', $entryName));
        }
    }

    private function generatePackageDirectory(string $name): string
    {
        $directoryPath = implode(
            '/',
            [
                rtrim($this->courseDirectoryPath, '/'),
                $this->packageType,
                api_replace_dangerous_char($name),
            ]
        );

        $filesystem = new Filesystem();
        $filesystem->mkdir(
            $directoryPath,
            api_get_permissions_for_new_directories()
        );

        return $directoryPath;
    }
}
