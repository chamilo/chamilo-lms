<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleExport;

use const PHP_EOL;

/**
 * Handles the export of glossaries within a course.
 */
class GlossaryExport extends ActivityExport
{
    /**
     * Export all glossary terms into a single Moodle glossary.
     *
     * @param int    $activityId the ID of the glossary (logical wrapper id)
     * @param string $exportDir  destination base directory for the export
     * @param int    $moduleId   module id used to name the activity folder
     * @param int    $sectionId  moodle section id where the activity will live
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        // Prepare destination directory for the activity
        $glossaryDir = $this->prepareActivityDirectory($exportDir, 'glossary', (int) $moduleId);

        // Collect data
        $glossaryData = $this->getData((int) $activityId, (int) $sectionId);

        // Generate XML files for the glossary
        $this->createGlossaryXml($glossaryData, $glossaryDir);
        $this->createModuleXml($glossaryData, $glossaryDir);
        $this->createGradesXml($glossaryData, $glossaryDir);
        $this->createGradeHistoryXml($glossaryData, $glossaryDir);
        $this->createInforefXml($glossaryData, $glossaryDir); // relies on 'users' and 'files' keys
        $this->createRolesXml($glossaryData, $glossaryDir);
        $this->createCalendarXml($glossaryData, $glossaryDir);
        $this->createCommentsXml($glossaryData, $glossaryDir);
        $this->createCompetenciesXml($glossaryData, $glossaryDir);
        $this->createFiltersXml($glossaryData, $glossaryDir);
    }

    /**
     * Gather all terms from the course and group them under a single glossary activity.
     */
    public function getData(int $glossaryId, int $sectionId): array
    {
        $adminData = MoodleExport::getAdminUserData();
        $adminId = (int) ($adminData['id'] ?? 0);

        $entries = [];
        if (!empty($this->course->resources['glossary'])) {
            foreach ($this->course->resources['glossary'] as $g) {
                $entries[] = [
                    'id' => (int) ($g->glossary_id ?? 0),
                    'userid' => $adminId,
                    'concept' => (string) ($g->name ?? ''),
                    'definition' => (string) ($g->description ?? ''),
                    'timecreated' => time(),
                    'timemodified' => time(),
                ];
            }
        }

        return [
            'id' => $glossaryId,
            'moduleid' => $glossaryId,
            'modulename' => 'glossary',
            'contextid' => (int) ($this->course->info['real_id'] ?? 0),
            'name' => get_lang('Glossary'),
            'description' => '',
            'timecreated' => time(),
            'timemodified' => time(),
            'sectionid' => $sectionId,
            'sectionnumber' => 0,
            'userid' => $adminId,
            'entries' => $entries,
            'users' => [$adminId],
            'files' => [], // no file refs for glossary entries (plain text)
        ];
    }

    /**
     * Create glossary.xml with all entries combined.
     */
    private function createGlossaryXml(array $glossaryData, string $glossaryDir): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<activity id="'.$glossaryData['id'].'" moduleid="'.$glossaryData['moduleid'].'" modulename="'.$glossaryData['modulename'].'" contextid="'.$glossaryData['contextid'].'">'.PHP_EOL;
        $xml .= '  <glossary id="'.$glossaryData['id'].'">'.PHP_EOL;
        $xml .= '    <name>'.htmlspecialchars((string) $glossaryData['name']).'</name>'.PHP_EOL;
        $xml .= '    <intro></intro>'.PHP_EOL;
        $xml .= '    <introformat>1</introformat>'.PHP_EOL;
        $xml .= '    <allowduplicatedentries>0</allowduplicatedentries>'.PHP_EOL;
        $xml .= '    <displayformat>dictionary</displayformat>'.PHP_EOL;
        $xml .= '    <mainglossary>0</mainglossary>'.PHP_EOL;
        $xml .= '    <showspecial>1</showspecial>'.PHP_EOL;
        $xml .= '    <showalphabet>1</showalphabet>'.PHP_EOL;
        $xml .= '    <showall>1</showall>'.PHP_EOL;
        $xml .= '    <allowcomments>0</allowcomments>'.PHP_EOL;
        $xml .= '    <allowprintview>1</allowprintview>'.PHP_EOL;
        $xml .= '    <usedynalink>1</usedynalink>'.PHP_EOL;
        $xml .= '    <defaultapproval>1</defaultapproval>'.PHP_EOL;
        $xml .= '    <globalglossary>0</globalglossary>'.PHP_EOL;
        $xml .= '    <entbypage>10</entbypage>'.PHP_EOL;
        $xml .= '    <editalways>0</editalways>'.PHP_EOL;
        $xml .= '    <rsstype>0</rsstype>'.PHP_EOL;
        $xml .= '    <rssarticles>0</rssarticles>'.PHP_EOL;
        $xml .= '    <assessed>0</assessed>'.PHP_EOL;
        $xml .= '    <assesstimestart>0</assesstimestart>'.PHP_EOL;
        $xml .= '    <assesstimefinish>0</assesstimefinish>'.PHP_EOL;
        $xml .= '    <scale>100</scale>'.PHP_EOL;
        $xml .= '    <timecreated>'.$glossaryData['timecreated'].'</timecreated>'.PHP_EOL;
        $xml .= '    <timemodified>'.$glossaryData['timemodified'].'</timemodified>'.PHP_EOL;
        $xml .= '    <completionentries>0</completionentries>'.PHP_EOL;

        // Entries
        $xml .= '    <entries>'.PHP_EOL;
        foreach ($glossaryData['entries'] as $entry) {
            $xml .= '      <entry id="'.$entry['id'].'">'.PHP_EOL;
            $xml .= '        <userid>'.$entry['userid'].'</userid>'.PHP_EOL;
            $xml .= '        <concept>'.htmlspecialchars((string) $entry['concept']).'</concept>'.PHP_EOL;
            $xml .= '        <definition><![CDATA['.$entry['definition'].']]></definition>'.PHP_EOL;
            $xml .= '        <definitionformat>1</definitionformat>'.PHP_EOL;
            $xml .= '        <definitiontrust>0</definitiontrust>'.PHP_EOL;
            $xml .= '        <attachment></attachment>'.PHP_EOL;
            $xml .= '        <timecreated>'.$entry['timecreated'].'</timecreated>'.PHP_EOL;
            $xml .= '        <timemodified>'.$entry['timemodified'].'</timemodified>'.PHP_EOL;
            $xml .= '        <teacherentry>1</teacherentry>'.PHP_EOL;
            $xml .= '        <sourceglossaryid>0</sourceglossaryid>'.PHP_EOL;
            $xml .= '        <usedynalink>0</usedynalink>'.PHP_EOL;
            $xml .= '        <casesensitive>0</casesensitive>'.PHP_EOL;
            $xml .= '        <fullmatch>0</fullmatch>'.PHP_EOL;
            $xml .= '        <approved>1</approved>'.PHP_EOL;
            $xml .= '        <ratings>'.PHP_EOL;
            $xml .= '        </ratings>'.PHP_EOL;
            $xml .= '      </entry>'.PHP_EOL;
        }
        $xml .= '    </entries>'.PHP_EOL;

        $xml .= '    <entriestags></entriestags>'.PHP_EOL;
        $xml .= '    <categories></categories>'.PHP_EOL;
        $xml .= '  </glossary>'.PHP_EOL;
        $xml .= '</activity>';

        $this->createXmlFile('glossary', $xml, $glossaryDir);
    }
}
