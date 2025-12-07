<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\FileIndex;
use Exception;

use const PHP_EOL;

/**
 * Base class for Moodle activity exporters.
 * Child classes must implement export() and may reuse helpers here.
 *
 * Expected $this->course shape (legacy array/object used by coursecopy):
 *   - info: ['real_id'=>int, 'code'=>string]
 *   - resources: mixed structure per tool
 */
abstract class ActivityExport
{
    /**
     * @var mixed Legacy course snapshot used during export
     */
    protected $course;

    public const DOCS_MODULE_ID = 1000000;

    public function __construct($course)
    {
        $this->course = $course;
    }

    /**
     * Export this activity to the provided directory.
     */
    abstract public function export(int $activityId, string $exportDir, int $moduleId, int $sectionId): void;

    /**
     * Resolve the section id (learnpath/section) that contains a given activity.
     * Falls back to 0 if not found.
     */
    public function getSectionIdForActivity(int $activityId, string $itemType): int
    {
        // Normalize legacy "student_publication" -> "work"
        $needle = 'student_publication' === $itemType ? 'work' : $itemType;

        foreach ($this->course->resources[RESOURCE_LEARNPATH] ?? [] as $learnpath) {
            foreach ($learnpath->items ?? [] as $item) {
                $type = ($item['item_type'] ?? '') === 'student_publication' ? 'work' : ($item['item_type'] ?? '');
                if ($type === $needle && (int) ($item['path'] ?? -1) === $activityId) {
                    return (int) $learnpath->source_id;
                }
            }
        }

        return 0;
    }

    /**
     * Ensure the activity directory exists and return its absolute path.
     * Result: <exportDir>/activities/<activityType>_<moduleId>.
     */
    protected function prepareActivityDirectory(string $exportDir, string $activityType, int $moduleId): string
    {
        $activityDir = rtrim($exportDir, '/')."/activities/{$activityType}_{$moduleId}";
        if (!is_dir($activityDir) && !@mkdir($activityDir, 0777, true) && !is_dir($activityDir)) {
            throw new Exception("Can not create activity directory: {$activityDir}");
        }

        return $activityDir;
    }

    /**
     * Write a simple XML file into $directory with name $fileName.xml.
     */
    protected function createXmlFile(string $fileName, string $xmlContent, string $directory): void
    {
        $filePath = rtrim($directory, '/').'/'.$fileName.'.xml';
        if (false === @file_put_contents($filePath, $xmlContent)) {
            throw new Exception("Error creating {$fileName}.xml at {$filePath}");
        }
    }

    /**
     * module.xml — generic module metadata used by Moodle backup.
     */
    protected function createModuleXml(array $data, string $directory): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<module id="'.(int) $data['moduleid'].'" version="2021051700">'.PHP_EOL;
        $xml .= '  <modulename>'.($data['modulename'] ?? '').'</modulename>'.PHP_EOL;
        $xml .= '  <sectionid>'.(int) ($data['sectionid'] ?? 0).'</sectionid>'.PHP_EOL;
        $xml .= '  <sectionnumber>'.(int) ($data['sectionnumber'] ?? 0).'</sectionnumber>'.PHP_EOL;
        $xml .= '  <idnumber></idnumber>'.PHP_EOL;
        $xml .= '  <added>'.time().'</added>'.PHP_EOL;
        $xml .= '  <score>0</score>'.PHP_EOL;
        $xml .= '  <indent>0</indent>'.PHP_EOL;
        $xml .= '  <visible>1</visible>'.PHP_EOL;
        $xml .= '  <visibleoncoursepage>1</visibleoncoursepage>'.PHP_EOL;
        $xml .= '  <visibleold>1</visibleold>'.PHP_EOL;
        $xml .= '  <groupmode>0</groupmode>'.PHP_EOL;
        $xml .= '  <groupingid>0</groupingid>'.PHP_EOL;
        $xml .= '  <completion>1</completion>'.PHP_EOL;
        $xml .= '  <completiongradeitemnumber>$@NULL@$</completiongradeitemnumber>'.PHP_EOL;
        $xml .= '  <completionview>0</completionview>'.PHP_EOL;
        $xml .= '  <completionexpected>0</completionexpected>'.PHP_EOL;
        $xml .= '  <availability>$@NULL@$</availability>'.PHP_EOL;
        $xml .= '  <showdescription>0</showdescription>'.PHP_EOL;
        $xml .= '  <tags></tags>'.PHP_EOL;
        $xml .= '</module>'.PHP_EOL;

        $this->createXmlFile('module', $xml, $directory);
    }

    /**
     * grades.xml — override in child to include real grade_item definitions.
     */
    protected function createGradesXml(array $data, string $directory): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<activity_gradebook>'.PHP_EOL;
        $xml .= '  <grade_items></grade_items>'.PHP_EOL;
        $xml .= '</activity_gradebook>'.PHP_EOL;

        $this->createXmlFile('grades', $xml, $directory);
    }

    /**
     * inforef.xml — references to users/files used by this activity.
     */
    protected function createInforefXml(array $references, string $directory): void
    {
        @error_log('[ActivityExport::createInforefXml] Start. Dir='.$directory);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<inforef>'.PHP_EOL;

        $userCount = 0;
        if (!empty($references['users']) && \is_array($references['users'])) {
            $xml .= ' <userref>'.PHP_EOL;
            foreach ($references['users'] as $uid) {
                $xml .= ' <user><id>'.htmlspecialchars((string) $uid).'</id></user>'.PHP_EOL;
                $userCount++;
            }
            $xml .= ' </userref>'.PHP_EOL;
        }

        $fileCount = 0;
        $resolvedByHash = 0;

        if (!empty($references['files']) && \is_array($references['files'])) {
            $xml .= ' <fileref>'.PHP_EOL;

            foreach ($references['files'] as $file) {
                $fid = null;
                $hash = null;

                if (\is_array($file)) {
                    $fid  = $file['id'] ?? null;
                    $hash = $file['contenthash'] ?? null;

                    if ($hash) {
                        $tmp = \Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\FileIndex::resolveByContenthash((string) $hash);
                        if (null !== $tmp) {
                            $fid = $tmp;
                            $resolvedByHash++;
                        } else {
                            @error_log('[ActivityExport::createInforefXml] WARNING: Could not resolve contenthash='.$hash.' to a file id.');
                        }
                    }
                } else {
                    $fid = $file;
                }

                if (null === $fid) {
                    @error_log('[ActivityExport::createInforefXml] WARNING: Null file id entry skipped.');
                    continue;
                }

                $xml .= ' <file><id>'.htmlspecialchars((string) $fid).'</id></file>'.PHP_EOL;
                $fileCount++;
            }

            $xml .= ' </fileref>'.PHP_EOL;
        }

        $xml .= '</inforef>'.PHP_EOL;

        $this->createXmlFile('inforef', $xml, $directory);

        @error_log('[ActivityExport::createInforefXml] Done. users='.$userCount.' files='.$fileCount.' resolvedByHash='.$resolvedByHash.' Dir='.$directory);
    }

    /**
     * roles.xml — left empty by default. Override if needed.
     */
    protected function createRolesXml(array $activityData, string $directory): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<roles></roles>'.PHP_EOL;
        $this->createXmlFile('roles', $xml, $directory);
    }

    /**
     * filters.xml — default empty.
     */
    protected function createFiltersXml(array $activityData, string $destinationDir): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<filters><filter_actives></filter_actives><filter_configs></filter_configs></filters>'.PHP_EOL;
        $this->createXmlFile('filters', $xml, $destinationDir);
    }

    /**
     * grade_history.xml — default empty.
     */
    protected function createGradeHistoryXml(array $activityData, string $destinationDir): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<grade_history><grade_grades></grade_grades></grade_history>'.PHP_EOL;
        $this->createXmlFile('grade_history', $xml, $destinationDir);
    }

    /**
     * completion.xml — default minimal placeholder.
     */
    protected function createCompletionXml(array $activityData, string $destinationDir): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<completion><completiondata><completion>'.PHP_EOL;
        $xml .= '  <timecompleted>0</timecompleted><completionstate>1</completionstate>'.PHP_EOL;
        $xml .= '</completion></completiondata></completion>'.PHP_EOL;

        $this->createXmlFile('completion', $xml, $destinationDir);
    }

    /**
     * comments.xml — default minimal placeholder.
     */
    protected function createCommentsXml(array $activityData, string $destinationDir): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<comments></comments>'.PHP_EOL;

        $this->createXmlFile('comments', $xml, $destinationDir);
    }

    /**
     * competencies.xml — default minimal placeholder.
     */
    protected function createCompetenciesXml(array $activityData, string $destinationDir): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<competencies></competencies>'.PHP_EOL;

        $this->createXmlFile('competencies', $xml, $destinationDir);
    }

    /**
     * calendar.xml — default with a single placeholder event.
     */
    protected function createCalendarXml(array $activityData, string $destinationDir): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<calendar><event>'.PHP_EOL;
        $xml .= '  <name>Due Date</name><timestart>'.time().'</timestart>'.PHP_EOL;
        $xml .= '</event></calendar>'.PHP_EOL;

        $this->createXmlFile('calendar', $xml, $destinationDir);
    }

    /**
     * Tiny helper to retrieve an admin user when needed by exporters.
     * Override if you have a service to resolve this properly.
     */
    protected function getAdminUserData(): array
    {
        // Default stub. Replace with real lookup if available.
        return ['id' => 2];
    }
}
