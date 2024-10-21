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

        // Retrieve resource data
        $resourceData = $this->getData($activityId, $sectionId);

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
    public function getData(int $resourceId, int $sectionId): array
    {
        $resource = $this->course->resources[RESOURCE_DOCUMENT][$resourceId];

        return [
            'id' => $resourceId,
            'moduleid' => $resource->source_id,
            'modulename' => 'resource',
            'contextid' => $resource->source_id,
            'name' => $resource->title,
            'intro' => $resource->comment ?? '',
            'sectionid' => $sectionId,
            'sectionnumber' => 1,
            'timemodified' => time(),
            'users' => [],
            'files' => [],
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

        $xmlContent .= '  <fileref>'.PHP_EOL;
        $xmlContent .= '    <file>'.PHP_EOL;
        $xmlContent .= '      <id>'.htmlspecialchars($references['id']).'</id>'.PHP_EOL;
        $xmlContent .= '    </file>'.PHP_EOL;
        $xmlContent .= '  </fileref>'.PHP_EOL;

        $xmlContent .= '</inforef>'.PHP_EOL;

        // Save the XML content to the directory
        $this->createXmlFile('inforef', $xmlContent, $directory);
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
