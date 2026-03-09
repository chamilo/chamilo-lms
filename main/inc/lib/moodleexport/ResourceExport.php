<?php

/* For licensing terms, see /license.txt */

namespace moodleexport;

/**
 * Class ResourceExport.
 *
 * Handles the export of resources within a course.
 */
class ResourceExport extends ActivityExport
{
    /**
     * Export a resource to the specified directory.
     *
     * @param int    $activityId The ID of the resource.
     * @param string $exportDir  The directory where the resource will be exported.
     * @param int    $moduleId   The ID of the module.
     * @param int    $sectionId  The ID of the section.
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        // Prepare the directory where the resource export will be saved
        $resourceDir = $this->prepareActivityDirectory($exportDir, 'resource', $moduleId);

        // Retrieve resource data (must use the actual exported module id)
        $resourceData = $this->getData((int) $activityId, (int) $sectionId, (int) $moduleId);

        // Generate XML files
        $this->createResourceXml($resourceData, $resourceDir);
        $this->createModuleXml($resourceData, $resourceDir);
        $this->createGradesXml($resourceData, $resourceDir);
        $this->createFiltersXml($resourceData, $resourceDir);
        $this->createGradeHistoryXml($resourceData, $resourceDir);
        $this->createInforefXml($resourceData, $resourceDir);
        $this->createRolesXml($resourceData, $resourceDir);
        $this->createCommentsXml($resourceData, $resourceDir);
        $this->createCalendarXml($resourceData, $resourceDir);
    }

    /**
     * Get resource data dynamically from the course.
     */
    public function getData(int $resourceId, int $sectionId, ?int $moduleId = null): array
    {
        $resource = $this->course->resources[RESOURCE_DOCUMENT][$resourceId];

        $name = (string) ($resource->title ?? '');
        if ($sectionId > 0) {
            $name = $this->lpItemTitle($sectionId, RESOURCE_DOCUMENT, $resourceId, $name);
        }

        // Moodle stores resource.name in VARCHAR(255). Strip HTML and truncate safely.
        $name = $this->sanitizeMoodleActivityName($name, 255);

        $effectiveModuleId = (int) ($moduleId ?? $resource->source_id);
        if ($effectiveModuleId <= 0) {
            $effectiveModuleId = (int) $resource->source_id;
        }

        $resourceFile = $this->buildResourceFileEntry($resource, $effectiveModuleId);

        return [
            'id' => $resourceId,
            'moduleid' => $effectiveModuleId,
            'modulename' => 'resource',
            'contextid' => $effectiveModuleId,
            'name' => $name,
            'intro' => $resource->comment ?? '',
            'sectionid' => $sectionId,
            'sectionnumber' => 1,
            'timemodified' => time(),
            'users' => [],
            'files' => [$resourceFile],
        ];
    }

    /**
     * Creates the inforef.xml file, referencing users and files associated with the activity.
     *
     * @param array  $references Contains 'users' and 'files' arrays to reference in the XML.
     * @param string $directory  The directory where the XML file will be saved.
     */
    protected function createInforefXml(array $references, string $directory): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<inforef>'.PHP_EOL;

        if (!empty($references['files']) && is_array($references['files'])) {
            $xmlContent .= '  <fileref>'.PHP_EOL;

            foreach ($references['files'] as $file) {
                $fileId = is_array($file) ? (int) ($file['id'] ?? 0) : (int) $file;
                if ($fileId <= 0) {
                    continue;
                }

                $xmlContent .= '    <file>'.PHP_EOL;
                $xmlContent .= '      <id>'.$fileId.'</id>'.PHP_EOL;
                $xmlContent .= '    </file>'.PHP_EOL;
            }

            $xmlContent .= '  </fileref>'.PHP_EOL;
        }

        $xmlContent .= '</inforef>'.PHP_EOL;

        $this->createXmlFile('inforef', $xmlContent, $directory);
    }

    /**
     * Build the files.xml entry for a resource activity file (mod_resource).
     */
    private function buildResourceFileEntry(object $resource, int $moduleId): array
    {
        $adminData = MoodleExport::getAdminUserData();
        $adminId = (int) ($adminData['id'] ?? 1);

        $documentPath = (string) $resource->path;
        $absolutePath = $this->course->path.$documentPath;

        $filename = basename($documentPath);
        $contenthash = is_file($absolutePath)
            ? sha1_file($absolutePath)
            : hash('sha1', $filename);

        return [
            // Use a dedicated range to avoid collisions with folder/page file ids
            'id' => $this->buildResourceFileId($moduleId, (int) $resource->source_id),
            'contenthash' => $contenthash,
            'contextid' => $moduleId,
            'component' => 'mod_resource',
            'filearea' => 'content',
            'itemid' => 0,
            'filepath' => '/',
            'documentpath' => $documentPath,
            'filename' => $filename,
            'userid' => $adminId,
            'filesize' => (int) ($resource->size ?? 0),
            'mimetype' => $this->guessMimeType($documentPath),
            'status' => 0,
            'timecreated' => time() - 3600,
            'timemodified' => time(),
            'source' => (string) ($resource->title ?? $filename),
            'author' => 'Unknown',
            'license' => 'allrightsreserved',
        ];
    }

    /**
     * Build a stable file id for mod_resource entries.
     */
    private function buildResourceFileId(int $moduleId, int $resourceId): int
    {
        $base = $moduleId > 0 ? $moduleId : $resourceId;

        return 1000000000 + $base;
    }

    /**
     * Guess MIME type from file extension.
     */
    private function guessMimeType(string $filePath): string
    {
        $ext = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));

        $map = [
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

        return $map[$ext] ?? 'application/octet-stream';
    }

    /**
     * Create the XML file for the resource.
     */
    private function createResourceXml(array $resourceData, string $resourceDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<activity id="'.$resourceData['id'].'" moduleid="'.$resourceData['moduleid'].'" modulename="resource" contextid="'.$resourceData['contextid'].'">'.PHP_EOL;
        $xmlContent .= '  <resource id="'.$resourceData['id'].'">'.PHP_EOL;
        $xmlContent .= '    <name>'.htmlspecialchars($resourceData['name']).'</name>'.PHP_EOL;
        $xmlContent .= '    <intro>'.htmlspecialchars($resourceData['intro']).'</intro>'.PHP_EOL;
        $xmlContent .= '    <introformat>1</introformat>'.PHP_EOL;
        $xmlContent .= '    <tobemigrated>0</tobemigrated>'.PHP_EOL;
        $xmlContent .= '    <legacyfiles>0</legacyfiles>'.PHP_EOL;
        $xmlContent .= '    <legacyfileslast>$@NULL@$</legacyfileslast>'.PHP_EOL;
        $xmlContent .= '    <display>0</display>'.PHP_EOL;
        $xmlContent .= '    <displayoptions>a:1:{s:10:"printintro";i:1;}</displayoptions>'.PHP_EOL;
        $xmlContent .= '    <filterfiles>0</filterfiles>'.PHP_EOL;
        $xmlContent .= '    <revision>1</revision>'.PHP_EOL;
        $xmlContent .= '    <timemodified>'.$resourceData['timemodified'].'</timemodified>'.PHP_EOL;
        $xmlContent .= '  </resource>'.PHP_EOL;
        $xmlContent .= '</activity>'.PHP_EOL;

        $this->createXmlFile('resource', $xmlContent, $resourceDir);
    }
}
