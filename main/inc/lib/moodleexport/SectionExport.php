<?php

/* For licensing terms, see /license.txt */

namespace moodleexport;

use Exception;

/**
 * Class SectionExport.
 *
 * @package moodleexport
 */
class SectionExport
{
    private $course;

    public function __construct($course)
    {
        $this->course = $course;
    }

    /**
     * Export a section and its activities to the appropriate directory.
     *
     * @param int $sectionId The ID of the section.
     * @param string $exportDir The main export directory (where `sections/` will be created).
     */
    public function exportSection($sectionId, $exportDir)
    {
        $sectionDir = $exportDir . "/sections/section_{$sectionId}";

        if (!is_dir($sectionDir)) {
            mkdir($sectionDir, api_get_permissions_for_new_directories(), true);
        }

        $learnpath = $this->getLearnpathById($sectionId);

        if ($learnpath === null) {
            throw new Exception("Learnpath with ID $sectionId not found.");
        }

        $sectionData = $this->getSectionData($learnpath);

        $this->createSectionXml($sectionData, $sectionDir);
        $this->createInforefXml($sectionData, $sectionDir);

        foreach ($sectionData['activities'] as $activity) {
            if ($activity['modulename'] === 'quiz') {
                $quizExport = new QuizExport($this->course);
                $quizExport->exportQuiz($activity['id'], $exportDir, $activity['moduleid'], $sectionId);
            }
            // Add more types of activities here if needed
        }
    }

    /**
     * Get the learnpath object by its ID.
     *
     * @param int $sectionId The ID of the section (learnpath).
     * @return object|null The learnpath object or null if not found.
     */
    public function getLearnpathById($sectionId)
    {
        foreach ($this->course->resources[RESOURCE_LEARNPATH] as $learnpath) {
            if ($learnpath->source_id == $sectionId) {
                return $learnpath;
            }
        }
        return null;
    }

    /**
     * Get the section data (hardcoded for now).
     *
     * @param int $sectionId The ID of the section.
     *
     * @return array Section data.
     */
    public function getSectionData($learnpath)
    {
        return [
            'id' => $learnpath->source_id,
            'number' => $learnpath->display_order,
            'name' => $learnpath->name,
            'summary' => $learnpath->description,
            'sequence' => $learnpath->source_id,
            'visible' => $learnpath->visibility,
            'timemodified' => strtotime($learnpath->modified_on),
            'activities' => $this->getActivitiesForSection($learnpath)
        ];
    }

    /**
     * Get the activities for the section (this simulates retrieving activities).
     *
     * @param int $sectionId The ID of the section.
     *
     * @return array List of activities.
     */
    private function getActivitiesForSection($learnpath)
    {
        $activities = [];
        foreach ($learnpath->items as $item) {
            switch ($item['item_type']) {
                case 'quiz':
                    $quizId = $item['path'];
                    $sectionId = $learnpath->source_id;
                    $quizExport = new QuizExport($this->course);
                    $quizData = $quizExport->getQuizData($quizId, $sectionId);

                    $activities[] = [
                        'id' => $quizData['id'],
                        'moduleid' => $quizData['moduleid'],
                        'type' => 'quiz',
                        'modulename' => $quizData['modulename'],
                        'name' => $quizData['name'],
                    ];
                    break;

                // Add more cases here for other types of activities if needed

                default:
                    // Handle other types of activities if needed
                    break;
            }
        }
        return $activities;
    }

    /**
     * Create the section.xml file.
     *
     * @param array $sectionData Section data.
     * @param string $destinationDir Directory where the XML will be saved.
     */
    private function createSectionXml($sectionData, $destinationDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<section id="' . $sectionData['id'] . '">' . PHP_EOL;
        $xmlContent .= '  <number>' . $sectionData['number'] . '</number>' . PHP_EOL;
        $xmlContent .= '  <name>' . htmlspecialchars($sectionData['name']) . '</name>' . PHP_EOL;
        $xmlContent .= '  <summary>' . htmlspecialchars($sectionData['summary']) . '</summary>' . PHP_EOL;
        $xmlContent .= '  <summaryformat>1</summaryformat>' . PHP_EOL;
        $xmlContent .= '  <sequence>' . implode(',', array_column($sectionData['activities'], 'moduleid')) . '</sequence>' . PHP_EOL;
        $xmlContent .= '  <visible>' . $sectionData['visible'] . '</visible>' . PHP_EOL;
        $xmlContent .= '  <timemodified>' . $sectionData['timemodified'] . '</timemodified>' . PHP_EOL;
        $xmlContent .= '</section>' . PHP_EOL;

        $xmlFile = $destinationDir . '/section.xml';
        file_put_contents($xmlFile, $xmlContent);
    }

    /**
     * Create the inforef.xml file for the section.
     *
     * @param array $sectionData Section data.
     * @param string $destinationDir Directory where the XML will be saved.
     */
    private function createInforefXml($sectionData, $destinationDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<inforef>' . PHP_EOL;
        foreach ($sectionData['activities'] as $activity) {
            $xmlContent .= '  <activity id="' . $activity['id'] . '">' . htmlspecialchars($activity['name']) . '</activity>' . PHP_EOL;
        }
        $xmlContent .= '</inforef>' . PHP_EOL;

        $xmlFile = $destinationDir . '/inforef.xml';
        file_put_contents($xmlFile, $xmlContent);
    }
}
