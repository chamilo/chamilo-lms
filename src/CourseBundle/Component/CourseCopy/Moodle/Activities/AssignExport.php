<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use DocumentManager;

use const ENT_XML1;
use const PHP_EOL;

/**
 * Exports a Chamilo assignment (work) to a Moodle 'assign' activity.
 * Assumes ActivityExport base provides:
 *   - $this->course (with ->info and ->resources)
 *   - prepareActivityDirectory(), createXmlFile(), createModuleXml(),
 *     createGradesXml(), createInforefXml(), createGradeHistoryXml(),
 *     createRolesXml(), createCommentsXml(), createCalendarXml(),
 *     createFiltersXml().
 */
final class AssignExport extends ActivityExport
{
    /**
     * Export a single assign into the destination directory.
     */
    public function export(int $activityId, string $exportDir, int $moduleId, int $sectionId): void
    {
        // 1) Ensure destination folder like activities/assign_<moduleId>
        $assignDir = $this->prepareActivityDirectory($exportDir, 'assign', $moduleId);

        // 2) Gather data
        $assignData = $this->getData($activityId, $sectionId);
        if (null === $assignData) {
            // Nothing to export (missing or invalid source)
            return;
        }

        // 3) Write activity files
        $this->createAssignXml($assignData, $assignDir);
        $this->createModuleXml($assignData, $assignDir);
        $this->createGradesXml($assignData, $assignDir);
        $this->createGradingXml($assignData, $assignDir);
        $this->createInforefXml($assignData, $assignDir);
        $this->createGradeHistoryXml($assignData, $assignDir);
        $this->createRolesXml($assignData, $assignDir);
        $this->createCommentsXml($assignData, $assignDir);
        $this->createCalendarXml($assignData, $assignDir);
        $this->createFiltersXml($assignData, $assignDir);
    }

    /**
     * Build the data array used by XML writers.
     */
    public function getData(int $assignId, int $sectionId): ?array
    {
        $work = $this->course->resources[RESOURCE_WORK][$assignId] ?? null;
        if (!$work || empty($work->params['id']) || empty($work->params['title'])) {
            return null;
        }

        $sentDate = !empty($work->params['sent_date'])
            ? (int) strtotime((string) $work->params['sent_date'])
            : time();

        // Collect attached documents for this work
        $files = [];
        $workFiles = getAllDocumentToWork($assignId, (int) $this->course->info['real_id']) ?? [];
        foreach ($workFiles as $file) {
            $docId = (int) ($file['document_id'] ?? 0);
            if ($docId <= 0) {
                continue;
            }
            $docData = DocumentManager::get_document_data_by_id($docId, (string) $this->course->info['code']);
            if (!empty($docData) && isset($docData['path'])) {
                $files[] = [
                    'id' => $docId,
                    // NOTE: Moodle uses filepool IDs. We keep a stable hash based on basename as a fallback.
                    'contenthash' => sha1((string) basename((string) $docData['path'])),
                    'filename' => (string) basename((string) $docData['path']),
                    'filepath' => '/Documents/',
                ];
            }
        }

        // Prefer a base helper; if your base doesn't have it, replace with your static service
        $admin = method_exists($this, 'getAdminUserData')
            ? $this->getAdminUserData()
            : ['id' => 2];

        return [
            'id' => (int) $work->params['id'],
            'moduleid' => (int) $work->params['id'],
            'modulename' => 'assign',
            'contextid' => (int) $this->course->info['real_id'],
            'sectionid' => $sectionId,
            'sectionnumber' => 0,
            'name' => (string) $work->params['title'],
            // Keep raw HTML; assign.xml will wrap it in CDATA
            'intro' => (string) ($work->params['description'] ?? ''),
            'duedate' => $sentDate,
            'gradingduedate' => $sentDate + 7 * 86400,
            'allowsubmissionsfromdate' => $sentDate,
            'timemodified' => time(),
            'grade_item_id' => 0,
            'files' => $files,
            'users' => [$admin['id']],
            'area_id' => 0,
        ];
    }

    /**
     * Write activity_gradebook/grades.xml for this activity.
     */
    protected function createGradesXml(array $data, string $directory): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<activity_gradebook>'.PHP_EOL;
        $xml .= '  <grade_items>'.PHP_EOL;
        $xml .= '    <grade_item id="'.(int) $data['grade_item_id'].'">'.PHP_EOL;
        $xml .= '      <categoryid>1</categoryid>'.PHP_EOL;
        $xml .= '      <itemname>'.\htmlspecialchars((string) $data['name'], ENT_XML1).'</itemname>'.PHP_EOL;
        $xml .= '      <itemtype>mod</itemtype>'.PHP_EOL;
        $xml .= '      <itemmodule>'.$data['modulename'].'</itemmodule>'.PHP_EOL;
        $xml .= '      <iteminstance>'.$data['id'].'</iteminstance>'.PHP_EOL;
        $xml .= '      <itemnumber>0</itemnumber>'.PHP_EOL;
        $xml .= '      <iteminfo>$@NULL@$</iteminfo>'.PHP_EOL;
        $xml .= '      <idnumber></idnumber>'.PHP_EOL;
        $xml .= '      <calculation>$@NULL@$</calculation>'.PHP_EOL;
        $xml .= '      <gradetype>1</gradetype>'.PHP_EOL;
        $xml .= '      <grademax>100.00000</grademax>'.PHP_EOL;
        $xml .= '      <grademin>0.00000</grademin>'.PHP_EOL;
        $xml .= '      <scaleid>$@NULL@$</scaleid>'.PHP_EOL;
        $xml .= '      <outcomeid>$@NULL@$</outcomeid>'.PHP_EOL;
        $xml .= '      <gradepass>0.00000</gradepass>'.PHP_EOL;
        $xml .= '      <multfactor>1.00000</multfactor>'.PHP_EOL;
        $xml .= '      <plusfactor>0.00000</plusfactor>'.PHP_EOL;
        $xml .= '      <aggregationcoef>0.00000</aggregationcoef>'.PHP_EOL;
        $xml .= '      <aggregationcoef2>0.23810</aggregationcoef2>'.PHP_EOL;
        $xml .= '      <weightoverride>0</weightoverride>'.PHP_EOL;
        $xml .= '      <sortorder>5</sortorder>'.PHP_EOL;
        $xml .= '      <display>0</display>'.PHP_EOL;
        $xml .= '      <decimals>$@NULL@$</decimals>'.PHP_EOL;
        $xml .= '      <hidden>0</hidden>'.PHP_EOL;
        $xml .= '      <locked>0</locked>'.PHP_EOL;
        $xml .= '      <locktime>0</locktime>'.PHP_EOL;
        $xml .= '      <needsupdate>0</needsupdate>'.PHP_EOL;
        $xml .= '      <timecreated>'.$data['timemodified'].'</timecreated>'.PHP_EOL;
        $xml .= '      <timemodified>'.$data['timemodified'].'</timemodified>'.PHP_EOL;
        $xml .= '      <grade_grades></grade_grades>'.PHP_EOL;
        $xml .= '    </grade_item>'.PHP_EOL;
        $xml .= '  </grade_items>'.PHP_EOL;
        $xml .= '  <grade_letters></grade_letters>'.PHP_EOL;
        $xml .= '</activity_gradebook>';

        $this->createXmlFile('grades', $xml, $directory);
    }

    /**
     * Write assign/assign.xml (main activity definition).
     * Uses CDATA for 'intro' to preserve HTML safely.
     */
    private function createAssignXml(array $d, string $dir): void
    {
        $name = \htmlspecialchars((string) $d['name'], ENT_XML1);
        $intro = (string) $d['intro'];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<activity id="'.$d['id'].'" moduleid="'.$d['moduleid'].'" modulename="assign" contextid="'.$d['contextid'].'">'.PHP_EOL;
        $xml .= '  <assign id="'.$d['id'].'">'.PHP_EOL;
        $xml .= '    <name>'.$name.'</name>'.PHP_EOL;
        $xml .= '    <intro><![CDATA['.$intro.']]></intro>'.PHP_EOL;
        $xml .= '    <introformat>1</introformat>'.PHP_EOL;
        $xml .= '    <alwaysshowdescription>1</alwaysshowdescription>'.PHP_EOL;
        $xml .= '    <submissiondrafts>0</submissiondrafts>'.PHP_EOL;
        $xml .= '    <sendnotifications>0</sendnotifications>'.PHP_EOL;
        $xml .= '    <sendlatenotifications>0</sendlatenotifications>'.PHP_EOL;
        $xml .= '    <sendstudentnotifications>1</sendstudentnotifications>'.PHP_EOL;
        $xml .= '    <duedate>'.$d['duedate'].'</duedate>'.PHP_EOL;
        $xml .= '    <cutoffdate>0</cutoffdate>'.PHP_EOL;
        $xml .= '    <gradingduedate>'.$d['gradingduedate'].'</gradingduedate>'.PHP_EOL;
        $xml .= '    <allowsubmissionsfromdate>'.$d['allowsubmissionsfromdate'].'</allowsubmissionsfromdate>'.PHP_EOL;
        $xml .= '    <grade>100</grade>'.PHP_EOL;
        $xml .= '    <timemodified>'.$d['timemodified'].'</timemodified>'.PHP_EOL;
        $xml .= '    <completionsubmit>1</completionsubmit>'.PHP_EOL;
        $xml .= '    <requiresubmissionstatement>0</requiresubmissionstatement>'.PHP_EOL;
        $xml .= '    <teamsubmission>0</teamsubmission>'.PHP_EOL;
        $xml .= '    <requireallteammemberssubmit>0</requireallteammemberssubmit>'.PHP_EOL;
        $xml .= '    <teamsubmissiongroupingid>0</teamsubmissiongroupingid>'.PHP_EOL;
        $xml .= '    <blindmarking>0</blindmarking>'.PHP_EOL;
        $xml .= '    <hidegrader>0</hidegrader>'.PHP_EOL;
        $xml .= '    <revealidentities>0</revealidentities>'.PHP_EOL;
        $xml .= '    <attemptreopenmethod>none</attemptreopenmethod>'.PHP_EOL;
        $xml .= '    <maxattempts>1</maxattempts>'.PHP_EOL;
        $xml .= '    <markingworkflow>0</markingworkflow>'.PHP_EOL;
        $xml .= '    <markingallocation>0</markingallocation>'.PHP_EOL;
        $xml .= '    <preventsubmissionnotingroup>0</preventsubmissionnotingroup>'.PHP_EOL;
        $xml .= '    <userflags></userflags>'.PHP_EOL;
        $xml .= '    <submissions></submissions>'.PHP_EOL;
        $xml .= '    <grades></grades>'.PHP_EOL;
        $xml .= '  </assign>'.PHP_EOL;
        $xml .= '</activity>';

        $this->createXmlFile('assign', $xml, $dir);
    }

    /**
     * Write assign/grading.xml (grading areas for this activity).
     */
    private function createGradingXml(array $d, string $dir): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<areas>'.PHP_EOL;
        $xml .= '  <area id="'.(int) ($d['area_id'] ?? 0).'">'.PHP_EOL;
        $xml .= '    <areaname>submissions</areaname>'.PHP_EOL;
        $xml .= '    <activemethod>$@NULL@$</activemethod>'.PHP_EOL;
        $xml .= '    <definitions></definitions>'.PHP_EOL;
        $xml .= '  </area>'.PHP_EOL;
        $xml .= '</areas>';

        $this->createXmlFile('grading', $xml, $dir);
    }
}
