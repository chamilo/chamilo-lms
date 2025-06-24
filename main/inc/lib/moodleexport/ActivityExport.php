<?php

/* For licensing terms, see /license.txt */

namespace moodleexport;

use Exception;

/**
 * Class ActivityExport.
 *
 * Base class for exporting common activities.
 */
abstract class ActivityExport
{
    protected $course;

    public function __construct($course)
    {
        $this->course = $course;
    }

    /**
     * Abstract method for exporting the activity.
     * Must be implemented by child classes.
     */
    abstract public function export($activityId, $exportDir, $moduleId, $sectionId);

    /**
     * Get the section ID for a given activity ID.
     */
    public function getSectionIdForActivity(int $activityId, string $itemType): int
    {
        foreach ($this->course->resources[RESOURCE_LEARNPATH] as $learnpath) {
            foreach ($learnpath->items as $item) {
                $item['item_type'] = $item['item_type'] === 'student_publication' ? 'work' : $item['item_type'];
                if ($item['item_type'] == $itemType && $item['path'] == $activityId) {
                    return $learnpath->source_id;
                }
            }
        }

        return 0;
    }

    /**
     * Prepares the directory for the activity.
     */
    protected function prepareActivityDirectory(string $exportDir, string $activityType, int $moduleId): string
    {
        $activityDir = "{$exportDir}/activities/{$activityType}_{$moduleId}";
        if (!is_dir($activityDir)) {
            mkdir($activityDir, 0777, true);
        }

        return $activityDir;
    }

    /**
     * Creates a generic XML file.
     */
    protected function createXmlFile(string $fileName, string $xmlContent, string $directory): void
    {
        $filePath = $directory.'/'.$fileName.'.xml';
        if (file_put_contents($filePath, $xmlContent) === false) {
            throw new Exception("Error creating {$fileName}.xml");
        }
    }

    /**
     * Creates the module.xml file.
     */
    protected function createModuleXml(array $data, string $directory): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<module id="'.$data['moduleid'].'" version="2021051700">'.PHP_EOL;
        $xmlContent .= '  <modulename>'.$data['modulename'].'</modulename>'.PHP_EOL;
        $xmlContent .= '  <sectionid>'.$data['sectionid'].'</sectionid>'.PHP_EOL;
        $xmlContent .= '  <sectionnumber>'.$data['sectionnumber'].'</sectionnumber>'.PHP_EOL;
        $xmlContent .= '  <idnumber></idnumber>'.PHP_EOL;
        $xmlContent .= '  <added>'.time().'</added>'.PHP_EOL;
        $xmlContent .= '  <score>0</score>'.PHP_EOL;
        $xmlContent .= '  <indent>0</indent>'.PHP_EOL;
        $xmlContent .= '  <visible>1</visible>'.PHP_EOL;
        $xmlContent .= '  <visibleoncoursepage>1</visibleoncoursepage>'.PHP_EOL;
        $xmlContent .= '  <visibleold>1</visibleold>'.PHP_EOL;
        $xmlContent .= '  <groupmode>0</groupmode>'.PHP_EOL;
        $xmlContent .= '  <groupingid>0</groupingid>'.PHP_EOL;
        $xmlContent .= '  <completion>1</completion>'.PHP_EOL;
        $xmlContent .= '  <completiongradeitemnumber>$@NULL@$</completiongradeitemnumber>'.PHP_EOL;
        $xmlContent .= '  <completionview>0</completionview>'.PHP_EOL;
        $xmlContent .= '  <completionexpected>0</completionexpected>'.PHP_EOL;
        $xmlContent .= '  <availability>$@NULL@$</availability>'.PHP_EOL;
        $xmlContent .= '  <showdescription>0</showdescription>'.PHP_EOL;
        $xmlContent .= '  <tags></tags>'.PHP_EOL;
        $xmlContent .= '</module>'.PHP_EOL;

        $this->createXmlFile('module', $xmlContent, $directory);
    }

    /**
     * Creates the grades.xml file.
     */
    protected function createGradesXml(array $data, string $directory): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<activity_gradebook>'.PHP_EOL;
        $xmlContent .= '  <grade_items></grade_items>'.PHP_EOL;
        $xmlContent .= '</activity_gradebook>'.PHP_EOL;

        $this->createXmlFile('grades', $xmlContent, $directory);
    }

    /**
     * Creates the inforef.xml file, referencing users and files associated with the activity.
     */
    protected function createInforefXml(array $references, string $directory): void
    {
        // Start the XML content
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<inforef>'.PHP_EOL;

        // Add user references if provided
        if (isset($references['users']) && is_array($references['users'])) {
            $xmlContent .= '  <userref>'.PHP_EOL;
            foreach ($references['users'] as $userId) {
                $xmlContent .= '    <user>'.PHP_EOL;
                $xmlContent .= '      <id>'.htmlspecialchars($userId).'</id>'.PHP_EOL;
                $xmlContent .= '    </user>'.PHP_EOL;
            }
            $xmlContent .= '  </userref>'.PHP_EOL;
        }

        // Add file references if provided
        if (isset($references['files']) && is_array($references['files'])) {
            $xmlContent .= '  <fileref>'.PHP_EOL;
            foreach ($references['files'] as $file) {
                $xmlContent .= '    <file>'.PHP_EOL;
                $xmlContent .= '      <id>'.htmlspecialchars($file['id']).'</id>'.PHP_EOL;
                $xmlContent .= '    </file>'.PHP_EOL;
            }
            $xmlContent .= '  </fileref>'.PHP_EOL;
        }

        $xmlContent .= '</inforef>'.PHP_EOL;

        $this->createXmlFile('inforef', $xmlContent, $directory);
    }

    /**
     * Creates the roles.xml file.
     */
    protected function createRolesXml(array $activityData, string $directory): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<roles></roles>'.PHP_EOL;

        $this->createXmlFile('roles', $xmlContent, $directory);
    }

    /**
     * Creates the filters.xml file for the activity.
     */
    protected function createFiltersXml(array $activityData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<filters>'.PHP_EOL;
        $xmlContent .= '  <filter_actives>'.PHP_EOL;
        $xmlContent .= '  </filter_actives>'.PHP_EOL;
        $xmlContent .= '  <filter_configs>'.PHP_EOL;
        $xmlContent .= '  </filter_configs>'.PHP_EOL;
        $xmlContent .= '</filters>'.PHP_EOL;

        $this->createXmlFile('filters', $xmlContent, $destinationDir);
    }

    /**
     * Creates the grade_history.xml file for the activity.
     */
    protected function createGradeHistoryXml(array $activityData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<grade_history>'.PHP_EOL;
        $xmlContent .= '  <grade_grades>'.PHP_EOL;
        $xmlContent .= '  </grade_grades>'.PHP_EOL;
        $xmlContent .= '</grade_history>'.PHP_EOL;

        $this->createXmlFile('grade_history', $xmlContent, $destinationDir);
    }

    /**
     * Creates the completion.xml file.
     */
    protected function createCompletionXml(array $activityData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<completion>'.PHP_EOL;
        $xmlContent .= '  <completiondata>'.PHP_EOL;
        $xmlContent .= '    <completion>'.PHP_EOL;
        $xmlContent .= '      <timecompleted>0</timecompleted>'.PHP_EOL;
        $xmlContent .= '      <completionstate>1</completionstate>'.PHP_EOL;
        $xmlContent .= '    </completion>'.PHP_EOL;
        $xmlContent .= '  </completiondata>'.PHP_EOL;
        $xmlContent .= '</completion>'.PHP_EOL;

        $this->createXmlFile('completion', $xmlContent, $destinationDir);
    }

    /**
     * Creates the comments.xml file.
     */
    protected function createCommentsXml(array $activityData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<comments>'.PHP_EOL;
        $xmlContent .= '  <comment>'.PHP_EOL;
        $xmlContent .= '    <content>This is a sample comment</content>'.PHP_EOL;
        $xmlContent .= '    <author>Professor</author>'.PHP_EOL;
        $xmlContent .= '  </comment>'.PHP_EOL;
        $xmlContent .= '</comments>'.PHP_EOL;

        $this->createXmlFile('comments', $xmlContent, $destinationDir);
    }

    /**
     * Creates the competencies.xml file.
     */
    protected function createCompetenciesXml(array $activityData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<competencies>'.PHP_EOL;
        $xmlContent .= '  <competency>'.PHP_EOL;
        $xmlContent .= '    <name>Sample Competency</name>'.PHP_EOL;
        $xmlContent .= '  </competency>'.PHP_EOL;
        $xmlContent .= '</competencies>'.PHP_EOL;

        $this->createXmlFile('competencies', $xmlContent, $destinationDir);
    }

    /**
     * Creates the calendar.xml file.
     */
    protected function createCalendarXml(array $activityData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<calendar>'.PHP_EOL;
        $xmlContent .= '  <event>'.PHP_EOL;
        $xmlContent .= '    <name>Due Date</name>'.PHP_EOL;
        $xmlContent .= '    <timestart>'.time().'</timestart>'.PHP_EOL;
        $xmlContent .= '  </event>'.PHP_EOL;
        $xmlContent .= '</calendar>'.PHP_EOL;

        $this->createXmlFile('calendar', $xmlContent, $destinationDir);
    }
}
