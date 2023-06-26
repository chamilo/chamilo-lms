<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\H5pImport\H5pImporter;

use DocumentManager;
use Exception;
use PclZip;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ZipPackageImporter.
 *
 * @package Chamilo\PluginBundle\H5pImport\Importer
 */
class ZipPackageImporter extends H5pPackageImporter
{
    private const ALLOWED_FILES = [
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
        'rtf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'ppt',
        'pptx',
        'odt',
        'ods',
        'odp',
        'xml',
        'csv',
        'diff',
        'patch',
        'swf',
        'md',
        'textile',
        'vtt',
        'webvtt',
        'gltf',
        'gl',
        'js',
        'css',
    ];

    /**
     * {@inheritDoc}
     */
    public function import(): string
    {
        $zipFile = new PclZip($this->packageFileInfo['tmp_name']);
        $zipContent = $zipFile->listContent();

        if ($this->validateH5pPackageContent($zipContent)) {

            $packageSize = array_reduce(
                $zipContent,
                function ($accumulator, $zipEntry) {
                    return $accumulator + $zipEntry['size'];
                }
            );

            $this->validateEnoughSpace($packageSize);

            $pathInfo = pathinfo($this->packageFileInfo['name']);

            $packageDirectoryPath = $this->generatePackageDirectory($pathInfo['filename']);
            $zipFile->extract($packageDirectoryPath);

            return "$packageDirectoryPath";

        } else {
            throw new Exception('Invalid H5P package');
        }

    }

    /**
     * @throws Exception
     */
    protected function validateEnoughSpace(int $packageSize)
    {
        $courseSpaceQuota = DocumentManager::get_course_quota($this->course->getCode());

        if (!enough_size($packageSize, $this->courseDirectoryPath, $courseSpaceQuota)) {
            throw new Exception('Not enough space to store package.');
        }
    }
    /**
     * Validate a H5P package.
     * Check if exits h5p.json, content.json file and if the files are in a file whitelist (ALLOWED_FILES).
     */
    private function validateH5pPackageContent(array $h5pPackageContent): bool
    {
        $validPackage = false;

        if (!empty($h5pPackageContent)) {

            foreach ($h5pPackageContent as $content) {

                if (preg_match('/(^[\._]|\/[\._]|\\\[\._])/', $content['filename']) !== 0) {
                    // Skip any file or folder starting with a . or _
                } elseif (!in_array(pathinfo($content['filename'], PATHINFO_EXTENSION), self::ALLOWED_FILES)) {
                    $validPackage = false;
                    break;
                } elseif ($content['filename'] === 'h5p.json') {
                    $validPackage = true;
                } elseif ($content['filename'] === 'content/content.json') {
                    $validPackage = true;
                }

            }
        }

        return $validPackage;

    }
    private function generatePackageDirectory(string $name): string
    {
        $baseDirectory = $this->courseDirectoryPath . '/h5p/';
        $safeName = api_replace_dangerous_char($name);
        $directoryPath = $baseDirectory . $safeName;

        $fs = new Filesystem();

        if ($fs->exists($directoryPath)) {
            $counter = 1;

            // Add numeric suffix to the name until a unique directory name is found
            while ($fs->exists($directoryPath)) {
                $modifiedName = $safeName . '_' . $counter;
                $directoryPath = $baseDirectory . $modifiedName;
                $counter++;
            }
        }

        $fs->mkdir(
            $directoryPath,
            api_get_permissions_for_new_directories()
        );

        $sharedLibrariesDir = dirname($directoryPath) . '/libraries';

        if (!$fs->exists($sharedLibrariesDir)) {
            $fs->mkdir(
                $sharedLibrariesDir,
                api_get_permissions_for_new_directories()
            );
        }

        return $directoryPath;
    }

}
