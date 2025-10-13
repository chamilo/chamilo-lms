<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CDocument;
use DocumentManager;
use Exception;

use const PATHINFO_EXTENSION;
use const PHP_EOL;

/**
 * Class FileExport.
 * Handles the export of files and metadata from Moodle courses.
 */
class FileExport
{
    /**
     * @var object
     */
    private $course;

    /**
     * Constructor to initialize course data.
     *
     * @param object $course course object containing resources and path data
     */
    public function __construct(object $course)
    {
        $this->course = $course;
    }

    /**
     * Export files and metadata from files.xml to the specified directory.
     */
    public function exportFiles(array $filesData, string $exportDir): void
    {
        $filesDir = $exportDir.'/files';
        if (!is_dir($filesDir)) {
            mkdir($filesDir, api_get_permissions_for_new_directories(), true);
        }
        $this->createPlaceholderFile($filesDir);

        $unique = ['files' => []];
        $seenKeys = []; // string => true

        foreach (($filesData['files'] ?? []) as $file) {
            $ch = (string) ($file['contenthash'] ?? '');
            $comp = (string) ($file['component'] ?? '');
            $area = (string) ($file['filearea'] ?? '');
            $path = $this->ensureTrailingSlash((string) ($file['filepath'] ?? '/'));
            $name = (string) ($file['filename'] ?? '');

            if ('' === $ch || '' === $name) {
                continue;
            }

            $dedupeKey = implode('|', [$ch, $comp, $area, $path, $name]);
            if (isset($seenKeys[$dedupeKey])) {
                continue;
            }
            $seenKeys[$dedupeKey] = true;

            FileIndex::register($file);

            $file['filepath'] = $path;
            $unique['files'][] = $file;
        }

        foreach ($unique['files'] as $file) {
            $ch = (string) $file['contenthash'];
            $subdir = FileIndex::resolveSubdirByContenthash($ch);
            $this->copyFileToExportDir($file, $filesDir, $subdir);
        }

        $this->createFilesXml($unique, $exportDir);
    }

    /**
     * Get file data from course resources. This is for testing purposes.
     *
     * @return array<string,mixed>
     */
    public function getFilesData(): array
    {
        $adminData = MoodleExport::getAdminUserData();
        $adminId = $adminData['id'] ?? 0;

        $filesData = ['files' => []];

        // Defensive read: documents may be missing
        $docResources = $this->course->resources[RESOURCE_DOCUMENT] ?? [];
        if (!\is_array($docResources)) {
            $docResources = [];
        }

        foreach ($docResources as $document) {
            $filesData = $this->processDocument($filesData, $document);
        }

        // Defensive read: works may be missing (avoids "Undefined array key 'work'")
        $workResources = $this->course->resources[RESOURCE_WORK] ?? [];
        if (!\is_array($workResources)) {
            $workResources = [];
        }

        foreach ($workResources as $work) {
            // getAllDocumentToWork might not exist in some installs; guard it
            $workFiles = \function_exists('getAllDocumentToWork')
                ? (getAllDocumentToWork($work->params['id'] ?? 0, $this->course->info['real_id'] ?? 0) ?: [])
                : [];

            if (!\is_array($workFiles) || empty($workFiles)) {
                continue;
            }

            foreach ($workFiles as $file) {
                // Safely fetch doc data
                $docId = (int) ($file['document_id'] ?? 0);
                if ($docId <= 0) {
                    continue;
                }

                $docData = DocumentManager::get_document_data_by_id(
                    $docId,
                    (string) ($this->course->info['code'] ?? '')
                );

                if (!\is_array($docData) || empty($docData['path'])) {
                    continue;
                }

                $filesData['files'][] = [
                    'id' => $docId,
                    'contenthash' => hash('sha1', basename($docData['path'])),
                    'contextid' => (int) ($this->course->info['real_id'] ?? 0),
                    'component' => 'mod_assign',
                    'filearea' => 'introattachment',
                    'itemid' => (int) ($work->params['id'] ?? 0),
                    'filepath' => '/Documents/',
                    'documentpath' => 'document/'.$docData['path'],
                    'filename' => basename($docData['path']),
                    'userid' => $adminId,
                    'filesize' => (int) ($docData['size'] ?? 0),
                    'mimetype' => $this->getMimeType($docData['path']),
                    'status' => 0,
                    'timecreated' => time() - 3600,
                    'timemodified' => time(),
                    'source' => (string) ($docData['title'] ?? ''),
                    'author' => 'Unknown',
                    'license' => 'allrightsreserved',
                ];
            }
        }

        return $filesData;
    }

    /**
     * Create a placeholder index.html file to prevent an empty directory.
     */
    private function createPlaceholderFile(string $filesDir): void
    {
        $placeholderFile = $filesDir.'/index.html';
        file_put_contents($placeholderFile, '<!-- Placeholder file to ensure the directory is not empty -->');
    }

    /**
     * Copy a file to the export directory using its contenthash.
     *
     * @param array<string,mixed> $file
     */
    private function copyFileToExportDir(array $file, string $filesDir, ?string $precomputedSubdir = null): void
    {
        if (($file['filepath'] ?? '.') === '.') {
            return;
        }

        $contenthash = (string) $file['contenthash'];
        $subDir = $precomputedSubdir ?: substr($contenthash, 0, 2);
        $exportSubDir = $filesDir.'/'.$subDir;

        if (!is_dir($exportSubDir)) {
            mkdir($exportSubDir, api_get_permissions_for_new_directories(), true);
        }

        $destinationFile = $exportSubDir.'/'.$contenthash;

        $filePath = $file['abs_path'] ?? null;
        if (!$filePath) {
            $filePath = $this->course->path.$file['documentpath'];
        }

        if (is_file($filePath)) {
            if (!is_file($destinationFile)) {
                copy($filePath, $destinationFile);
            }
        } else {
            throw new Exception("Source file not found: {$filePath}");
        }
    }

    /**
     * Create the files.xml with the provided file data.
     *
     * @param array<string,mixed> $filesData
     */
    private function createFilesXml(array $filesData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<files>'.PHP_EOL;

        foreach (($filesData['files'] ?? []) as $file) {
            $xmlContent .= $this->createFileXmlEntry($file);
        }

        $xmlContent .= '</files>'.PHP_EOL;
        file_put_contents($destinationDir.'/files.xml', $xmlContent);
    }

    /**
     * Create an XML entry for a file.
     *
     * @param array<string,mixed> $file
     */
    private function createFileXmlEntry(array $file): string
    {
        // itemid is forced to 0 in V1 files.xml for consistency with restore
        return '  <file id="'.(int) $file['id'].'">'.PHP_EOL.
            '    <contenthash>'.htmlspecialchars((string) $file['contenthash']).'</contenthash>'.PHP_EOL.
            '    <contextid>'.(int) $file['contextid'].'</contextid>'.PHP_EOL.
            '    <component>'.htmlspecialchars((string) $file['component']).'</component>'.PHP_EOL.
            '    <filearea>'.htmlspecialchars((string) $file['filearea']).'</filearea>'.PHP_EOL.
            '    <itemid>0</itemid>'.PHP_EOL.
            '    <filepath>'.htmlspecialchars((string) $file['filepath']).'</filepath>'.PHP_EOL.
            '    <filename>'.htmlspecialchars((string) $file['filename']).'</filename>'.PHP_EOL.
            '    <userid>'.(int) $file['userid'].'</userid>'.PHP_EOL.
            '    <filesize>'.(int) $file['filesize'].'</filesize>'.PHP_EOL.
            '    <mimetype>'.htmlspecialchars((string) $file['mimetype']).'</mimetype>'.PHP_EOL.
            '    <status>'.(int) $file['status'].'</status>'.PHP_EOL.
            '    <timecreated>'.(int) $file['timecreated'].'</timecreated>'.PHP_EOL.
            '    <timemodified>'.(int) $file['timemodified'].'</timemodified>'.PHP_EOL.
            '    <source>'.htmlspecialchars((string) $file['source']).'</source>'.PHP_EOL.
            '    <author>'.htmlspecialchars((string) $file['author']).'</author>'.PHP_EOL.
            '    <license>'.htmlspecialchars((string) $file['license']).'</license>'.PHP_EOL.
            '    <sortorder>0</sortorder>'.PHP_EOL.
            '    <repositorytype>$@NULL@$</repositorytype>'.PHP_EOL.
            '    <repositoryid>$@NULL@$</repositoryid>'.PHP_EOL.
            '    <reference>$@NULL@$</reference>'.PHP_EOL.
            '  </file>'.PHP_EOL;
    }

    /**
     * Process a document or folder and add its data to the files array.
     *
     * @param array<string,mixed> $filesData
     */
    private function processDocument(array $filesData, object $document): array
    {
        // Skip files already embedded/handled by PageExport
        if (
            ($document->file_type ?? null) === 'file'
            && isset($this->course->used_page_doc_ids)
            && \in_array($document->source_id, (array) $this->course->used_page_doc_ids, true)
        ) {
            return $filesData;
        }

        // Skip top-level HTML documents that are exported as Page
        if (
            ($document->file_type ?? null) === 'file'
            && 'html' === pathinfo($document->path, PATHINFO_EXTENSION)
            && 1 === substr_count($document->path, '/')
        ) {
            return $filesData;
        }

        if (($document->file_type ?? null) === 'file') {
            $extension = strtolower((string) pathinfo($document->path, PATHINFO_EXTENSION));
            if (!\in_array($extension, ['html', 'htm'], true)) {
                $fileData = $this->getFileData($document);
                $fileData['filepath'] = '/Documents/';
                $fileData['contextid'] = 0;
                $fileData['component'] = 'mod_folder';
                $filesData['files'][] = $fileData;
            }
        } elseif (($document->file_type ?? null) === 'folder') {
            $docRepo = Container::getDocumentRepository();
            $folderFiles = $docRepo->listFilesByParentIid((int) $document->source_id);

            foreach ($folderFiles as $file) {
                $filesData['files'][] = $this->getFolderFileData(
                    $file,
                    (int) $document->source_id,
                    '/Documents/'.\dirname($file['path']).'/'
                );
            }
        }

        return $filesData;
    }

    /**
     * Get file data for a single document.
     *
     * @return array<string,mixed>
     */
    private function getFileData(object $document): array
    {
        $adminData = MoodleExport::getAdminUserData();
        $adminId = $adminData['id'] ?? 0;

        $contenthash = hash('sha1', basename($document->path));
        $mimetype = $this->getMimeType($document->path);

        // Try to resolve absolute path for single file documents
        $absPath = null;
        if (isset($document->source_id)) {
            $repo = Container::getDocumentRepository();
            $doc = $repo->findOneBy(['iid' => (int) $document->source_id]);
            if ($doc instanceof CDocument) {
                $absPath = $repo->getAbsolutePathForDocument($doc);
            }
        }

        return [
            'id' => (int) $document->source_id,
            'contenthash' => $contenthash,
            'contextid' => (int) $document->source_id,
            'component' => 'mod_resource',
            'filearea' => 'content',
            'itemid' => (int) $document->source_id,
            'filepath' => '/',
            'documentpath' => (string) $document->path,
            'filename' => basename($document->path),
            'userid' => $adminId,
            'filesize' => (int) $document->size,
            'mimetype' => $mimetype,
            'status' => 0,
            'timecreated' => time() - 3600,
            'timemodified' => time(),
            'source' => (string) $document->title,
            'author' => 'Unknown',
            'license' => 'allrightsreserved',
            // New: absolute path for reliable copy
            'abs_path' => $absPath,
        ];
    }

    /**
     * Get file data for files inside a folder.
     *
     * @param array<string,mixed> $file
     *
     * @return array<string,mixed>
     */
    private function getFolderFileData(array $file, int $sourceId, string $parentPath = '/Documents/'): array
    {
        $adminData = MoodleExport::getAdminUserData();
        $adminId = $adminData['id'] ?? 0;

        $contenthash = hash('sha1', basename($file['path']));
        $mimetype = $this->getMimeType($file['path']);
        $filename = basename($file['path']);
        $filepath = $this->ensureTrailingSlash($parentPath);

        return [
            'id' => (int) $file['id'],
            'contenthash' => $contenthash,
            'contextid' => $sourceId,
            'component' => 'mod_folder',
            'filearea' => 'content',
            'itemid' => (int) $file['id'],
            'filepath' => $filepath,
            'documentpath' => 'document/'.$file['path'],
            'filename' => $filename,
            'userid' => $adminId,
            'filesize' => (int) $file['size'],
            'mimetype' => $mimetype,
            'status' => 0,
            'timecreated' => time() - 3600,
            'timemodified' => time(),
            'source' => (string) $file['title'],
            'author' => 'Unknown',
            'license' => 'allrightsreserved',
            'abs_path' => $file['abs_path'] ?? null,
        ];
    }

    /**
     * Ensure the directory path has a trailing slash.
     */
    private function ensureTrailingSlash(string $path): string
    {
        if ('' === $path || '.' === $path || '/' === $path) {
            return '/';
        }

        $path = (string) preg_replace('/\/+/', '/', $path);

        return rtrim($path, '/').'/';
    }

    /**
     * Get MIME type based on the file extension.
     */
    public function getMimeType(string $filePath): string
    {
        $extension = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = $this->getMimeTypes();

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Get an array of file extensions and their corresponding MIME types.
     *
     * @return array<string,string>
     */
    private function getMimeTypes(): array
    {
        return [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'html' => 'text/html',
            'htm' => 'text/html',
            'txt' => 'text/plain',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
        ];
    }
}
