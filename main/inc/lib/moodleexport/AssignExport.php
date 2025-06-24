<?php

/* For licensing terms, see /license.txt */

namespace moodleexport;

use DocumentManager;

/**
 * Class AssignExport.
 *
 * Handles the export of assignments within a course.
 */
class AssignExport extends ActivityExport
{
    /**
     * Export all assign data into a single Moodle assign activity.
     *
     * @param int    $activityId The ID of the assign.
     * @param string $exportDir  The directory where the assign will be exported.
     * @param int    $moduleId   The ID of the module.
     * @param int    $sectionId  The ID of the section.
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        // Prepare the directory where the assign export will be saved
        $assignDir = $this->prepareActivityDirectory($exportDir, 'assign', $moduleId);

        // Retrieve assign data
        $assignData = $this->getData($activityId, $sectionId);

        // Generate XML files for the assign
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
     * Get all the data related to the assign activity.
     */
    public function getData(int $assignId, int $sectionId): ?array
    {
        $work = $this->course->resources[RESOURCE_WORK][$assignId];

        if (empty($work->params['id']) || empty($work->params['title'])) {
            return null;
        }

        $sentDate = !empty($work->params['sent_date']) ? strtotime($work->params['sent_date']) : time();

        $workFiles = getAllDocumentToWork($assignId, $this->course->info['real_id']);
        $files = [];
        if (!empty($workFiles)) {
            foreach ($workFiles as $file) {
                $docData = DocumentManager::get_document_data_by_id($file['document_id'], $this->course->info['code']);
                if (!empty($docData)) {
                    $files[] = [
                        'id' => $file['document_id'],
                        'contenthash' => hash('sha1', basename($docData['path'])),
                    ];
                }
            }
        }

        $adminData = MoodleExport::getAdminUserData();
        $adminId = $adminData['id'];

        return [
            'id' => (int) $work->params['id'],
            'moduleid' => (int) $work->params['id'],
            'modulename' => 'assign',
            'contextid' => $this->course->info['real_id'],
            'sectionid' => $sectionId,
            'sectionnumber' => 0,
            'name' => htmlspecialchars($work->params['title'], ENT_QUOTES),
            'intro' => htmlspecialchars($work->params['description'], ENT_QUOTES),
            'duedate' => $sentDate,
            'gradingduedate' => $sentDate + 7 * 86400,
            'allowsubmissionsfromdate' => $sentDate,
            'timemodified' => time(),
            'grade_item_id' => 0,
            'files' => $files,
            'users' => [$adminId],
            'area_id' => 0,
        ];
    }

    /**
     * Create the grades.xml file for the assign activity.
     */
    protected function createGradesXml(array $data, string $directory): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<activity_gradebook>'.PHP_EOL;
        $xmlContent .= '  <grade_items>'.PHP_EOL;
        $xmlContent .= '    <grade_item id="'.$data['grade_item_id'].'">'.PHP_EOL;
        $xmlContent .= '      <categoryid>1</categoryid>'.PHP_EOL;
        $xmlContent .= '      <itemname>'.htmlspecialchars($data['name']).'</itemname>'.PHP_EOL;
        $xmlContent .= '      <itemtype>mod</itemtype>'.PHP_EOL;
        $xmlContent .= '      <itemmodule>'.$data['modulename'].'</itemmodule>'.PHP_EOL;
        $xmlContent .= '      <iteminstance>'.$data['id'].'</iteminstance>'.PHP_EOL;
        $xmlContent .= '      <itemnumber>0</itemnumber>'.PHP_EOL;
        $xmlContent .= '      <iteminfo>$@NULL@$</iteminfo>'.PHP_EOL;
        $xmlContent .= '      <idnumber></idnumber>'.PHP_EOL;
        $xmlContent .= '      <calculation>$@NULL@$</calculation>'.PHP_EOL;
        $xmlContent .= '      <gradetype>1</gradetype>'.PHP_EOL;
        $xmlContent .= '      <grademax>100.00000</grademax>'.PHP_EOL;
        $xmlContent .= '      <grademin>0.00000</grademin>'.PHP_EOL;
        $xmlContent .= '      <scaleid>$@NULL@$</scaleid>'.PHP_EOL;
        $xmlContent .= '      <outcomeid>$@NULL@$</outcomeid>'.PHP_EOL;
        $xmlContent .= '      <gradepass>0.00000</gradepass>'.PHP_EOL;
        $xmlContent .= '      <multfactor>1.00000</multfactor>'.PHP_EOL;
        $xmlContent .= '      <plusfactor>0.00000</plusfactor>'.PHP_EOL;
        $xmlContent .= '      <aggregationcoef>0.00000</aggregationcoef>'.PHP_EOL;
        $xmlContent .= '      <aggregationcoef2>0.23810</aggregationcoef2>'.PHP_EOL;
        $xmlContent .= '      <weightoverride>0</weightoverride>'.PHP_EOL;
        $xmlContent .= '      <sortorder>5</sortorder>'.PHP_EOL;
        $xmlContent .= '      <display>0</display>'.PHP_EOL;
        $xmlContent .= '      <decimals>$@NULL@$</decimals>'.PHP_EOL;
        $xmlContent .= '      <hidden>0</hidden>'.PHP_EOL;
        $xmlContent .= '      <locked>0</locked>'.PHP_EOL;
        $xmlContent .= '      <locktime>0</locktime>'.PHP_EOL;
        $xmlContent .= '      <needsupdate>0</needsupdate>'.PHP_EOL;
        $xmlContent .= '      <timecreated>'.$data['timemodified'].'</timecreated>'.PHP_EOL;
        $xmlContent .= '      <timemodified>'.$data['timemodified'].'</timemodified>'.PHP_EOL;
        $xmlContent .= '      <grade_grades></grade_grades>'.PHP_EOL;
        $xmlContent .= '    </grade_item>'.PHP_EOL;
        $xmlContent .= '  </grade_items>'.PHP_EOL;
        $xmlContent .= '  <grade_letters></grade_letters>'.PHP_EOL;
        $xmlContent .= '</activity_gradebook>';

        $this->createXmlFile('grades', $xmlContent, $directory);
    }

    /**
     * Create the XML file for the assign activity.
     */
    private function createAssignXml(array $assignData, string $assignDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<activity id="'.$assignData['id'].'" moduleid="'.$assignData['moduleid'].'" modulename="assign" contextid="'.$assignData['contextid'].'">'.PHP_EOL;
        $xmlContent .= '  <assign id="'.$assignData['id'].'">'.PHP_EOL;
        $xmlContent .= '    <name>'.htmlspecialchars($assignData['name']).'</name>'.PHP_EOL;
        $xmlContent .= '    <intro><![CDATA['.$assignData['intro'].']]></intro>'.PHP_EOL;
        $xmlContent .= '    <introformat>1</introformat>'.PHP_EOL;
        $xmlContent .= '    <alwaysshowdescription>1</alwaysshowdescription>'.PHP_EOL;
        $xmlContent .= '    <submissiondrafts>0</submissiondrafts>'.PHP_EOL;
        $xmlContent .= '    <sendnotifications>0</sendnotifications>'.PHP_EOL;
        $xmlContent .= '    <sendlatenotifications>0</sendlatenotifications>'.PHP_EOL;
        $xmlContent .= '    <sendstudentnotifications>1</sendstudentnotifications>'.PHP_EOL;
        $xmlContent .= '    <duedate>'.$assignData['duedate'].'</duedate>'.PHP_EOL;
        $xmlContent .= '    <cutoffdate>0</cutoffdate>'.PHP_EOL;
        $xmlContent .= '    <gradingduedate>'.$assignData['gradingduedate'].'</gradingduedate>'.PHP_EOL;
        $xmlContent .= '    <allowsubmissionsfromdate>'.$assignData['allowsubmissionsfromdate'].'</allowsubmissionsfromdate>'.PHP_EOL;
        $xmlContent .= '    <grade>100</grade>'.PHP_EOL;
        $xmlContent .= '    <timemodified>'.$assignData['timemodified'].'</timemodified>'.PHP_EOL;
        $xmlContent .= '    <completionsubmit>1</completionsubmit>'.PHP_EOL;
        $xmlContent .= '    <requiresubmissionstatement>0</requiresubmissionstatement>'.PHP_EOL;
        $xmlContent .= '    <teamsubmission>0</teamsubmission>'.PHP_EOL;
        $xmlContent .= '    <requireallteammemberssubmit>0</requireallteammemberssubmit>'.PHP_EOL;
        $xmlContent .= '    <teamsubmissiongroupingid>0</teamsubmissiongroupingid>'.PHP_EOL;
        $xmlContent .= '    <blindmarking>0</blindmarking>'.PHP_EOL;
        $xmlContent .= '    <hidegrader>0</hidegrader>'.PHP_EOL;
        $xmlContent .= '    <revealidentities>0</revealidentities>'.PHP_EOL;
        $xmlContent .= '    <attemptreopenmethod>none</attemptreopenmethod>'.PHP_EOL;
        $xmlContent .= '    <maxattempts>1</maxattempts>'.PHP_EOL;
        $xmlContent .= '    <markingworkflow>0</markingworkflow>'.PHP_EOL;
        $xmlContent .= '    <markingallocation>0</markingallocation>'.PHP_EOL;
        $xmlContent .= '    <preventsubmissionnotingroup>0</preventsubmissionnotingroup>'.PHP_EOL;
        $xmlContent .= '    <userflags></userflags>'.PHP_EOL;
        $xmlContent .= '    <submissions></submissions>'.PHP_EOL;
        $xmlContent .= '    <grades></grades>'.PHP_EOL;
        $xmlContent .= '  </assign>'.PHP_EOL;
        $xmlContent .= '</activity>';

        $this->createXmlFile('assign', $xmlContent, $assignDir);
    }

    /**
     * Create the grading.xml file for the assign activity.
     */
    private function createGradingXml(array $data, string $assignDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<areas>'.PHP_EOL;
        $xmlContent .= '  <area id="'.$data['area_id'].'">'.PHP_EOL;
        $xmlContent .= '    <areaname>submissions</areaname>'.PHP_EOL;
        $xmlContent .= '    <activemethod>$@NULL@$</activemethod>'.PHP_EOL;
        $xmlContent .= '    <definitions></definitions>'.PHP_EOL;
        $xmlContent .= '  </area>'.PHP_EOL;
        $xmlContent .= '</areas>';

        $this->createXmlFile('grading', $xmlContent, $assignDir);
    }
}
