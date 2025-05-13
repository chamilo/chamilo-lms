<?php

/* For licensing terms, see /license.txt */

namespace moodleexport;

/**
 * Class FolderExport.
 *
 * Handles the export of folders within a course.
 */
class FolderExport extends ActivityExport
{
    /**
     * Export a folder to the specified directory.
     *
     * @param int    $activityId The ID of the folder.
     * @param string $exportDir  The directory where the folder will be exported.
     * @param int    $moduleId   The ID of the module.
     * @param int    $sectionId  The ID of the section.
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        // Prepare the directory where the folder export will be saved
        $folderDir = $this->prepareActivityDirectory($exportDir, 'folder', $moduleId);

        // Retrieve folder data
        $folderData = $this->getData($activityId, $sectionId);

        // Generate XML files
        $this->createFolderXml($folderData, $folderDir);
        $this->createModuleXml($folderData, $folderDir);
        $this->createGradesXml($folderData, $folderDir);
        $this->createFiltersXml($folderData, $folderDir);
        $this->createGradeHistoryXml($folderData, $folderDir);
        $this->createInforefXml($this->getFilesForFolder($activityId), $folderDir);
        $this->createRolesXml($folderData, $folderDir);
        $this->createCommentsXml($folderData, $folderDir);
        $this->createCalendarXml($folderData, $folderDir);
    }

    /**
     * Get folder data dynamically from the course.
     */
    public function getData(int $folderId, int $sectionId): ?array
    {
        if ($folderId === 0) {
            return [
                'id' => 0,
                'moduleid' => 0,
                'modulename' => 'folder',
                'contextid' => 0,
                'name' => 'Documents',
                'sectionid' => $sectionId,
                'timemodified' => time(),
            ];
        }

        $folder = $this->course->resources['document'][$folderId];

        return [
            'id' => $folderId,
            'moduleid' => $folder->source_id,
            'modulename' => 'folder',
            'contextid' => $folder->source_id,
            'name' => $folder->title,
            'sectionid' => $sectionId,
            'timemodified' => time(),
        ];
    }

    /**
     * Create the XML file for the folder.
     */
    private function createFolderXml(array $folderData, string $folderDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<activity id="'.$folderData['id'].'" moduleid="'.$folderData['moduleid'].'" modulename="folder" contextid="'.$folderData['contextid'].'">'.PHP_EOL;
        $xmlContent .= '  <folder id="'.$folderData['id'].'">'.PHP_EOL;
        $xmlContent .= '    <name>'.htmlspecialchars($folderData['name']).'</name>'.PHP_EOL;
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
     * Get the list of files for a folder.
     */
    private function getFilesForFolder(int $folderId): array
    {
        $files = [];
        if ($folderId === 0) {
            foreach ($this->course->resources[RESOURCE_DOCUMENT] as $doc) {
                if ($doc->file_type === 'file') {
                    $files[] = [
                        'id' => (int) $doc->source_id,
                        'contenthash' => hash('sha1', basename($doc->path)),
                        'filename' => basename($doc->path),
                        'filepath' => '/Documents/',
                        'filesize' => (int) $doc->size,
                        'mimetype' => $this->getMimeType($doc->path),
                    ];
                }
            }
        }

        return ['files' => $files];
    }

    /**
     * Get the MIME type for a given file.
     */
    private function getMimeType(string $filename): string
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $mimetypes = [
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        return $mimetypes[$ext] ?? 'application/octet-stream';
    }
}
