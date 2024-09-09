<?php

/* For licensing terms, see /license.txt */

namespace moodleexport;

use Exception;

/**
 * Class SectionExport.
 * Handles the export of course sections and their activities.
 *
 * @package moodleexport
 */
class SectionExport
{
    private $course;

    /**
     * Constructor to initialize the course object.
     *
     * @param object $course The course object to be exported.
     */
    public function __construct($course)
    {
        $this->course = $course;
    }

    /**
     * Export a section and its activities to the specified directory.
     */
    public function exportSection(int $sectionId, string $exportDir): void
    {
        $sectionDir = $exportDir . "/sections/section_{$sectionId}";

        if (!is_dir($sectionDir)) {
            mkdir($sectionDir, api_get_permissions_for_new_directories(), true);
        }

        if ($sectionId > 0) {
            $learnpath = $this->getLearnpathById($sectionId);
            if ($learnpath === null) {
                throw new Exception("Learnpath with ID $sectionId not found.");
            }
            $sectionData = $this->getSectionData($learnpath);
        } else {
            $sectionData = [
                'id' => 0,
                'number' => 0,
                'name' => get_lang('General'),
                'summary' => get_lang('GeneralResourcesCourse'),
                'sequence' => 0,
                'visible' => 1,
                'timemodified' => time(),
                'activities' => $this->getActivitiesForGeneral(),
            ];
        }

        $this->createSectionXml($sectionData, $sectionDir);
        $this->createInforefXml($sectionData, $sectionDir);
        $this->exportActivities($sectionData['activities'], $exportDir, $sectionId);
    }

    /**
     * Export the activities of a section.
     */
    private function exportActivities(array $activities, string $exportDir, int $sectionId): void
    {
        foreach ($activities as $activity) {
            switch ($activity['modulename']) {
                case 'quiz':
                    $quizExport = new QuizExport($this->course);
                    $quizExport->export($activity['id'], $exportDir, $activity['moduleid'], $sectionId);
                    break;
                case 'page':
                    $pageExport = new PageExport($this->course);
                    $pageExport->export($activity['id'], $exportDir, $activity['moduleid'], $sectionId);
                    break;
                case 'resource':
                    $resourceExport = new ResourceExport($this->course);
                    $resourceExport->export($activity['id'], $exportDir, $activity['moduleid'], $sectionId);
                    break;
                case 'folder':
                    $folderExport = new FolderExport($this->course);
                    $folderExport->export($activity['id'], $exportDir, $activity['moduleid'], $sectionId);
                    break;
            }
        }
    }

    /**
     * Get all general items not linked to any lesson (learnpath).
     */
    public function getGeneralItems(): array
    {
        $generalItems = [];

        if (!empty($this->course->resources[RESOURCE_DOCUMENT])) {
            foreach ($this->course->resources[RESOURCE_DOCUMENT] as $document) {
                if (!$this->isItemInLearnpath($document, RESOURCE_DOCUMENT)) {
                    $generalItems[] = [
                        'id' => $document->source_id,
                        'item_type' => 'document',
                        'path' => $document->source_id,
                        'title' => $document->title,
                    ];
                }
            }
        }

        if (!empty($this->course->resources[RESOURCE_QUIZ])) {
            foreach ($this->course->resources[RESOURCE_QUIZ] as $id => $quiz) {
                if (!$this->isItemInLearnpath($quiz, RESOURCE_QUIZ)) {
                    $generalItems[] = [
                        'id' => $quiz->source_id,
                        'item_type' => 'quiz',
                        'path' => $id,
                        'title' => $quiz->title,
                    ];
                }
            }
        }

        return $generalItems;
    }

    /**
     * Get the activities for the general section.
     */
    public function getActivitiesForGeneral(): array
    {
        $generalLearnpath = (object) [
            'items' => $this->getGeneralItems(),
            'source_id' => 0
        ];

        return $this->getActivitiesForSection($generalLearnpath, true);
    }

    /**
     * Check if an item is associated with any learnpath.
     */
    private function isItemInLearnpath(object $item, string $type): bool
    {
        if (!empty($this->course->resources[RESOURCE_LEARNPATH])) {
            foreach ($this->course->resources[RESOURCE_LEARNPATH] as $learnpath) {
                if (!empty($learnpath->items)) {
                    foreach ($learnpath->items as $learnpathItem) {
                        if ($learnpathItem['item_type'] === $type && $learnpathItem['path'] == $item->source_id) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get the learnpath object by its ID.
     */
    public function getLearnpathById(int $sectionId): ?object
    {
        foreach ($this->course->resources[RESOURCE_LEARNPATH] as $learnpath) {
            if ($learnpath->source_id == $sectionId) {
                return $learnpath;
            }
        }

        return null;
    }

    /**
     * Get section data for a learnpath.
     */
    public function getSectionData(object $learnpath): array
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
     * Get the activities for a specific section.
     */
    public function getActivitiesForSection(object $learnpath, bool $isGeneral = false): array
    {
        $activities = [];
        $sectionId = $isGeneral ? 0 : $learnpath->source_id;

        foreach ($learnpath->items as $item) {
            switch ($item['item_type']) {
                case 'quiz':
                    $quizId = (int) $item['path'];
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

                case 'document':
                    $documentId = (int) $item['path'];
                    $document = \DocumentManager::get_document_data_by_id($documentId, $this->course->code);

                    // Handle HTML files (pages)
                    if ('html' === pathinfo($document['path'], PATHINFO_EXTENSION)) {
                        $pageId = $item['path'];
                        $pageExport = new PageExport($this->course);
                        $pageData = $pageExport->getPageData($pageId, $sectionId);

                        $activities[] = [
                            'id' => $pageData['id'],
                            'moduleid' => $pageData['moduleid'],
                            'type' => 'page',
                            'modulename' => 'page',
                            'name' => $pageData['name'],
                        ];
                    }
                    // Handle file-type documents (resources)
                    elseif ('file' === $document['filetype']) {
                        $resourceId = $item['path'];
                        $resourceExport = new ResourceExport($this->course);
                        $resourceData = $resourceExport->getResourceData($resourceId, $sectionId);

                        $activities[] = [
                            'id' => $resourceData['id'],
                            'moduleid' => $resourceData['moduleid'],
                            'type' => 'resource',
                            'modulename' => 'resource',
                            'name' => $resourceData['name'],
                        ];
                    }
                    // Handle folder-type documents
                    elseif ('folder' === $document['filetype']) {
                        $folderId = $item['path'];
                        $folderExport = new FolderExport($this->course);
                        $folderData = $folderExport->getFolderData($folderId, $sectionId);

                        $activities[] = [
                            'id' => $folderData['id'],
                            'moduleid' => $folderData['moduleid'],
                            'type' => 'folder',
                            'modulename' => 'folder',
                            'name' => $folderData['name'],
                        ];
                    }
                    break;

                default:
                    break;
            }
        }

        return $activities;
    }

    /**
     * Create the section.xml file.
     */
    private function createSectionXml(array $sectionData, string $destinationDir): void
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
     */
    private function createInforefXml(array $sectionData, string $destinationDir): void
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
