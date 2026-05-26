<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleExport;
use Chamilo\CourseBundle\Entity\CDocument;

use const PATHINFO_EXTENSION;
use const PHP_EOL;

/**
 * Handles the export of file resources within a course.
 *
 * Important:
 * - A Moodle resource keeps its own file owner: mod_resource/content
 * - It must not be rewritten later to mod_folder
 */
class ResourceExport extends ActivityExport
{
    /**
     * Export a resource.
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        $resourceDir = $this->prepareActivityDirectory((string) $exportDir, 'resource', (int) $moduleId);

        $resourceData = $this->getData((int) $activityId, (int) $sectionId, (int) $moduleId);

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
     *
     * @return array<string,mixed>
     */
    public function getData(int $resourceId, int $sectionId, ?int $moduleId = null): array
    {
        $docBucket =
            $this->course->resources[\defined('RESOURCE_DOCUMENT') ? RESOURCE_DOCUMENT : 'document']
            ?? $this->course->resources['document']
            ?? [];

        $resource = $docBucket[$resourceId] ?? null;

        if (null === $resource) {
            return [
                'id' => $resourceId,
                'moduleid' => $resourceId,
                'modulename' => 'resource',
                'contextid' => 0,
                'name' => 'Resource '.$resourceId,
                'intro' => '',
                'sectionid' => $sectionId,
                'sectionnumber' => 1,
                'timemodified' => time(),
                'users' => [],
                'files' => [],
            ];
        }

        $effectiveModuleId = (int) ($moduleId ?? ($resource->source_id ?? 0));
        if ($effectiveModuleId <= 0) {
            $effectiveModuleId = (int) ($resource->source_id ?? 0);
        }

        $name = (string) ($resource->title ?? ('Resource '.$resourceId));
        if ($sectionId > 0) {
            $name = $this->lpItemTitle(
                $sectionId,
                \defined('RESOURCE_DOCUMENT') ? (string) RESOURCE_DOCUMENT : 'document',
                $resourceId,
                $name
            );
        }
        $name = $this->sanitizeMoodleActivityName($name, 255);

        $resourceFile = $this->buildResourceFileEntry($resource, $effectiveModuleId);

        return [
            'id' => (int) ($resource->source_id ?? $resourceId),
            'moduleid' => $effectiveModuleId,
            'modulename' => 'resource',
            'contextid' => $effectiveModuleId,
            'name' => $name,
            'intro' => (string) ($resource->comment ?? ''),
            'sectionid' => $sectionId,
            'sectionnumber' => 1,
            'timemodified' => time(),
            'users' => [],
            'files' => [$resourceFile],
        ];
    }

    /**
     * Direct inforef using file ids.
     *
     * @param array<string,mixed> $references
     */
    protected function createInforefXml(array $references, string $directory): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<inforef>'.PHP_EOL;

        if (!empty($references['files']) && \is_array($references['files'])) {
            $xmlContent .= '  <fileref>'.PHP_EOL;

            foreach ($references['files'] as $file) {
                $fileId = \is_array($file) ? (int) ($file['id'] ?? 0) : (int) $file;
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
     *
     * @return array<string,mixed>
     */
    private function buildResourceFileEntry(object $resource, int $moduleId): array
    {
        $adminData = MoodleExport::getAdminUserData();
        $adminId = (int) ($adminData['id'] ?? 1);

        $documentPath = (string) ($resource->path ?? '');
        $absolutePath = $this->resolveDocumentAbsolutePath((int) ($resource->source_id ?? 0), $documentPath);
        $filename = basename($documentPath);

        $contenthash = hash('sha1', $filename);
        if (is_file((string) $absolutePath)) {
            $contenthash = sha1_file((string) $absolutePath);
        }

        return [
            'id' => $this->buildResourceFileId($moduleId, (int) ($resource->source_id ?? 0)),
            'contenthash' => $contenthash,
            'contextid' => $moduleId,
            'component' => 'mod_resource',
            'filearea' => 'content',
            'itemid' => 0,
            'filepath' => '/',
            'documentpath' => ltrim($documentPath, '/'),
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
            'abs_path' => $absolutePath,
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
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
        ];

        return $map[$ext] ?? 'application/octet-stream';
    }

    /**
     * Create resource.xml.
     *
     * @param array<string,mixed> $resourceData
     */
    private function createResourceXml(array $resourceData, string $resourceDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<activity id="'.$resourceData['id'].'" moduleid="'.$resourceData['moduleid'].'" modulename="resource" contextid="'.$resourceData['contextid'].'">'.PHP_EOL;
        $xmlContent .= '  <resource id="'.$resourceData['id'].'">'.PHP_EOL;
        $xmlContent .= '    <name>'.htmlspecialchars((string) $resourceData['name']).'</name>'.PHP_EOL;
        $xmlContent .= '    <intro>'.htmlspecialchars((string) $resourceData['intro']).'</intro>'.PHP_EOL;
        $xmlContent .= '    <introformat>1</introformat>'.PHP_EOL;
        $xmlContent .= '    <tobemigrated>0</tobemigrated>'.PHP_EOL;
        $xmlContent .= '    <legacyfiles>0</legacyfiles>'.PHP_EOL;
        $xmlContent .= '    <legacyfileslast>$@NULL@$</legacyfileslast>'.PHP_EOL;
        $xmlContent .= '    <display>0</display>'.PHP_EOL;
        $xmlContent .= '    <displayoptions>a:1:{s:10:"printintro";i:1;}</displayoptions>'.PHP_EOL;
        $xmlContent .= '    <filterfiles>0</filterfiles>'.PHP_EOL;
        $xmlContent .= '    <revision>1</revision>'.PHP_EOL;
        $xmlContent .= '    <timemodified>'.(int) $resourceData['timemodified'].'</timemodified>'.PHP_EOL;
        $xmlContent .= '  </resource>'.PHP_EOL;
        $xmlContent .= '</activity>'.PHP_EOL;

        $this->createXmlFile('resource', $xmlContent, $resourceDir);
    }

    /**
     * Resolve the absolute document path.
     */
    private function resolveDocumentAbsolutePath(int $documentId, string $documentPath): ?string
    {
        if ($documentId > 0 && class_exists(Container::class)) {
            try {
                $repo = Container::getDocumentRepository();
                $doc = $repo->findOneBy(['iid' => $documentId]);
                if ($doc instanceof CDocument) {
                    $absPath = $repo->getAbsolutePathForDocument($doc);
                    if (is_file((string) $absPath)) {
                        return (string) $absPath;
                    }
                }
            } catch (\Throwable $e) {
                @error_log('[ResourceExport::resolveDocumentAbsolutePath] '.$e->getMessage());
            }
        }

        $basePath = rtrim((string) $this->course->path, '/');
        $relative = ltrim($documentPath, '/');
        $fallback = $basePath.'/'.$relative;

        return is_file($fallback) ? $fallback : null;
    }
}
