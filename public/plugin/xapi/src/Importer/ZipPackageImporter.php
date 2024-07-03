<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Importer;

use DocumentManager;
use Exception;
use PclZip;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ZipImporter.
 *
 * @package Chamilo\PluginBundle\XApi\Importer
 */
class ZipPackageImporter extends PackageImporter
{
    /**
     * {@inheritDoc}
     */
    public function import(): string
    {
        $zipFile = new PclZip($this->packageFileInfo['tmp_name']);
        $zipContent = $zipFile->listContent();

        $packageSize = array_reduce(
            $zipContent,
            function ($accumulator, $zipEntry) {
                if (preg_match('~.(php.*|phtml)$~i', $zipEntry['filename'])) {
                    throw new Exception("File \"{$zipEntry['filename']}\" contains a PHP script");
                }

                if (in_array($zipEntry['filename'], ['tincan.xml', 'cmi5.xml'])) {
                    $this->packageType = explode('.', $zipEntry['filename'], 2)[0];
                }

                return $accumulator + $zipEntry['size'];
            }
        );

        if (empty($this->packageType)) {
            throw new Exception('Invalid package');
        }

        $this->validateEnoughSpace($packageSize);

        $pathInfo = pathinfo($this->packageFileInfo['name']);

        $packageDirectoryPath = $this->generatePackageDirectory($pathInfo['filename']);

        $zipFile->extract($packageDirectoryPath);

        return "$packageDirectoryPath/{$this->packageType}.xml";
    }

    /**
     * @throws \Exception
     */
    protected function validateEnoughSpace(int $packageSize)
    {
        $courseSpaceQuota = DocumentManager::get_course_quota($this->course->getCode());

        if (!enough_size($packageSize, $this->courseDirectoryPath, $courseSpaceQuota)) {
            throw new Exception('Not enough space to storage package.');
        }
    }

    private function generatePackageDirectory(string $name): string
    {
        $directoryPath = implode(
            '/',
            [
                $this->courseDirectoryPath,
                $this->packageType,
                api_replace_dangerous_char($name),
            ]
        );

        $fs = new Filesystem();
        $fs->mkdir(
            $directoryPath,
            api_get_permissions_for_new_directories()
        );

        return $directoryPath;
    }
}
