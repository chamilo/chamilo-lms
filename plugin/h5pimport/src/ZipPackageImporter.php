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
     * Extensions that must never be extracted, regardless of allowlist.
     * These can enable server-side code execution.
     */
    private const BLOCKED_EXTENSIONS = [
        'php',
        'php3',
        'php4',
        'php5',
        'php6',
        'php7',
        'phtml',
        'phar',
        'shtml',
        'cgi',
        'pl',
        'py',
        'rb',
        'sh',
        'bash',
        'bat',
        'cmd',
        'exe',
        'dll',
        'so',
    ];

    /**
     * Filenames that must never be extracted.
     * These can override server configuration to enable code execution.
     */
    private const BLOCKED_FILENAMES = [
        '.htaccess',
        '.htpasswd',
        '.user.ini',
        'web.config',
        'php.ini',
    ];

    /**
     * Import an H5P package. No DB change.
     *
     * @throws Exception When the H5P package is invalid.
     *
     * @return string The path to the extracted package directory.
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

            // Extract only the files that passed validation — never extract the full
            // archive blindly, as the zip may contain entries skipped during validation.
            $safeFiles = $this->getSafeFileList($zipContent);
            $zipFile->extract(
                PCLZIP_OPT_PATH, $packageDirectoryPath,
                PCLZIP_OPT_BY_NAME, $safeFiles
            );

            // Write a protective .htaccess so that even if a server misconfiguration
            // exists, files in this directory cannot be executed as scripts.
            $this->writeProtectiveHtaccess($packageDirectoryPath);

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
     *
     * Every entry in the zip must pass all of the following checks before
     * extraction is allowed:
     *  - No file or directory component may start with '.' or '_' (blocks
     *    .htaccess, .htpasswd, .user.ini, etc.)
     *  - The base filename must not be in the blocked-filenames list.
     *  - The extension must not be in the blocked-extensions list.
     *  - The extension must be in the allowed-extensions list.
     *
     * Additionally the archive must contain 'h5p.json' to be considered a
     * valid H5P package.
     *
     * @param array $h5pPackageContent the content of the H5P package
     *
     * @return bool whether the H5P package is valid or not
     */
    private function validateH5pPackageContent(array $h5pPackageContent): bool
    {
        if (empty($h5pPackageContent)) {
            return false;
        }

        $hasH5pJson = false;

        foreach ($h5pPackageContent as $content) {
            $filename = $content['filename'];

            // Reject — do NOT skip — any file or directory component that starts
            // with '.' or '_'. Previously this used `continue`, which allowed
            // dangerous files like .htaccess to be silently included in the
            // extraction while the loop kept searching for h5p.json.
            if (0 !== preg_match('/(^[\._]|\/[\._]|\\\[\._])/', $filename)) {
                return false;
            }

            // Directories have no extension to check; skip the extension tests.
            if (1 === ($content['folder'] ?? 0)) {
                continue;
            }

            $basename = basename($filename);
            $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            // Reject server-configuration and script-execution override files.
            if (in_array(strtolower($basename), self::BLOCKED_FILENAMES, true)) {
                return false;
            }

            // Reject extensions that can result in server-side code execution.
            if (in_array($fileExtension, self::BLOCKED_EXTENSIONS, true)) {
                return false;
            }

            // Reject any extension not explicitly on the allowlist.
            if (!in_array($fileExtension, self::ALLOWED_EXTENSIONS, true)) {
                return false;
            }

            if ('h5p.json' === $filename) {
                $hasH5pJson = true;
            }
        }

        return $hasH5pJson;
    }

    /**
     * Return filenames of zip entries that are safe to extract.
     *
     * This mirrors the security checks in validateH5pPackageContent so that
     * the extraction step is independently guarded even if validation logic
     * evolves in the future.
     *
     * @param array $h5pPackageContent PclZip listContent() result
     *
     * @return array list of filenames safe to pass to PCLZIP_OPT_BY_NAME
     */
    private function getSafeFileList(array $h5pPackageContent): array
    {
        $safeFiles = [];

        foreach ($h5pPackageContent as $content) {
            $filename = $content['filename'];

            if (0 !== preg_match('/(^[\._]|\/[\._]|\\\[\._])/', $filename)) {
                continue;
            }

            if (1 === ($content['folder'] ?? 0)) {
                continue;
            }

            $basename = basename($filename);
            $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array(strtolower($basename), self::BLOCKED_FILENAMES, true)) {
                continue;
            }

            if (in_array($fileExtension, self::BLOCKED_EXTENSIONS, true)) {
                continue;
            }

            if (!in_array($fileExtension, self::ALLOWED_EXTENSIONS, true)) {
                continue;
            }

            $safeFiles[] = $filename;
        }

        return $safeFiles;
    }

    /**
     * Write a protective .htaccess into the extracted package directory.
     *
     * This is a defence-in-depth measure: even if a file with a dangerous
     * extension somehow reached the directory (e.g. via a future code path),
     * Apache will not execute it as a script and will not allow per-directory
     * configuration files to override this setting.
     */
    private function writeProtectiveHtaccess(string $directory): void
    {
        $htaccessPath = $directory.'/.htaccess';
        $content = <<<'HTACCESS'
# Auto-generated by Chamilo H5P importer — do not remove.
# Prevent execution of any server-side scripts in this directory.
Options -ExecCGI -Indexes
php_flag engine off
RemoveHandler .php .php3 .php4 .php5 .php6 .php7 .phtml .phar
RemoveType .php .php3 .php4 .php5 .php6 .php7 .phtml .phar
AddType text/plain .php .php3 .php4 .php5 .php6 .php7 .phtml .phar .txt
HTACCESS;

        file_put_contents($htaccessPath, $content);
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
                $counter++;
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
