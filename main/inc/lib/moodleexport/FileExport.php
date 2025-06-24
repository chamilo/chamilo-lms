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

        // Create placeholder index.html
        $this->createPlaceholderFile($filesDir);

        // Export each file
        foreach ($filesData['files'] as $file) {
            $this->copyFileToExportDir($file, $filesDir);
        }

        // Create files.xml in the export directory
        $this->createFilesXml($filesData, $exportDir);
    }

    /**
     * Get file data from course resources. This is for testing purposes.
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
        if ($file['filepath'] === '.') {
            return;
        }

        $contenthash = $file['contenthash'];
        $subDir = substr($contenthash, 0, 2);
        $filePath = $this->course->path.$file['documentpath'];
        $exportSubDir = $filesDir.'/'.$subDir;

        // Ensure the subdirectory exists
        if (!is_dir($exportSubDir)) {
            mkdir($exportSubDir, api_get_permissions_for_new_directories(), true);
        }

        // Copy the file to the export directory
        $destinationFile = $exportSubDir.'/'.$contenthash;
        if (file_exists($filePath)) {
            copy($filePath, $destinationFile);
        } else {
            throw new Exception("File {$filePath} not found.");
        }
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
     * Create an XML entry for a file.
     */
    private function createFileXmlEntry(array $file): string
    {
        return '  <file id="'.$file['id'].'">'.PHP_EOL.
            '    <contenthash>'.htmlspecialchars($file['contenthash']).'</contenthash>'.PHP_EOL.
            '    <contextid>'.$file['contextid'].'</contextid>'.PHP_EOL.
            '    <component>'.htmlspecialchars($file['component']).'</component>'.PHP_EOL.
            '    <filearea>'.htmlspecialchars($file['filearea']).'</filearea>'.PHP_EOL.
            '    <itemid>0</itemid>'.PHP_EOL.
            '    <filepath>'.htmlspecialchars($file['filepath']).'</filepath>'.PHP_EOL.
            '    <filename>'.htmlspecialchars($file['filename']).'</filename>'.PHP_EOL.
            '    <userid>'.$file['userid'].'</userid>'.PHP_EOL.
            '    <filesize>'.$file['filesize'].'</filesize>'.PHP_EOL.
            '    <mimetype>'.htmlspecialchars($file['mimetype']).'</mimetype>'.PHP_EOL.
            '    <status>'.$file['status'].'</status>'.PHP_EOL.
            '    <timecreated>'.$file['timecreated'].'</timecreated>'.PHP_EOL.
            '    <timemodified>'.$file['timemodified'].'</timemodified>'.PHP_EOL.
            '    <source>'.htmlspecialchars($file['source']).'</source>'.PHP_EOL.
            '    <author>'.htmlspecialchars($file['author']).'</author>'.PHP_EOL.
            '    <license>'.htmlspecialchars($file['license']).'</license>'.PHP_EOL.
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
        if (
            $document->file_type === 'file' &&
            isset($this->course->used_page_doc_ids) &&
            in_array($document->source_id, $this->course->used_page_doc_ids)
        ) {
            return $filesData;
        }

        if (
            $document->file_type === 'file' &&
            pathinfo($document->path, PATHINFO_EXTENSION) === 'html' &&
            substr_count($document->path, '/') === 1
        ) {
            return $filesData;
        }

        if ($document->file_type === 'file') {
            $extension = pathinfo($document->path, PATHINFO_EXTENSION);
            if (!in_array(strtolower($extension), ['html', 'htm'])) {
                $fileData = $this->getFileData($document);
                $fileData['filepath'] = '/Documents/';
                $fileData['contextid'] = 0;
                $fileData['component'] = 'mod_folder';
                $filesData['files'][] = $fileData;
            }
        } elseif ($document->file_type === 'folder') {
            $folderFiles = \DocumentManager::getAllDocumentsByParentId($this->course->info, $document->source_id);
            foreach ($folderFiles as $file) {
                $filesData['files'][] = $this->getFolderFileData($file, (int) $document->source_id, '/Documents/'.dirname($file['path']).'/');
            }
        }

        return $filesData;
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
     * Get file data for files inside a folder.
     */
    private function getFolderFileData(array $file, int $sourceId, string $parentPath = '/Documents/'): array
    {
        $adminData = MoodleExport::getAdminUserData();
        $adminId = $adminData['id'];
        $contenthash = hash('sha1', basename($file['path']));
        $mimetype = $this->getMimeType($file['path']);
        $filename = basename($file['path']);
        $filepath = $this->ensureTrailingSlash($parentPath);

        return [
            'id' => $file['id'],
            'contenthash' => $contenthash,
            'contextid' => $sourceId,
            'component' => 'mod_folder',
            'filearea' => 'content',
            'itemid' => (int) $file['id'],
            'filepath' => $filepath,
            'documentpath' => 'document/'.$file['path'],
            'filename' => $filename,
            'userid' => $adminId,
            'filesize' => $file['size'],
            'mimetype' => $mimetype,
            'status' => 0,
            'timecreated' => time() - 3600,
            'timemodified' => time(),
            'source' => $file['title'],
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
     * Get MIME type based on the file extension.
     */
    public function getMimeType($filePath): string
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeTypes = $this->getMimeTypes();

        return $mimeTypes[$extension] ?? 'application/octet-stream';
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
            'html' => 'text/html',
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
