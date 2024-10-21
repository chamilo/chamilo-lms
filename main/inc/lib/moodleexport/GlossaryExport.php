<?php

/* For licensing terms, see /license.txt */

namespace moodleexport;

/**
 * Class GlossaryExport.
 *
 * Handles the export of glossaries within a course.
 */
class GlossaryExport extends ActivityExport
{
    /**
     * Export all glossary terms into a single Moodle glossary.
     *
     * @param int    $activityId The ID of the glossary.
     * @param string $exportDir  The directory where the glossary will be exported.
     * @param int    $moduleId   The ID of the module.
     * @param int    $sectionId  The ID of the section.
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        // Prepare the directory where the glossary export will be saved
        $glossaryDir = $this->prepareActivityDirectory($exportDir, 'glossary', $moduleId);

        // Retrieve glossary data
        $glossaryData = $this->getData($activityId, $sectionId);

        // Generate XML files for the glossary
        $this->createGlossaryXml($glossaryData, $glossaryDir);
        $this->createModuleXml($glossaryData, $glossaryDir);
        $this->createGradesXml($glossaryData, $glossaryDir);
        $this->createGradeHistoryXml($glossaryData, $glossaryDir);
        $this->createInforefXml($glossaryData, $glossaryDir);
        $this->createRolesXml($glossaryData, $glossaryDir);
        $this->createCalendarXml($glossaryData, $glossaryDir);
        $this->createCommentsXml($glossaryData, $glossaryDir);
        $this->createCompetenciesXml($glossaryData, $glossaryDir);
        $this->createFiltersXml($glossaryData, $glossaryDir);
    }

    /**
     * Get all terms from the course and group them into a single glossary.
     */
    public function getData(int $glossaryId, int $sectionId): ?array
    {
        $adminData = MoodleExport::getAdminUserData();
        $adminId = $adminData['id'];

        $glossaryEntries = [];
        foreach ($this->course->resources['glossary'] as $glossary) {
            $glossaryEntries[] = [
                'id' => $glossary->glossary_id,
                'userid' => $adminId,
                'concept' => $glossary->name,
                'definition' => $glossary->description,
                'timecreated' => time(),
                'timemodified' => time(),
            ];
        }

        // Return the glossary data with all terms included
        return [
            'id' => $glossaryId,
            'moduleid' => $glossaryId,
            'modulename' => 'glossary',
            'contextid' => $this->course->info['real_id'],
            'name' => get_lang('Glossary'),
            'description' => '',
            'timecreated' => time(),
            'timemodified' => time(),
            'sectionid' => $sectionId,
            'sectionnumber' => 0,
            'userid' => $adminId,
            'entries' => $glossaryEntries,
            'users' => [$adminId],
            'files' => [],
        ];
    }

    /**
     * Create the XML file for the glossary with all terms combined.
     */
    private function createGlossaryXml(array $glossaryData, string $glossaryDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<activity id="'.$glossaryData['id'].'" moduleid="'.$glossaryData['moduleid'].'" modulename="'.$glossaryData['modulename'].'" contextid="'.$glossaryData['contextid'].'">'.PHP_EOL;
        $xmlContent .= '  <glossary id="'.$glossaryData['id'].'">'.PHP_EOL;
        $xmlContent .= '    <name>'.htmlspecialchars($glossaryData['name']).'</name>'.PHP_EOL;
        $xmlContent .= '    <intro></intro>'.PHP_EOL;
        $xmlContent .= '    <introformat>1</introformat>'.PHP_EOL;
        $xmlContent .= '    <allowduplicatedentries>0</allowduplicatedentries>'.PHP_EOL;
        $xmlContent .= '    <displayformat>dictionary</displayformat>'.PHP_EOL;
        $xmlContent .= '    <mainglossary>0</mainglossary>'.PHP_EOL;
        $xmlContent .= '    <showspecial>1</showspecial>'.PHP_EOL;
        $xmlContent .= '    <showalphabet>1</showalphabet>'.PHP_EOL;
        $xmlContent .= '    <showall>1</showall>'.PHP_EOL;
        $xmlContent .= '    <allowcomments>0</allowcomments>'.PHP_EOL;
        $xmlContent .= '    <allowprintview>1</allowprintview>'.PHP_EOL;
        $xmlContent .= '    <usedynalink>1</usedynalink>'.PHP_EOL;
        $xmlContent .= '    <defaultapproval>1</defaultapproval>'.PHP_EOL;
        $xmlContent .= '    <globalglossary>0</globalglossary>'.PHP_EOL;
        $xmlContent .= '    <entbypage>10</entbypage>'.PHP_EOL;
        $xmlContent .= '    <editalways>0</editalways>'.PHP_EOL;
        $xmlContent .= '    <rsstype>0</rsstype>'.PHP_EOL;
        $xmlContent .= '    <rssarticles>0</rssarticles>'.PHP_EOL;
        $xmlContent .= '    <assessed>0</assessed>'.PHP_EOL;
        $xmlContent .= '    <assesstimestart>0</assesstimestart>'.PHP_EOL;
        $xmlContent .= '    <assesstimefinish>0</assesstimefinish>'.PHP_EOL;
        $xmlContent .= '    <scale>100</scale>'.PHP_EOL;
        $xmlContent .= '    <timecreated>'.$glossaryData['timecreated'].'</timecreated>'.PHP_EOL;
        $xmlContent .= '    <timemodified>'.$glossaryData['timemodified'].'</timemodified>'.PHP_EOL;
        $xmlContent .= '    <completionentries>0</completionentries>'.PHP_EOL;
        $xmlContent .= '    <entries>'.PHP_EOL;

        // Add glossary terms (entries)
        foreach ($glossaryData['entries'] as $entry) {
            $xmlContent .= '      <entry id="'.$entry['id'].'">'.PHP_EOL;
            $xmlContent .= '        <userid>'.$entry['userid'].'</userid>'.PHP_EOL;
            $xmlContent .= '        <concept>'.htmlspecialchars($entry['concept']).'</concept>'.PHP_EOL;
            $xmlContent .= '        <definition><![CDATA['.$entry['definition'].']]></definition>'.PHP_EOL;
            $xmlContent .= '        <definitionformat>1</definitionformat>'.PHP_EOL;
            $xmlContent .= '        <definitiontrust>0</definitiontrust>'.PHP_EOL;
            $xmlContent .= '        <attachment></attachment>'.PHP_EOL;
            $xmlContent .= '        <timecreated>'.$entry['timecreated'].'</timecreated>'.PHP_EOL;
            $xmlContent .= '        <timemodified>'.$entry['timemodified'].'</timemodified>'.PHP_EOL;
            $xmlContent .= '        <teacherentry>1</teacherentry>'.PHP_EOL;
            $xmlContent .= '        <sourceglossaryid>0</sourceglossaryid>'.PHP_EOL;
            $xmlContent .= '        <usedynalink>0</usedynalink>'.PHP_EOL;
            $xmlContent .= '        <casesensitive>0</casesensitive>'.PHP_EOL;
            $xmlContent .= '        <fullmatch>0</fullmatch>'.PHP_EOL;
            $xmlContent .= '        <approved>1</approved>'.PHP_EOL;
            $xmlContent .= '        <ratings>'.PHP_EOL;
            $xmlContent .= '        </ratings>'.PHP_EOL;
            $xmlContent .= '      </entry>'.PHP_EOL;
        }
        $xmlContent .= '    </entries>'.PHP_EOL;
        $xmlContent .= '    <entriestags></entriestags>'.PHP_EOL;
        $xmlContent .= '    <categories></categories>'.PHP_EOL;
        $xmlContent .= '  </glossary>'.PHP_EOL;
        $xmlContent .= '</activity>';

        $this->createXmlFile('glossary', $xmlContent, $glossaryDir);
    }
}
