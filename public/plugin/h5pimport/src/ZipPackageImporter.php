<?php

// For licensing terms, see /license.txt

namespace Chamilo\PluginBundle\H5pImport\H5pImporter;

use Exception;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ZipPackageImporter.
 */
class ZipPackageImporter extends H5pPackageImporter
{
    /*
     * Allowed file extensions
     * List obtained from H5P: https://h5p.org/allowed-file-extensions
     * */
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
     * Import an H5P package. No DB change.
     *
     * @return string The path to the extracted package directory.
     *
     * @throws Exception When the H5P package is invalid.
     */
    public function import(): string
    {
        $zipFile = new \PclZip($this->packageFileInfo['tmp_name']);
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

            return "{$packageDirectoryPath}";
        }

        throw new Exception('Invalid H5P package');
    }

    /**
     * @throws Exception
     */
    protected function validateEnoughSpace(int $packageSize)
    {
        $courseSpaceQuota = \DocumentManager::get_course_quota($this->course->getCode());

        if (!enough_size($packageSize, $this->courseDirectoryPath, $courseSpaceQuota)) {
            throw new Exception('Not enough space to store package.');
        }
    }

    /**
     * Validate an H5P package.
     * Check if 'h5p.json' or 'content/content.json' files exist
     * and if the files are in a file whitelist (ALLOWED_EXTENSIONS).
     *
     * @param array $h5pPackageContent the content of the H5P package
     *
     * @return bool whether the H5P package is valid or not
     */
    private function validateH5pPackageContent(array $h5pPackageContent): bool
    {
        $validPackage = false;

        if (!empty($h5pPackageContent)) {
            foreach ($h5pPackageContent as $content) {
                $filename = $content['filename'];

                if (0 !== preg_match('/(^[\._]|\/[\._]|\\\[\._])/', $filename)) {
                    // Skip any file or folder starting with a . or _
                    continue;
                }

                $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);

                if (in_array($fileExtension, self::ALLOWED_EXTENSIONS)) {
                    $validPackage = 'h5p.json' === $filename || 'content/content.json' === $filename;
                    if ($validPackage) {
                        break;
                    }
                }
            }
        }

        return $validPackage;
    }

    private function generatePackageDirectory(string $name): string
    {
        $baseDirectory = $this->courseDirectoryPath.'/h5p/content/';
        $safeName = api_replace_dangerous_char($name);
        $directoryPath = $baseDirectory.$safeName;

        $fs = new Filesystem();

        if ($fs->exists($directoryPath)) {
            $counter = 1;

            // Add numeric suffix to the name until a unique directory name is found
            while ($fs->exists($directoryPath)) {
                $modifiedName = $safeName.'_'.$counter;
                $directoryPath = $baseDirectory.$modifiedName;
                ++$counter;
            }
        }

        $fs->mkdir(
            $directoryPath,
            api_get_permissions_for_new_directories()
        );

        $sharedLibrariesDir = $this->courseDirectoryPath.'/h5p/libraries';

        if (!$fs->exists($sharedLibrariesDir)) {
            $fs->mkdir(
                $sharedLibrariesDir,
                api_get_permissions_for_new_directories()
            );
        }

        return $directoryPath;
    }
}
