<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use const PHP_EOL;

/**
 * Handles the export of file resources within a course.
 */
class ResourceExport extends ActivityExport
{
    /**
     * Export a resource to the specified directory.
     *
     * @param int    $activityId the ID of the resource
     * @param string $exportDir  the directory where the resource will be exported
     * @param int    $moduleId   the ID of the module
     * @param int    $sectionId  the ID of the section
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        $resourceDir = $this->prepareActivityDirectory((string) $exportDir, 'resource', (int) $moduleId);

        $resourceData = $this->getData((int) $activityId, (int) $sectionId);

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
    public function getData(int $resourceId, int $sectionId): array
    {
        $docBucket = $this->course->resources[RESOURCE_DOCUMENT] ?? [];
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

        return [
            'id' => (int) $resourceId,
            'moduleid' => (int) $resource->source_id,
            'modulename' => 'resource',
            'contextid' => (int) $resource->source_id,
            'name' => (string) $resource->title,
            'intro' => (string) ($resource->comment ?? ''),
            'sectionid' => $sectionId,
            'sectionnumber' => 1,
            'timemodified' => time(),
            'users' => [],
            'files' => [],
        ];
    }

    /**
     * Creates the inforef.xml file. En V1 se escribía un único <file><id/> con $references['id'].
     * Mantenemos el comportamiento para compatibilidad.
     *
     * @param array<string,mixed> $references
     */
    protected function createInforefXml(array $references, string $directory): void
    {
        $fileId = (int) ($references['id'] ?? 0);

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<inforef>'.PHP_EOL;
        $xmlContent .= '  <fileref>'.PHP_EOL;
        $xmlContent .= '    <file>'.PHP_EOL;
        $xmlContent .= '      <id>'.$fileId.'</id>'.PHP_EOL;
        $xmlContent .= '    </file>'.PHP_EOL;
        $xmlContent .= '  </fileref>'.PHP_EOL;
        $xmlContent .= '</inforef>'.PHP_EOL;

        $this->createXmlFile('inforef', $xmlContent, $directory);
    }

    /**
     * Create the XML file for the resource.
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
}
