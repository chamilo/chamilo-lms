<?php

/* For licensing terms, see /license.txt */

namespace moodleexport;

use DocumentManager;
use Exception;

/**
 * Class FileExport.
 * Handles the export of files and metadata from Moodle courses.
 *
 * @package moodleexport
 */
class FileExport
{
    private $course;

    /**
     * Constructor to initialize course data.
     *
     * @param object $course Course object containing resources and path data.
     */
    public function __construct($course)
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

        foreach ($filesData['files'] as $file) {
            $this->copyFileToExportDir($file, $filesDir);
        }

        $this->createFilesXml($filesData, $exportDir);
    }

    /**
     * Get file data from course resources.
     */
    public function getFilesData(): array
    {
        $adminData = MoodleExport::getAdminUserData();
        $adminId = $adminData['id'];

        $filesData = ['files' => []];

        foreach ($this->course->resources[RESOURCE_DOCUMENT] as $document) {
            $filesData = $this->processDocument($filesData, $document);
        }

        foreach ($this->course->resources[RESOURCE_WORK] as $work) {
            $workFiles = getAllDocumentToWork($work->params['id'], $this->course->info['real_id']);

            if (!empty($workFiles)) {
                foreach ($workFiles as $file) {
                    $docData = DocumentManager::get_document_data_by_id($file['document_id'], $this->course->info['code']);
                    if (!empty($docData)) {
                        $filesData['files'][] = [
                            'id' => $file['document_id'],
                            'contenthash' => hash('sha1', basename($docData['path'])),
                            'contextid' => $this->course->info['real_id'],
                            'component' => 'mod_assign',
                            'filearea' => 'introattachment',
                            'itemid' => (int) $work->params['id'],
                            'filepath' => '/Documents/',
                            'documentpath' => 'document/'.$docData['path'],
                            'filename' => basename($docData['path']),
                            'userid' => $adminId,
                            'filesize' => $docData['size'],
                            'mimetype' => $this->getMimeType($docData['path']),
                            'status' => 0,
                            'timecreated' => time() - 3600,
                            'timemodified' => time(),
                            'source' => $docData['title'],
                            'author' => 'Unknown',
                            'license' => 'allrightsreserved',
                        ];
                    }
                }
            }
        }

        return $filesData;
    }

    /**
     * Get MIME type based on the file extension.
     */
    public function getMimeType($filePath): string
    {
        $extension = strtolower((string) pathinfo((string) $filePath, PATHINFO_EXTENSION));
        $mimeTypes = $this->getMimeTypes();

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Create a placeholder index.html file to prevent an empty directory.
     */
    private function createPlaceholderFile(string $filesDir): void
    {
        $placeholderFile = $filesDir.'/index.html';
        file_put_contents($placeholderFile, "<!-- Placeholder file to ensure the directory is not empty -->");
    }

    /**
     * Copy a file to the export directory using its contenthash.
     */
    private function copyFileToExportDir(array $file, string $filesDir): void
    {
        if (($file['filepath'] ?? '') === '.') {
            return;
        }

        $contenthash = (string) ($file['contenthash'] ?? '');
        if ($contenthash === '') {
            return;
        }

        $subDir = substr($contenthash, 0, 2);
        $exportSubDir = $filesDir.'/'.$subDir;

        if (!is_dir($exportSubDir)) {
            mkdir($exportSubDir, api_get_permissions_for_new_directories(), true);
        }

        $destinationFile = $exportSubDir.'/'.$contenthash;

        $filePath = '';
        if (!empty($file['absolutepath'])) {
            $filePath = (string) $file['absolutepath'];
        } else {
            $filePath = $this->course->path.($file['documentpath'] ?? '');
        }

        if (is_file($filePath)) {
            copy($filePath, $destinationFile);

            return;
        }

        throw new Exception("File {$filePath} not found.");
    }

    /**
     * Create the files.xml with the provided file data.
     */
    private function createFilesXml(array $filesData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<files>'.PHP_EOL;

        foreach ($filesData['files'] as $file) {
            $xmlContent .= $this->createFileXmlEntry($file);
        }

        $xmlContent .= '</files>'.PHP_EOL;
        file_put_contents($destinationDir.'/files.xml', $xmlContent);
    }

    /**
     * Ensure mandatory file fields exist for Moodle restore.
     */
    private function normalizeFileEntry(array $file): array
    {
        $adminData = MoodleExport::getAdminUserData();
        $adminId = (int) ($adminData['id'] ?? 1);

        if (!isset($file['itemid']) || $file['itemid'] === null || (is_string($file['itemid']) && trim($file['itemid']) === '')) {
            $file['itemid'] = 0;
        } else {
            $file['itemid'] = (int) $file['itemid'];
        }

        if (!isset($file['userid']) || $file['userid'] === null || (is_string($file['userid']) && trim($file['userid']) === '')) {
            $file['userid'] = $adminId;
        } else {
            $file['userid'] = (int) $file['userid'];
        }

        return $file;
    }

    /**
     * Create an XML entry for a file.
     */
    private function createFileXmlEntry(array $file): string
    {
        $file = $this->normalizeFileEntry($file);

        $itemId = (int) $file['itemid'];
        $userId = (int) $file['userid'];

        return '  <file id="'.$file['id'].'">'.PHP_EOL.
            '    <contenthash>'.htmlspecialchars((string) $file['contenthash']).'</contenthash>'.PHP_EOL.
            '    <contextid>'.$file['contextid'].'</contextid>'.PHP_EOL.
            '    <component>'.htmlspecialchars((string) $file['component']).'</component>'.PHP_EOL.
            '    <filearea>'.htmlspecialchars((string) $file['filearea']).'</filearea>'.PHP_EOL.
            '    <itemid>'.$itemId.'</itemid>'.PHP_EOL.
            '    <filepath>'.htmlspecialchars((string) $file['filepath']).'</filepath>'.PHP_EOL.
            '    <filename>'.htmlspecialchars((string) $file['filename']).'</filename>'.PHP_EOL.
            '    <userid>'.$userId.'</userid>'.PHP_EOL.
            '    <filesize>'.$file['filesize'].'</filesize>'.PHP_EOL.
            '    <mimetype>'.htmlspecialchars((string) $file['mimetype']).'</mimetype>'.PHP_EOL.
            '    <status>'.$file['status'].'</status>'.PHP_EOL.
            '    <timecreated>'.$file['timecreated'].'</timecreated>'.PHP_EOL.
            '    <timemodified>'.$file['timemodified'].'</timemodified>'.PHP_EOL.
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
     */
    private function processDocument(array $filesData, object $document): array
    {
        if ($document->file_type !== 'file') {
            return $filesData;
        }

        $fileData = $this->getFileData($document);
        $filepath = $this->buildMoodleFilepathFromChamiloPath((string) $document->path);

        $fileData['filepath'] = $filepath;
        $fileData['contextid'] = ActivityExport::DOCS_MODULE_ID;
        $fileData['component'] = 'mod_folder';
        $fileData['filearea'] = 'content';
        $fileData['itemid'] = ActivityExport::DOCS_MODULE_ID;

        $filesData['files'][] = $fileData;

        return $filesData;
    }

    /**
     * Build a Moodle filepath from a Chamilo document path.
     */
    private function buildMoodleFilepathFromChamiloPath(string $documentPath): string
    {
        $normalizedPath = $this->stripChamiloDocumentPrefix($documentPath);
        $normalizedPath = ltrim(str_replace('\\', '/', $normalizedPath), '/');

        $relDir = dirname($normalizedPath);

        return $this->ensureTrailingSlash($relDir === '.' ? '/' : '/'.$relDir.'/');
    }

    /**
     * Remove the internal Chamilo document prefix from a path.
     */
    private function stripChamiloDocumentPrefix(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('#^/?document/#', '', $path);

        return $path;
    }

    /**
     * Get file data for a single document.
     */
    private function getFileData(object $document): array
    {
        $adminData = MoodleExport::getAdminUserData();
        $adminId = $adminData['id'];
        $contenthash = hash('sha1', basename($document->path));
        $mimetype = $this->getMimeType($document->path);

        return [
            'id' => $document->source_id,
            'contenthash' => $contenthash,
            'contextid' => $document->source_id,
            'component' => 'mod_resource',
            'filearea' => 'content',
            'itemid' => (int) $document->source_id,
            'filepath' => '/',
            'documentpath' => $document->path,
            'filename' => basename($document->path),
            'userid' => $adminId,
            'filesize' => $document->size,
            'mimetype' => $mimetype,
            'status' => 0,
            'timecreated' => time() - 3600,
            'timemodified' => time(),
            'source' => $document->title,
            'author' => 'Unknown',
            'license' => 'allrightsreserved',
        ];
    }

    /**
     * Ensure the directory path has a trailing slash.
     */
    private function ensureTrailingSlash(string $path): string
    {
        if (empty($path) || $path === '.' || $path === '/') {
            return '/';
        }

        $path = preg_replace('/\/+/', '/', $path);

        return rtrim($path, '/').'/';
    }

    /**
     * Get an array of file extensions and their corresponding MIME types.
     */
    private function getMimeTypes(): array
    {
        return [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'html' => 'text/html',
            'htm' => 'text/html',
            'txt' => 'text/plain',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mp3' => 'audio/mpeg',
            'ogg' => 'audio/ogg',
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
