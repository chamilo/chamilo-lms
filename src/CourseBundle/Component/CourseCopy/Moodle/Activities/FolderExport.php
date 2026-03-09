<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use const PATHINFO_EXTENSION;
use const PHP_EOL;

/**
 * Handles the export of folder activities within a course.
 */
class FolderExport extends ActivityExport
{
    /**
     * Export a folder to the specified directory.
     *
     * folderId = 0 or DOCS_MODULE_ID means the virtual root "Documents" folder.
     */
    public function export(int $activityId, string $exportDir, int $moduleId, int $sectionId): void
    {
        $normalizedActivityId = $activityId === self::DOCS_MODULE_ID ? 0 : $activityId;
        $folderDir = $this->prepareActivityDirectory($exportDir, 'folder', $moduleId);
        @error_log('[FolderExport::export] Activity dir='.$folderDir);

        $folderData = $this->getData($normalizedActivityId, $sectionId, $moduleId);
        if (!$folderData) {
            @error_log('[FolderExport::export] Error: getData returned null for activityId='.$activityId);
            return;
        }

        $references = $this->getFilesForFolder($normalizedActivityId);
        $this->createFolderXml($folderData, $folderDir);
        $this->createModuleXml($folderData, $folderDir);
        $this->createGradesXml($folderData, $folderDir);
        $this->createFiltersXml($folderData, $folderDir);
        $this->createGradeHistoryXml($folderData, $folderDir);
        $this->createInforefXml($references, $folderDir);
        $this->createRolesXml($folderData, $folderDir);
        $this->createCommentsXml($folderData, $folderDir);
        $this->createCalendarXml($folderData, $folderDir);
    }

    /**
     * Build the data structure consumed by the XML builders.
     */
    public function getData(int $folderId, int $sectionId, ?int $moduleId = null): ?array
    {
        if (0 === $folderId || self::DOCS_MODULE_ID === $folderId) {
            $effectiveModuleId = ($moduleId && $moduleId > 0)
                ? $moduleId
                : self::DOCS_MODULE_ID;

            return [
                'id' => self::DOCS_MODULE_ID,
                'moduleid' => $effectiveModuleId,
                'modulename' => 'folder',
                'contextid' => $effectiveModuleId,
                'name' => 'Documents',
                'sectionid' => $sectionId,
                'sectionnumber' => 0,
                'timemodified' => time(),
                'users' => [],
                'files' => [],
            ];
        }

        $documents = $this->getDocumentBucket();
        $folder = $documents[$folderId] ?? null;

        if (null === $folder) {
            return null;
        }

        $effectiveModuleId = ($moduleId && $moduleId > 0)
            ? $moduleId
            : (int) ($folder->source_id ?? $folderId);

        return [
            'id' => $folderId,
            'moduleid' => $effectiveModuleId,
            'modulename' => 'folder',
            'contextid' => $effectiveModuleId,
            'name' => (string) ($folder->title ?? 'Folder'),
            'sectionid' => $sectionId,
            'sectionnumber' => 0,
            'timemodified' => time(),
            'users' => [],
            'files' => [],
        ];
    }

    /**
     * Write folder.xml for the activity.
     */
    private function createFolderXml(array $folderData, string $folderDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<activity id="'.$folderData['id'].'" moduleid="'.$folderData['moduleid'].'" modulename="folder" contextid="'.$folderData['contextid'].'">'.PHP_EOL;
        $xmlContent .= '  <folder id="'.$folderData['id'].'">'.PHP_EOL;
        $xmlContent .= '    <name>'.htmlspecialchars((string) $folderData['name']).'</name>'.PHP_EOL;
        $xmlContent .= '    <intro></intro>'.PHP_EOL;
        $xmlContent .= '    <introformat>1</introformat>'.PHP_EOL;
        $xmlContent .= '    <revision>1</revision>'.PHP_EOL;
        $xmlContent .= '    <timemodified>'.$folderData['timemodified'].'</timemodified>'.PHP_EOL;
        $xmlContent .= '    <display>0</display>'.PHP_EOL;
        $xmlContent .= '    <showexpanded>1</showexpanded>'.PHP_EOL;
        $xmlContent .= '    <showdownloadfolder>1</showdownloadfolder>'.PHP_EOL;
        $xmlContent .= '    <forcedownload>1</forcedownload>'.PHP_EOL;
        $xmlContent .= '  </folder>'.PHP_EOL;
        $xmlContent .= '</activity>';

        $this->createXmlFile('folder', $xmlContent, $folderDir);
    }

    /**
     * List files included under the exported folder.
     *
     * For the virtual root folder, include every file document in the course document tree.
     * For a real folder document, include every file under that folder path.
     *
     * @return array{users: array<int>, files: array<int,array<string,int|string>>}
     */
    private function getFilesForFolder(int $folderId): array
    {
        $files = [];
        $documents = $this->getDocumentBucket();

        if (0 === $folderId || self::DOCS_MODULE_ID === $folderId) {
            foreach ($documents as $document) {
                if (($document->file_type ?? '') !== 'file') {
                    continue;
                }

                $documentPath = (string) ($document->path ?? '');

                $files[] = [
                    'id' => (int) ($document->source_id ?? 0),
                    'contenthash' => $this->buildContentHashForDocument($document),
                    'filename' => basename($documentPath),
                    'filepath' => $this->buildFolderFilepathFromDocumentPath($documentPath),
                    'filesize' => (int) ($document->size ?? 0),
                    'mimetype' => $this->getMimeType($documentPath),
                ];
            }

            return [
                'users' => [],
                'files' => $files,
            ];
        }

        $folder = $documents[$folderId] ?? null;
        $folderPath = rtrim((string) ($folder->path ?? ''), '/');

        if ('' === $folderPath) {
            return [
                'users' => [],
                'files' => [],
            ];
        }

        foreach ($documents as $document) {
            if (($document->file_type ?? '') !== 'file') {
                continue;
            }

            $documentPath = (string) ($document->path ?? '');
            if (strpos($documentPath, $folderPath.'/') !== 0) {
                continue;
            }

            $files[] = [
                'id' => (int) ($document->source_id ?? 0),
                'contenthash' => $this->buildContentHashForDocument($document),
                'filename' => basename($documentPath),
                'filepath' => $this->buildFolderFilepathFromDocumentPath($documentPath),
                'filesize' => (int) ($document->size ?? 0),
                'mimetype' => $this->getMimeType($documentPath),
            ];
        }

        return [
            'users' => [],
            'files' => $files,
        ];
    }

    /**
     * Resolve the document bucket defensively.
     *
     * @return array<int,mixed>
     */
    private function getDocumentBucket(): array
    {
        $documents =
            $this->course->resources[defined('RESOURCE_DOCUMENT') ? RESOURCE_DOCUMENT : 'document']
            ?? $this->course->resources['document']
            ?? [];

        return is_array($documents) ? $documents : [];
    }

    /**
     * Build the folder filepath as exported in files.xml.
     */
    private function buildFolderFilepathFromDocumentPath(string $documentPath): string
    {
        $normalizedPath = $this->stripChamiloDocumentPrefix($documentPath);
        $normalizedPath = ltrim(str_replace('\\', '/', $normalizedPath), '/');

        $relativeDir = dirname($normalizedPath);
        if ('.' === $relativeDir || '/' === $relativeDir) {
            return '/';
        }

        return '/'.trim($relativeDir, '/').'/';
    }

    /**
     * Remove a leading "document/" prefix when present.
     */
    private function stripChamiloDocumentPrefix(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('#^/?document/#', '', $path);

        return (string) $path;
    }

    /**
     * Build a contenthash compatible with the current exported file set.
     * Prefer the real file SHA1 when the file exists.
     */
    private function buildContentHashForDocument(object $document): string
    {
        $documentPath = (string) ($document->path ?? '');

        foreach ($this->buildAbsoluteDocumentCandidates($documentPath) as $absolutePath) {
            if (is_file($absolutePath)) {
                return (string) sha1_file($absolutePath);
            }
        }

        return hash('sha1', ltrim(str_replace('\\', '/', $documentPath), '/'));
    }

    /**
     * Try a few possible absolute paths for the same logical course document.
     *
     * @return array<int,string>
     */
    private function buildAbsoluteDocumentCandidates(string $documentPath): array
    {
        $basePath = rtrim((string) ($this->course->path ?? ''), '/');
        $normalized = ltrim(str_replace('\\', '/', $documentPath), '/');

        $candidates = [
            $basePath.'/'.$normalized,
            $basePath.'/document/'.$normalized,
            $basePath.$documentPath,
        ];

        return array_values(array_unique(array_filter($candidates)));
    }

    /**
     * Basic MIME type resolver for common extensions.
     */
    private function getMimeType(string $filename): string
    {
        $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            'html' => 'text/html',
            'htm' => 'text/html',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}
