<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleExport;
use DocumentManager;
use Exception;

use const PHP_EOL;

/**
 * Base class for Moodle activity exporters.
 */
abstract class ActivityExport
{
    /**
     * Synthetic module id used by the root "Documents" folder activity.
     */
    public const DOCS_MODULE_ID = 1000000;
    public const INTRO_PAGE_MODULE_ID = 1000001;

    /**
     * @var object
     */
    protected $course;

    public function __construct($course)
    {
        $this->course = $course;
    }

    /**
     * Export the activity.
     */
    abstract public function export(int $activityId, string $exportDir, int $moduleId, int $sectionId): void;

    /**
     * Resolve the section id (learnpath/source_id) that contains a given activity.
     */
    public function getSectionIdForActivity(int $activityId, string $itemType): int
    {
        $needle = $this->normalizeLpItemTypeForComparison($itemType);

        $learnpaths =
            $this->course->resources[\defined('RESOURCE_LEARNPATH') ? RESOURCE_LEARNPATH : 'learnpath']
            ?? $this->course->resources['learnpath']
            ?? [];

        if (!\is_array($learnpaths) || empty($learnpaths)) {
            return 0;
        }

        foreach ($learnpaths as $learnpathWrap) {
            $learnpath = (\is_object($learnpathWrap) && isset($learnpathWrap->obj) && \is_object($learnpathWrap->obj))
                ? $learnpathWrap->obj
                : $learnpathWrap;

            if (!\is_object($learnpath) || empty($learnpath->items) || !\is_array($learnpath->items)) {
                continue;
            }

            foreach ($learnpath->items as $item) {
                if (!\is_array($item)) {
                    continue;
                }

                $lpType = $this->normalizeLpItemTypeForComparison((string) ($item['item_type'] ?? ''));
                if ($lpType !== $needle) {
                    continue;
                }

                $lpPath = (string) ($item['path'] ?? '');

                if ('' !== $lpPath && ctype_digit($lpPath) && (int) $lpPath === $activityId) {
                    return (int) ($learnpath->source_id ?? 0);
                }

                if ('document' === $needle) {
                    $courseCode = (string) ($this->course->code ?? $this->course->info['code'] ?? '');
                    $doc = DocumentManager::get_document_data_by_id($activityId, $courseCode);

                    if (!empty($doc['path'])) {
                        $docPath = (string) $doc['path'];
                        $candidates = [
                            $docPath,
                            ltrim($docPath, '/'),
                            '/'.ltrim($docPath, '/'),
                            'document/'.ltrim($docPath, '/'),
                            '/document/'.ltrim($docPath, '/'),
                        ];

                        foreach (array_unique($candidates) as $candidate) {
                            if ($lpPath === $candidate) {
                                return (int) ($learnpath->source_id ?? 0);
                            }
                        }
                    }
                }
            }
        }

        return 0;
    }

    /**
     * Prepare the directory for the activity.
     */
    protected function prepareActivityDirectory(string $exportDir, string $activityType, int $moduleId): string
    {
        $activityDir = rtrim($exportDir, '/')."/activities/{$activityType}_{$moduleId}";

        if (!is_dir($activityDir) && !@mkdir($activityDir, api_get_permissions_for_new_directories(), true) && !is_dir($activityDir)) {
            throw new Exception("Unable to create activity directory: {$activityDir}");
        }

        return $activityDir;
    }

    /**
     * Create a generic XML file.
     */
    protected function createXmlFile(string $fileName, string $xmlContent, string $directory): void
    {
        $filePath = rtrim($directory, '/').'/'.$fileName.'.xml';

        if (false === @file_put_contents($filePath, $xmlContent)) {
            throw new Exception("Error creating {$fileName}.xml");
        }
    }

    /**
     * Create module.xml.
     *
     * @param array<string,mixed> $data
     */
    protected function createModuleXml(array $data, string $directory): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<module id="'.(int) ($data['moduleid'] ?? 0).'" version="2021051700">'.PHP_EOL;
        $xmlContent .= '  <modulename>'.htmlspecialchars((string) ($data['modulename'] ?? '')).'</modulename>'.PHP_EOL;
        $xmlContent .= '  <sectionid>'.(int) ($data['sectionid'] ?? 0).'</sectionid>'.PHP_EOL;
        $xmlContent .= '  <sectionnumber>'.(int) ($data['sectionnumber'] ?? 0).'</sectionnumber>'.PHP_EOL;
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
     * Create grades.xml.
     *
     * @param array<string,mixed> $data
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
     * Create inforef.xml.
     *
     * @param array<string,mixed> $references
     */
    protected function createInforefXml(array $references, string $directory): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<inforef>'.PHP_EOL;

        if (!empty($references['users']) && \is_array($references['users'])) {
            $xmlContent .= '  <userref>'.PHP_EOL;
            foreach ($references['users'] as $userId) {
                $xmlContent .= '    <user>'.PHP_EOL;
                $xmlContent .= '      <id>'.htmlspecialchars((string) $userId).'</id>'.PHP_EOL;
                $xmlContent .= '    </user>'.PHP_EOL;
            }
            $xmlContent .= '  </userref>'.PHP_EOL;
        }

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
     * Create roles.xml.
     *
     * @param array<string,mixed> $activityData
     */
    protected function createRolesXml(array $activityData, string $directory): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<roles></roles>'.PHP_EOL;

        $this->createXmlFile('roles', $xmlContent, $directory);
    }

    /**
     * Create filters.xml.
     *
     * @param array<string,mixed> $activityData
     */
    protected function createFiltersXml(array $activityData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<filters>'.PHP_EOL;
        $xmlContent .= '  <filter_actives></filter_actives>'.PHP_EOL;
        $xmlContent .= '  <filter_configs></filter_configs>'.PHP_EOL;
        $xmlContent .= '</filters>'.PHP_EOL;

        $this->createXmlFile('filters', $xmlContent, $destinationDir);
    }

    /**
     * Create grade_history.xml.
     *
     * @param array<string,mixed> $activityData
     */
    protected function createGradeHistoryXml(array $activityData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<grade_history>'.PHP_EOL;
        $xmlContent .= '  <grade_grades></grade_grades>'.PHP_EOL;
        $xmlContent .= '</grade_history>'.PHP_EOL;

        $this->createXmlFile('grade_history', $xmlContent, $destinationDir);
    }

    /**
     * Create completion.xml.
     *
     * @param array<string,mixed> $activityData
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
     * Create comments.xml.
     *
     * @param array<string,mixed> $activityData
     */
    protected function createCommentsXml(array $activityData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<comments></comments>'.PHP_EOL;

        $this->createXmlFile('comments', $xmlContent, $destinationDir);
    }

    /**
     * Create competencies.xml.
     *
     * @param array<string,mixed> $activityData
     */
    protected function createCompetenciesXml(array $activityData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<competencies></competencies>'.PHP_EOL;

        $this->createXmlFile('competencies', $xmlContent, $destinationDir);
    }

    /**
     * Create calendar.xml.
     *
     * @param array<string,mixed> $activityData
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

    /**
     * Create a Moodle-safe activity name.
     */
    protected function sanitizeMoodleActivityName(string $raw, int $maxLen = 255): string
    {
        $s = trim($raw);
        if ('' === $s) {
            return '';
        }

        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = strip_tags($s);
        $s = preg_replace('/\s+/u', ' ', $s);
        $s = trim((string) $s);

        if ('' === $s) {
            return '';
        }

        if (\function_exists('mb_strlen') && \function_exists('mb_substr')) {
            if (mb_strlen($s, 'UTF-8') > $maxLen) {
                $s = mb_substr($s, 0, $maxLen, 'UTF-8');
            }
        } elseif (\strlen($s) > $maxLen) {
            $s = substr($s, 0, $maxLen);
        }

        return $s;
    }

    /**
     * Return the LP item title if present, otherwise the fallback title.
     */
    protected function lpItemTitle(int $sectionId, string $itemType, int $resourceId, ?string $fallback): string
    {
        $learnpaths =
            $this->course->resources[\defined('RESOURCE_LEARNPATH') ? RESOURCE_LEARNPATH : 'learnpath']
            ?? $this->course->resources['learnpath']
            ?? [];

        if (!\is_array($learnpaths) || empty($learnpaths)) {
            return $fallback ?? '';
        }

        $needle = $this->normalizeLpItemTypeForComparison($itemType);
        $courseCode = (string) ($this->course->code ?? $this->course->info['code'] ?? '');

        foreach ($learnpaths as $learnpathWrap) {
            $learnpath = (\is_object($learnpathWrap) && isset($learnpathWrap->obj) && \is_object($learnpathWrap->obj))
                ? $learnpathWrap->obj
                : $learnpathWrap;

            if (!\is_object($learnpath) || (int) ($learnpath->source_id ?? 0) !== $sectionId || empty($learnpath->items)) {
                continue;
            }

            foreach ((array) $learnpath->items as $item) {
                if (!\is_array($item)) {
                    continue;
                }

                $lpType = $this->normalizeLpItemTypeForComparison((string) ($item['item_type'] ?? ''));
                if ($lpType !== $needle) {
                    continue;
                }

                $lpPath = (string) ($item['path'] ?? '');

                if (ctype_digit($lpPath) && (int) $lpPath === $resourceId) {
                    return (string) ($item['title'] ?? ($fallback ?? ''));
                }

                if ('document' === $needle) {
                    $doc = DocumentManager::get_document_data_by_id($resourceId, $courseCode);

                    if (!empty($doc['path'])) {
                        $docPath = (string) $doc['path'];
                        $candidates = [
                            $docPath,
                            ltrim($docPath, '/'),
                            '/'.ltrim($docPath, '/'),
                            'document/'.ltrim($docPath, '/'),
                            '/document/'.ltrim($docPath, '/'),
                        ];

                        foreach (array_unique($candidates) as $candidate) {
                            if ($lpPath === $candidate) {
                                return (string) ($item['title'] ?? ($fallback ?? ''));
                            }
                        }
                    }
                }
            }
        }

        return $fallback ?? '';
    }

    /**
     * Get admin user data used by builders.
     *
     * @return array<string,mixed>
     */
    protected function getAdminUserData(): array
    {
        return MoodleExport::getAdminUserData();
    }

    /**
     * Normalize item types for LP comparison.
     */
    private function normalizeLpItemTypeForComparison(string $type): string
    {
        switch ($type) {
            case 'student_publication':
            case 'work':
            case 'assign':
                return 'work';

            case 'link':
            case 'url':
                return 'link';

            case 'survey':
            case 'feedback':
                return 'survey';

            case 'page':
            case 'resource':
                return 'document';

            default:
                return $type;
        }
    }
}
