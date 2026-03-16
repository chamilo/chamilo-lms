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
        $resourceDir = $this->prepareActivityDirectory($exportDir, 'resource', (int) $moduleId);
        $resourceData = $this->getData((int) $activityId, (int) $sectionId, (int) $moduleId);

        if (empty($resourceData)) {
            return;
        }

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
        if (empty($this->course->resources[RESOURCE_DOCUMENT][$resourceId])) {
            return [];
        }

        $resource = $this->course->resources[RESOURCE_DOCUMENT][$resourceId];

        $name = (string) ($resource->title ?? '');
        if ($sectionId > 0) {
            $name = $this->lpItemTitle($sectionId, RESOURCE_DOCUMENT, $resourceId, $name);
        }
        $name = $this->sanitizeMoodleActivityName($name, 255);

        $effectiveModuleId = (int) ($moduleId ?? $resource->source_id);
        if ($effectiveModuleId <= 0) {
            $effectiveModuleId = (int) $resource->source_id;
        }

        $resourceFile = $this->buildResourceFileEntry($resource, $effectiveModuleId);

        $introResult = $this->extractEmbeddedFilesAndNormalizeContent(
            (string) ($resource->comment ?? ''),
            $effectiveModuleId,
            'mod_resource',
            'intro',
            0,
            fn (int $sequence): int => $this->buildResourceIntroFileId($effectiveModuleId, $sequence)
        );

        return [
            'id' => $resourceId,
            'moduleid' => $effectiveModuleId,
            'modulename' => 'resource',
            'contextid' => $effectiveModuleId,
            'name' => $name,
            'intro' => $introResult['content'],
            'sectionid' => $sectionId,
            'sectionnumber' => 1,
            'timemodified' => time(),
            'users' => [],
            'files' => array_merge([$resourceFile], $introResult['files']),
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
     * Build the files.xml entry for a resource activity file.
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
     * Build a stable file id for mod_resource main file entries.
     */
    private function buildResourceFileId(int $moduleId, int $resourceId): int
    {
        $base = $moduleId > 0 ? $moduleId : $resourceId;

        return 1000000000 + $base;
    }

    /**
     * Build a stable file id for embedded intro files in mod_resource.
     */
    private function buildResourceIntroFileId(int $moduleId, int $sequence): int
    {
        return 1250000000 + max(0, $moduleId) + max(1, $sequence);
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
        $xmlContent .= '    <name>'.htmlspecialchars((string) $resourceData['name']).'</name>'.PHP_EOL;
        $xmlContent .= '    <intro><![CDATA['.(string) $resourceData['intro'].']]></intro>'.PHP_EOL;
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
