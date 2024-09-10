<?php

/* For licensing terms, see /license.txt */

namespace moodleexport;

use Exception;

/**
 * Class CourseExport.
 *
 * @package moodleexport
 */
class CourseExport
{
    private $course;
    private $courseInfo;
    private $activities;

    public function __construct($course, $activities)
    {
        $this->course = $course;
        $this->courseInfo = api_get_course_info($course->code);
        $this->activities = $activities;

        if (!$this->courseInfo) {
            throw new Exception("Course not found.");
        }
    }

    /**
     * Export the course-related files to the appropriate directory.
     */
    public function exportCourse(string $exportDir): void
    {
        $courseDir = $exportDir . '/course';
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
     * Create course.xml based on the course data from MoodleExport.
     */
    private function createCourseXml(string $destinationDir): void
    {
        $courseId = $this->courseInfo['real_id'] ?? 0;
        $contextId = $this->courseInfo['real_id'] ?? 1;
        $shortname = $this->courseInfo['code'] ?? 'Unknown Course';
        $fullname = $this->courseInfo['title'] ?? 'Unknown Fullname';
        $showgrades = $this->courseInfo['showgrades'] ?? 0;
        $startdate = $this->courseInfo['startdate'] ?? time();
        $enddate = $this->courseInfo['enddate'] ?? time() + (60 * 60 * 24 * 365);
        $visible = $this->courseInfo['visible'] ?? 1;
        $enablecompletion = $this->courseInfo['enablecompletion'] ?? 0;

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<course id="' . $courseId . '" contextid="' . $contextId . '">' . PHP_EOL;
        $xmlContent .= '  <shortname>' . htmlspecialchars($shortname) . '</shortname>' . PHP_EOL;
        $xmlContent .= '  <fullname>' . htmlspecialchars($fullname) . '</fullname>' . PHP_EOL;
        $xmlContent .= '  <format>topics</format>' . PHP_EOL;
        $xmlContent .= '  <showgrades>' . $showgrades . '</showgrades>' . PHP_EOL;
        $xmlContent .= '  <startdate>' . $startdate . '</startdate>' . PHP_EOL;
        $xmlContent .= '  <enddate>' . $enddate . '</enddate>' . PHP_EOL;
        $xmlContent .= '  <visible>' . $visible . '</visible>' . PHP_EOL;
        $xmlContent .= '  <enablecompletion>' . $enablecompletion . '</enablecompletion>' . PHP_EOL;
        $xmlContent .= '  <category id="1">' . PHP_EOL;
        $xmlContent .= '    <name>Miscellaneous</name>' . PHP_EOL;
        $xmlContent .= '  </category>' . PHP_EOL;
        $xmlContent .= '</course>';

        file_put_contents($destinationDir . '/course.xml', $xmlContent);
    }

    /**
     * Create enrolments.xml based on the course data from MoodleExport.
     */
    private function createEnrolmentsXml(array $enrolmentsData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<enrolments>' . PHP_EOL;
        foreach ($enrolmentsData as $enrol) {
            $id = $enrol['id'] ?? 0;
            $type = $enrol['type'] ?? 'manual';
            $status = $enrol['status'] ?? 1;

            $xmlContent .= '  <enrol id="' . $id . '">' . PHP_EOL;
            $xmlContent .= '    <enrol>' . htmlspecialchars($type) . '</enrol>' . PHP_EOL;
            $xmlContent .= '    <status>' . $status . '</status>' . PHP_EOL;
            $xmlContent .= '  </enrol>' . PHP_EOL;
        }
        $xmlContent .= '</enrolments>';

        file_put_contents($destinationDir . '/enrolments.xml', $xmlContent);
    }

    /**
     * Creates the inforef.xml file with file references and question categories.
     */
    private function createInforefXml(string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<inforef>' . PHP_EOL;

        $questionCategories = [];
        foreach ($this->activities as $activity) {
            if ($activity['modulename'] === 'quiz') {
                $quizExport = new QuizExport($this->course);
                $quizData = $quizExport->getData($activity['id'], $activity['sectionid']);
                foreach ($quizData['questions'] as $question) {
                    $categoryId = $question['questioncategoryid'];
                    if (!in_array($categoryId, $questionCategories, true)) {
                        $questionCategories[] = $categoryId;
                    }
                }
            }
        }

        $xmlContent .= '  <question_categoryref>' . PHP_EOL;
        foreach ($questionCategories as $categoryId) {
            $xmlContent .= '    <question_category>' . PHP_EOL;
            $xmlContent .= '      <id>' . $categoryId . '</id>' . PHP_EOL;
            $xmlContent .= '    </question_category>' . PHP_EOL;
        }
        $xmlContent .= '  </question_categoryref>' . PHP_EOL;
        $xmlContent .= '</inforef>' . PHP_EOL;

        file_put_contents($destinationDir . '/inforef.xml', $xmlContent);
    }

    /**
     * Creates the roles.xml file.
     */
    private function createRolesXml(array $rolesData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<roles>' . PHP_EOL;
        foreach ($rolesData as $role) {
            $roleName = $role['name'] ?? 'Student';
            $xmlContent .= '  <role>' . PHP_EOL;
            $xmlContent .= '    <name>' . htmlspecialchars($roleName) . '</name>' . PHP_EOL;
            $xmlContent .= '  </role>' . PHP_EOL;
        }
        $xmlContent .= '</roles>';

        file_put_contents($destinationDir . '/roles.xml', $xmlContent);
    }

    /**
     * Creates the calendar.xml file.
     */
    private function createCalendarXml(array $calendarData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<calendar>' . PHP_EOL;
        foreach ($calendarData as $event) {
            $eventName = $event['name'] ?? 'Event';
            $timestart = $event['timestart'] ?? time();
            $duration = $event['duration'] ?? 3600;

            $xmlContent .= '  <event>' . PHP_EOL;
            $xmlContent .= '    <name>' . htmlspecialchars($eventName) . '</name>' . PHP_EOL;
            $xmlContent .= '    <timestart>' . $timestart . '</timestart>' . PHP_EOL;
            $xmlContent .= '    <duration>' . $duration . '</duration>' . PHP_EOL;
            $xmlContent .= '  </event>' . PHP_EOL;
        }
        $xmlContent .= '</calendar>';

        file_put_contents($destinationDir . '/calendar.xml', $xmlContent);
    }

    /**
     * Creates the comments.xml file.
     */
    private function createCommentsXml(array $commentsData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<comments>' . PHP_EOL;
        foreach ($commentsData as $comment) {
            $content = $comment['content'] ?? 'No comment';
            $author = $comment['author'] ?? 'Anonymous';

            $xmlContent .= '  <comment>' . PHP_EOL;
            $xmlContent .= '    <content>' . htmlspecialchars($content) . '</content>' . PHP_EOL;
            $xmlContent .= '    <author>' . htmlspecialchars($author) . '</author>' . PHP_EOL;
            $xmlContent .= '  </comment>' . PHP_EOL;
        }
        $xmlContent .= '</comments>';

        file_put_contents($destinationDir . '/comments.xml', $xmlContent);
    }

    /**
     * Creates the competencies.xml file.
     */
    private function createCompetenciesXml(array $competenciesData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<competencies>' . PHP_EOL;
        foreach ($competenciesData as $competency) {
            $name = $competency['name'] ?? 'Competency';
            $xmlContent .= '  <competency>' . PHP_EOL;
            $xmlContent .= '    <name>' . htmlspecialchars($name) . '</name>' . PHP_EOL;
            $xmlContent .= '  </competency>' . PHP_EOL;
        }
        $xmlContent .= '</competencies>';

        file_put_contents($destinationDir . '/competencies.xml', $xmlContent);
    }

    /**
     * Creates the completiondefaults.xml file.
     */
    private function createCompletionDefaultsXml(array $completionData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<completiondefaults>' . PHP_EOL;
        foreach ($completionData as $completion) {
            $completionState = $completion['state'] ?? 0;
            $xmlContent .= '  <completion>' . PHP_EOL;
            $xmlContent .= '    <completionstate>' . $completionState . '</completionstate>' . PHP_EOL;
            $xmlContent .= '  </completion>' . PHP_EOL;
        }
        $xmlContent .= '</completiondefaults>';

        file_put_contents($destinationDir . '/completiondefaults.xml', $xmlContent);
    }

    /**
     * Creates the contentbank.xml file.
     */
    private function createContentBankXml(array $contentBankData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<contentbank>' . PHP_EOL;
        foreach ($contentBankData as $content) {
            $id = $content['id'] ?? 0;
            $name = $content['name'] ?? 'Content';
            $xmlContent .= '  <content id="' . $id . '">' . htmlspecialchars($name) . '</content>' . PHP_EOL;
        }
        $xmlContent .= '</contentbank>';

        file_put_contents($destinationDir . '/contentbank.xml', $xmlContent);
    }

    /**
     * Creates the filters.xml file.
     */
    private function createFiltersXml(array $filtersData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<filters>' . PHP_EOL;
        foreach ($filtersData as $filter) {
            $filterName = $filter['name'] ?? 'filter_example';
            $active = $filter['active'] ?? 1;

            $xmlContent .= '  <filter>' . PHP_EOL;
            $xmlContent .= '    <filtername>' . htmlspecialchars($filterName) . '</filtername>' . PHP_EOL;
            $xmlContent .= '    <active>' . $active . '</active>' . PHP_EOL;
            $xmlContent .= '  </filter>' . PHP_EOL;
        }
        $xmlContent .= '</filters>';

        file_put_contents($destinationDir . '/filters.xml', $xmlContent);
    }
}
