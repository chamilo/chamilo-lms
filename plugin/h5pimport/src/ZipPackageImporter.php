<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\H5pImport\H5pImporter;

use DocumentManager;
use Exception;
use PclZip;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ZipImporter.
 *
 * @package Chamilo\PluginBundle\H5pImport\Importer
 */
class ZipPackageImporter extends H5pPackageImporter
{
    /**
     * {@inheritDoc}
     */
    public function import(): string
    {
        $zipFile = new PclZip($this->packageFileInfo['tmp_name']);
        $zipContent = $zipFile->listContent();
        $aux = false;

        foreach ($zipContent as $content) {
            if ($content['filename'] === 'h5p.json') {
                $aux = true;
            }
        }

        if ($aux) {
            $packageSize = array_reduce(
                $zipContent,
                function ($accumulator, $zipEntry) {
                    if (preg_match('~.(php.*|phtml)$~i', $zipEntry['filename'])) {
                        throw new Exception("File \"{$zipEntry['filename']}\" contains a PHP script");
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
            return "$packageDirectoryPath/h5p.json";
        } else {
            throw new Exception('Missing h5p json file inside package');
        }

    }

    /**
     * @throws Exception
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
                'h5p',
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
