<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\QuizExport;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\CalendarEvent;
use Exception;

use const PHP_EOL;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * Writes the course-level directory and XMLs inside the export root.
 */
class CourseExport
{
    /**
     * @var object
     */
    private $course;

    /**
     * @var array<string,mixed>
     */
    private array $courseInfo;

    /**
     * @var array<int,array<string,mixed>>
     */
    private array $activities;

    /**
     * @param array<int,array<string,mixed>>|null $activities
     *
     * @throws Exception
     */
    public function __construct(object $course, ?array $activities = [])
    {
        $this->course = $course;
        $this->courseInfo = (array) (api_get_course_info($course->code) ?? []);

        if (empty($this->courseInfo)) {
            throw new Exception('Course not found.');
        }

        $this->activities = $activities ?? [];
    }

    /**
     * Export the course-related files to the appropriate directory.
     */
    public function exportCourse(string $exportDir): void
    {
        $courseDir = $exportDir.'/course';
        if (!is_dir($courseDir)) {
            mkdir($courseDir, api_get_permissions_for_new_directories(), true);
        }

        $this->createCourseXml($courseDir);
        $this->createEnrolmentsXml($this->courseInfo['enrolments'] ?? [], $courseDir);
        $this->createInforefXml($courseDir);
        $this->createRolesXml($this->courseInfo['roles'] ?? [], $courseDir);
        $this->createCalendarXml($this->courseInfo['calendar'] ?? [], $courseDir);
        $this->createCommentsXml($this->courseInfo['comments'] ?? [], $courseDir);
        $this->createCompetenciesXml($this->courseInfo['competencies'] ?? [], $courseDir);
        $this->createCompletionDefaultsXml($this->courseInfo['completiondefaults'] ?? [], $courseDir);
        $this->createContentBankXml($this->courseInfo['contentbank'] ?? [], $courseDir);
        $this->createFiltersXml($this->courseInfo['filters'] ?? [], $courseDir);
    }

    /**
     * Create course.xml based on the course data.
     */
    private function createCourseXml(string $destinationDir): void
    {
        $courseId = (int) ($this->courseInfo['real_id'] ?? 0);
        $contextId = (int) ($this->courseInfo['real_id'] ?? 1);
        $shortname = (string) ($this->courseInfo['code'] ?? 'Unknown Course');
        $fullname = (string) ($this->courseInfo['title'] ?? 'Unknown Fullname');
        $showgrades = (int) ($this->courseInfo['showgrades'] ?? 0);
        $startdate = (int) ($this->courseInfo['startdate'] ?? time());
        $enddate = (int) ($this->courseInfo['enddate'] ?? (time() + 31536000));
        $visible = (int) ($this->courseInfo['visible'] ?? 1);
        $enablecompletion = (int) ($this->courseInfo['enablecompletion'] ?? 0);

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<course id="'.$courseId.'" contextid="'.$contextId.'">'.PHP_EOL;
        $xmlContent .= '  <shortname>'.htmlspecialchars($shortname).'</shortname>'.PHP_EOL;
        $xmlContent .= '  <fullname>'.htmlspecialchars($fullname).'</fullname>'.PHP_EOL;
        $xmlContent .= '  <idnumber></idnumber>'.PHP_EOL;
        $xmlContent .= '  <summary></summary>'.PHP_EOL;
        $xmlContent .= '  <summaryformat>1</summaryformat>'.PHP_EOL;
        $xmlContent .= '  <format>topics</format>'.PHP_EOL;
        $xmlContent .= '  <showgrades>'.$showgrades.'</showgrades>'.PHP_EOL;
        $xmlContent .= '  <newsitems>5</newsitems>'.PHP_EOL;
        $xmlContent .= '  <startdate>'.$startdate.'</startdate>'.PHP_EOL;
        $xmlContent .= '  <enddate>'.$enddate.'</enddate>'.PHP_EOL;
        $xmlContent .= '  <marker>0</marker>'.PHP_EOL;
        $xmlContent .= '  <maxbytes>0</maxbytes>'.PHP_EOL;
        $xmlContent .= '  <legacyfiles>0</legacyfiles>'.PHP_EOL;
        $xmlContent .= '  <showreports>0</showreports>'.PHP_EOL;
        $xmlContent .= '  <visible>'.$visible.'</visible>'.PHP_EOL;
        $xmlContent .= '  <groupmode>0</groupmode>'.PHP_EOL;
        $xmlContent .= '  <groupmodeforce>0</groupmodeforce>'.PHP_EOL;
        $xmlContent .= '  <defaultgroupingid>0</defaultgroupingid>'.PHP_EOL;
        $xmlContent .= '  <lang></lang>'.PHP_EOL;
        $xmlContent .= '  <theme></theme>'.PHP_EOL;
        $xmlContent .= '  <timecreated>'.time().'</timecreated>'.PHP_EOL;
        $xmlContent .= '  <timemodified>'.time().'</timemodified>'.PHP_EOL;
        $xmlContent .= '  <requested>0</requested>'.PHP_EOL;
        $xmlContent .= '  <showactivitydates>1</showactivitydates>'.PHP_EOL;
        $xmlContent .= '  <showcompletionconditions>1</showcompletionconditions>'.PHP_EOL;
        $xmlContent .= '  <enablecompletion>'.$enablecompletion.'</enablecompletion>'.PHP_EOL;
        $xmlContent .= '  <completionnotify>0</completionnotify>'.PHP_EOL;
        $xmlContent .= '  <category id="1">'.PHP_EOL;
        $xmlContent .= '    <name>Miscellaneous</name>'.PHP_EOL;
        $xmlContent .= '    <description>$@NULL@$</description>'.PHP_EOL;
        $xmlContent .= '  </category>'.PHP_EOL;
        $xmlContent .= '  <tags>'.PHP_EOL;
        $xmlContent .= '  </tags>'.PHP_EOL;
        $xmlContent .= '  <customfields>'.PHP_EOL;
        $xmlContent .= '  </customfields>'.PHP_EOL;
        $xmlContent .= '  <courseformatoptions>'.PHP_EOL;
        $xmlContent .= '    <courseformatoption>'.PHP_EOL;
        $xmlContent .= '      <format>topics</format>'.PHP_EOL;
        $xmlContent .= '      <sectionid>0</sectionid>'.PHP_EOL;
        $xmlContent .= '      <name>hiddensections</name>'.PHP_EOL;
        $xmlContent .= '      <value>0</value>'.PHP_EOL;
        $xmlContent .= '    </courseformatoption>'.PHP_EOL;
        $xmlContent .= '    <courseformatoption>'.PHP_EOL;
        $xmlContent .= '      <format>topics</format>'.PHP_EOL;
        $xmlContent .= '      <sectionid>0</sectionid>'.PHP_EOL;
        $xmlContent .= '      <name>coursedisplay</name>'.PHP_EOL;
        $xmlContent .= '      <value>0</value>'.PHP_EOL;
        $xmlContent .= '    </courseformatoption>'.PHP_EOL;
        $xmlContent .= '  </courseformatoptions>'.PHP_EOL;
        $xmlContent .= '</course>';

        file_put_contents($destinationDir.'/course.xml', $xmlContent);
    }

    /**
     * Create enrolments.xml based on the course data.
     *
     * @param array<int,array<string,mixed>> $enrolmentsData
     */
    private function createEnrolmentsXml(array $enrolmentsData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<enrolments>'.PHP_EOL;
        foreach ($enrolmentsData as $enrol) {
            $id = (int) ($enrol['id'] ?? 0);
            $type = (string) ($enrol['type'] ?? 'manual');
            $status = (int) ($enrol['status'] ?? 1);

            $xmlContent .= '  <enrol id="'.$id.'">'.PHP_EOL;
            $xmlContent .= '    <enrol>'.htmlspecialchars($type).'</enrol>'.PHP_EOL;
            $xmlContent .= '    <status>'.$status.'</status>'.PHP_EOL;
            $xmlContent .= '  </enrol>'.PHP_EOL;
        }
        $xmlContent .= '</enrolments>';

        file_put_contents($destinationDir.'/enrolments.xml', $xmlContent);
    }

    /**
     * Creates the inforef.xml file with question category references and a basic role ref.
     */
    private function createInforefXml(string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<inforef>'.PHP_EOL;

        // Gather unique question category ids from quiz activities
        $questionCategories = [];
        foreach ($this->activities as $activity) {
            if (($activity['modulename'] ?? '') === 'quiz') {
                $quizExport = new QuizExport($this->course);
                $quizData = $quizExport->getData((int) $activity['id'], (int) $activity['sectionid']);

                foreach ($quizData['questions'] as $question) {
                    $categoryId = (int) $question['questioncategoryid'];
                    if (!\in_array($categoryId, $questionCategories, true)) {
                        $questionCategories[] = $categoryId;
                    }
                }
            }
        }

        if (!empty($questionCategories)) {
            $xmlContent .= '  <question_categoryref>'.PHP_EOL;
            foreach ($questionCategories as $categoryId) {
                $xmlContent .= '    <question_category>'.PHP_EOL;
                $xmlContent .= '      <id>'.$categoryId.'</id>'.PHP_EOL;
                $xmlContent .= '    </question_category>'.PHP_EOL;
            }
            $xmlContent .= '  </question_categoryref>'.PHP_EOL;
        }

        // Add a minimal role reference (student)
        $xmlContent .= '  <roleref>'.PHP_EOL;
        $xmlContent .= '    <role>'.PHP_EOL;
        $xmlContent .= '      <id>5</id>'.PHP_EOL;
        $xmlContent .= '    </role>'.PHP_EOL;
        $xmlContent .= '  </roleref>'.PHP_EOL;

        $xmlContent .= '</inforef>'.PHP_EOL;

        file_put_contents($destinationDir.'/inforef.xml', $xmlContent);
    }

    /**
     * Creates the roles.xml file.
     *
     * @param array<int,array<string,mixed>> $rolesData
     */
    private function createRolesXml(array $rolesData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<roles>'.PHP_EOL;
        foreach ($rolesData as $role) {
            $roleName = (string) ($role['name'] ?? 'Student');
            $xmlContent .= '  <role>'.PHP_EOL;
            $xmlContent .= '    <name>'.htmlspecialchars($roleName).'</name>'.PHP_EOL;
            $xmlContent .= '  </role>'.PHP_EOL;
        }
        $xmlContent .= '</roles>';

        file_put_contents($destinationDir.'/roles.xml', $xmlContent);
    }

    /**
     * Always writes course/calendar.xml (Moodle expects it).
     * Priority:
     *  1) Events pushed by the builder into $this->course->resources (truth source).
     *  2) Fallback to $calendarData (legacy optional).
     *  3) Minimal stub if none.
     *
     * @param array<int,array<string,mixed>> $calendarData
     */
    private function createCalendarXml(array $calendarData, string $destinationDir): void
    {
        $builderEvents = $this->collectCalendarEvents();

        if (!empty($builderEvents)) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
            $xml .= '<calendar>' . PHP_EOL;

            foreach ($builderEvents as $ev) {
                // Builder fields: iid, title, content, startDate, endDate, firstPath, firstName, firstSize, firstComment, allDay
                $title   = (string) ($ev->title ?? 'Event');
                $content = (string) ($ev->content ?? '');
                $start   = $this->toTimestamp((string)($ev->startDate ?? ''), time());
                $end     = $this->toTimestamp((string)($ev->endDate ?? ''), 0);
                $allday  = (int) ($ev->allDay ?? 0);

                $duration = 0;
                if ($end > 0 && $end > $start) {
                    $duration = $end - $start;
                }

                // Keep a Moodle-restore-friendly minimal shape
                $xml .= "  <event>" . PHP_EOL;
                $xml .= '    <name>'.htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</name>' . PHP_EOL;
                $xml .= '    <description><![CDATA['.$content.']]></description>' . PHP_EOL;
                $xml .= '    <format>1</format>' . PHP_EOL;          // HTML
                $xml .= '    <eventtype>course</eventtype>' . PHP_EOL;
                $xml .= '    <timestart>'.$start.'</timestart>' . PHP_EOL;
                $xml .= '    <duration>'.$duration.'</duration>' . PHP_EOL;
                $xml .= '    <visible>1</visible>' . PHP_EOL;
                $xml .= '    <allday>'.$allday.'</allday>' . PHP_EOL;
                $xml .= '    <repeatid>0</repeatid>' . PHP_EOL;
                $xml .= '    <uuid>$@NULL@$</uuid>' . PHP_EOL;
                $xml .= "  </event>" . PHP_EOL;
            }

            $xml .= '</calendar>' . PHP_EOL;
            file_put_contents($destinationDir . '/calendar.xml', $xml);
            return;
        }

        if (!empty($calendarData)) {
            $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
            $xml .= '<calendar>' . PHP_EOL;

            foreach ($calendarData as $e) {
                $name      = htmlspecialchars((string)($e['name'] ?? 'Event'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $timestart = (int)($e['timestart'] ?? time());
                $duration  = (int)($e['duration']  ?? 0);

                $xml .= "  <event>" . PHP_EOL;
                $xml .= "    <name>{$name}</name>" . PHP_EOL;
                $xml .= "    <timestart>{$timestart}</timestart>" . PHP_EOL;
                $xml .= "    <duration>{$duration}</duration>" . PHP_EOL;
                $xml .= "  </event>" . PHP_EOL;
            }

            $xml .= '</calendar>' . PHP_EOL;
            file_put_contents($destinationDir . '/calendar.xml', $xml);
            return;
        }

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<calendar/>' . PHP_EOL;
        file_put_contents($destinationDir . '/calendar.xml', $xml);
    }

    /**
     * Creates the comments.xml file.
     *
     * @param array<int,array<string,mixed>> $commentsData
     */
    private function createCommentsXml(array $commentsData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<comments>'.PHP_EOL;
        foreach ($commentsData as $comment) {
            $content = (string) ($comment['content'] ?? 'No comment');
            $author = (string) ($comment['author'] ?? 'Anonymous');

            $xmlContent .= '  <comment>'.PHP_EOL;
            $xmlContent .= '    <content>'.htmlspecialchars($content).'</content>'.PHP_EOL;
            $xmlContent .= '    <author>'.htmlspecialchars($author).'</author>'.PHP_EOL;
            $xmlContent .= '  </comment>'.PHP_EOL;
        }
        $xmlContent .= '</comments>';

        file_put_contents($destinationDir.'/comments.xml', $xmlContent);
    }

    /**
     * Creates the competencies.xml file.
     *
     * @param array<int,array<string,mixed>> $competenciesData
     */
    private function createCompetenciesXml(array $competenciesData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<competencies>'.PHP_EOL;
        foreach ($competenciesData as $competency) {
            $name = (string) ($competency['name'] ?? 'Competency');
            $xmlContent .= '  <competency>'.PHP_EOL;
            $xmlContent .= '    <name>'.htmlspecialchars($name).'</name>'.PHP_EOL;
            $xmlContent .= '  </competency>'.PHP_EOL;
        }
        $xmlContent .= '</competencies>';

        file_put_contents($destinationDir.'/competencies.xml', $xmlContent);
    }

    /**
     * Creates the completiondefaults.xml file.
     *
     * @param array<int,array<string,mixed>> $completionData
     */
    private function createCompletionDefaultsXml(array $completionData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<completiondefaults>'.PHP_EOL;
        foreach ($completionData as $completion) {
            $completionState = (int) ($completion['state'] ?? 0);
            $xmlContent .= '  <completion>'.PHP_EOL;
            $xmlContent .= '    <completionstate>'.$completionState.'</completionstate>'.PHP_EOL;
            $xmlContent .= '  </completion>'.PHP_EOL;
        }
        $xmlContent .= '</completiondefaults>';

        file_put_contents($destinationDir.'/completiondefaults.xml', $xmlContent);
    }

    /**
     * Creates the contentbank.xml file.
     *
     * @param array<int,array<string,mixed>> $contentBankData
     */
    private function createContentBankXml(array $contentBankData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<contentbank>'.PHP_EOL;
        foreach ($contentBankData as $content) {
            $id = (int) ($content['id'] ?? 0);
            $name = (string) ($content['name'] ?? 'Content');
            $xmlContent .= '  <content id="'.$id.'">'.htmlspecialchars($name).'</content>'.PHP_EOL;
        }
        $xmlContent .= '</contentbank>';

        file_put_contents($destinationDir.'/contentbank.xml', $xmlContent);
    }

    /**
     * Creates the filters.xml file.
     *
     * @param array<int,array<string,mixed>> $filtersData
     */
    private function createFiltersXml(array $filtersData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<filters>'.PHP_EOL;
        foreach ($filtersData as $filter) {
            $filterName = (string) ($filter['name'] ?? 'filter_example');
            $active = (int) ($filter['active'] ?? 1);

            $xmlContent .= '  <filter>'.PHP_EOL;
            $xmlContent .= '    <filtername>'.htmlspecialchars($filterName).'</filtername>'.PHP_EOL;
            $xmlContent .= '    <active>'.$active.'</active>'.PHP_EOL;
            $xmlContent .= '  </filter>'.PHP_EOL;
        }
        $xmlContent .= '</filters>';

        file_put_contents($destinationDir.'/filters.xml', $xmlContent);
    }

    /**
     * Gather CalendarEvent objects pushed by the builder into $this->course->resources.
     * We DO NOT invent types; we rely on the builder's CalendarEvent class in the same namespace.
     *
     * @return CalendarEvent[]  // from Builder namespace
     */
    private function collectCalendarEvents(): array
    {
        $out = [];
        $resources = $this->course->resources ?? null;

        if (!\is_array($resources)) {
            return $out;
        }

        // Prefer a dedicated 'calendar' bucket if present; otherwise, scan all buckets.
        if (isset($resources['calendar']) && \is_array($resources['calendar'])) {
            foreach ($resources['calendar'] as $item) {
                if ($item instanceof CalendarEvent) {
                    $out[] = $item;
                }
            }
            return $out;
        }

        foreach ($resources as $bucket) {
            if (\is_array($bucket)) {
                foreach ($bucket as $item) {
                    if ($item instanceof CalendarEvent) {
                        $out[] = $item;
                    }
                }
            } elseif ($bucket instanceof CalendarEvent) {
                $out[] = $bucket;
            }
        }

        return $out;
    }

    /**
     * Convert a date-string ('Y-m-d H:i:s') or numeric to timestamp with fallback.
     */
    private function toTimestamp(string $value, int $fallback): int
    {
        if ($value === '') { return $fallback; }
        if (\is_numeric($value)) { return (int) $value; }
        $t = \strtotime($value);
        return false !== $t ? (int) $t : $fallback;
    }
}
