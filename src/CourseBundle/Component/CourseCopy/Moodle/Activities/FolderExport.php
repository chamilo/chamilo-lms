<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use const PATHINFO_EXTENSION;
use const PHP_EOL;

/**
 * Handles the export of folders within a course.
 */
class FolderExport extends ActivityExport
{
    /**
     * Export a folder to the specified directory.
     *
     * @param int    $activityId the ID of the folder (0 means virtual "Documents" root)
     * @param string $exportDir  destination base directory of the export
     * @param int    $moduleId   module id used to name the activity folder
     * @param int    $sectionId  moodle section id where the activity will live
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        // Prepare destination directory for the activity
        $folderDir = $this->prepareActivityDirectory($exportDir, 'folder', (int) $moduleId);

        // Gather data
        $folderData = $this->getData((int) $activityId, (int) $sectionId);

        // Generate activity files
        $this->createFolderXml($folderData, $folderDir);
        $this->createModuleXml($folderData, $folderDir);
        $this->createGradesXml($folderData, $folderDir);
        $this->createFiltersXml($folderData, $folderDir);
        $this->createGradeHistoryXml($folderData, $folderDir);
        $this->createInforefXml($this->getFilesForFolder((int) $activityId), $folderDir);
        $this->createRolesXml($folderData, $folderDir);
        $this->createCommentsXml($folderData, $folderDir);
        $this->createCalendarXml($folderData, $folderDir);
    }

    /**
     * Build the data structure consumed by the XML builders.
     */
    public function getData(int $folderId, int $sectionId): ?array
    {
        // Virtual "Documents" folder at section level
        if (0 === $folderId) {
            return [
                'id' => 0,
                'moduleid' => 0,
                'modulename' => 'folder',
                'contextid' => 0,
                'name' => 'Documents',
                'sectionid' => $sectionId,
                'sectionnumber' => 0,
                'timemodified' => time(),
                'users' => [],
                'files' => [],
            ];
        }

        // Real folder coming from course resources
        $folder = $this->course->resources[RESOURCE_DOCUMENT][$folderId] ?? null;
        if (null === $folder) {
            return null;
        }

        return [
            'id' => $folderId,
            'moduleid' => (int) $folder->source_id,
            'modulename' => 'folder',
            'contextid' => (int) $folder->source_id,
            'name' => (string) $folder->title,
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
     * For folderId=0 we include all root-level documents (Documents root).
     *
     * @return array{users: array<int>, files: array<int,array<string,int|string>>}
     */
    private function getFilesForFolder(int $folderId): array
    {
        $files = [];

        if (0 === $folderId) {
            $docBucket = $this->course->resources[RESOURCE_DOCUMENT] ?? [];
            foreach ($docBucket as $doc) {
                if (($doc->file_type ?? '') === 'file') {
                    $files[] = [
                        'id' => (int) $doc->source_id,
                        'contenthash' => hash('sha1', basename((string) $doc->path)),
                        'filename' => basename((string) $doc->path),
                        'filepath' => '/Documents/',
                        'filesize' => (int) $doc->size,
                        'mimetype' => $this->getMimeType((string) $doc->path),
                    ];
                }
            }
        }

        return ['users' => [], 'files' => $files];
    }

    /**
     * Basic mimetype resolver for common extensions.
     */
    private function getMimeType(string $filename): string
    {
        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        $mimetypes = [
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            'html' => 'text/html',
        ];

        return $mimetypes[$ext] ?? 'application/octet-stream';
    }
}
