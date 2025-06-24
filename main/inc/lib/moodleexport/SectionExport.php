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
        $sectionDir = $exportDir."/sections/section_{$sectionId}";

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
     * Get all general items not linked to any lesson (learnpath).
     */
    public function getGeneralItems(): array
    {
        $generalItems = [];

        // List of resource types and their corresponding ID keys
        $resourceTypes = [
            RESOURCE_DOCUMENT => 'source_id',
            RESOURCE_QUIZ => 'source_id',
            RESOURCE_GLOSSARY => 'glossary_id',
            RESOURCE_LINK => 'source_id',
            RESOURCE_WORK => 'source_id',
            RESOURCE_FORUM => 'source_id',
            RESOURCE_SURVEY => 'source_id',
            RESOURCE_TOOL_INTRO => 'source_id',
        ];

        foreach ($resourceTypes as $resourceType => $idKey) {
            if (!empty($this->course->resources[$resourceType])) {
                foreach ($this->course->resources[$resourceType] as $id => $resource) {
                    if (!$this->isItemInLearnpath($resource, $resourceType)) {
                        $title = $resourceType === RESOURCE_WORK
                            ? ($resource->params['title'] ?? '')
                            : ($resource->title ?? $resource->name);
                        $generalItems[] = [
                            'id' => $resource->$idKey,
                            'item_type' => $resourceType,
                            'path' => $id,
                            'title' => $title,
                        ];
                    }
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
            'source_id' => 0,
        ];

        $activities = $this->getActivitiesForSection($generalLearnpath, true);

        if (!in_array('folder', array_column($activities, 'modulename'))) {
            $activities[] = [
                'id' => 0,
                'moduleid' => 0,
                'modulename' => 'folder',
                'name' => 'Documents',
                'sectionid' => 0,
            ];
        }

        return $activities;
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
            'activities' => $this->getActivitiesForSection($learnpath),
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
            $this->addActivityToList($item, $sectionId, $activities);
        }

        return $activities;
    }

    /**
     * Export the activities of a section.
     */
    private function exportActivities(array $activities, string $exportDir, int $sectionId): void
    {
        $exportClasses = [
            'quiz' => QuizExport::class,
            'glossary' => GlossaryExport::class,
            'url' => UrlExport::class,
            'assign' => AssignExport::class,
            'forum' => ForumExport::class,
            'page' => PageExport::class,
            'resource' => ResourceExport::class,
            'folder' => FolderExport::class,
            'feedback' => FeedbackExport::class,
        ];

        foreach ($activities as $activity) {
            $moduleName = $activity['modulename'];
            if (isset($exportClasses[$moduleName])) {
                $exportClass = new $exportClasses[$moduleName]($this->course);
                $exportClass->export($activity['id'], $exportDir, $activity['moduleid'], $sectionId);
            } else {
                throw new \Exception("Export for module '$moduleName' is not supported.");
            }
        }
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
     * Add an activity to the activities list.
     */
    private function addActivityToList(array $item, int $sectionId, array &$activities): void
    {
        static $documentsFolderAdded = false;
        if (!$documentsFolderAdded && $sectionId === 0) {
            $activities[] = [
                'id' => 0,
                'moduleid' => 0,
                'type' => 'folder',
                'modulename' => 'folder',
                'name' => 'Documents',
            ];
            $documentsFolderAdded = true;
        }

        $activityData = null;
        $activityClassMap = [
            'quiz' => QuizExport::class,
            'glossary' => GlossaryExport::class,
            'url' => UrlExport::class,
            'assign' => AssignExport::class,
            'forum' => ForumExport::class,
            'page' => PageExport::class,
            'resource' => ResourceExport::class,
            'feedback' => FeedbackExport::class,
        ];

        if ($item['id'] == 'course_homepage') {
            $item['item_type'] = 'page';
            $item['path'] = 0;
        }

        $itemType = $item['item_type'] === 'link' ? 'url' :
            ($item['item_type'] === 'work' || $item['item_type'] === 'student_publication' ? 'assign' :
                ($item['item_type'] === 'survey' ? 'feedback' : $item['item_type']));

        switch ($itemType) {
            case 'quiz':
            case 'glossary':
            case 'assign':
            case 'url':
            case 'forum':
            case 'feedback':
            case 'page':
                $activityId = $itemType === 'glossary' ? 1 : (int) $item['path'];
                $exportClass = $activityClassMap[$itemType];
                $exportInstance = new $exportClass($this->course);
                $activityData = $exportInstance->getData($activityId, $sectionId);
                break;

            case 'document':
                $documentId = (int) $item['path'];
                $document = \DocumentManager::get_document_data_by_id($documentId, $this->course->code);

                if ($document) {
                    $isRoot = substr_count($document['path'], '/') === 1;
                    $documentType = $this->getDocumentType($document['filetype'], $document['path']);
                    if ($documentType === 'page' && $isRoot) {
                        $activityClass = $activityClassMap['page'];
                        $exportInstance = new $activityClass($this->course);
                        $activityData = $exportInstance->getData($item['path'], $sectionId);
                    }
                    elseif ($sectionId > 0 && $documentType && isset($activityClassMap[$documentType])) {
                        $activityClass = $activityClassMap[$documentType];
                        $exportInstance = new $activityClass($this->course);
                        $activityData = $exportInstance->getData($item['path'], $sectionId);
                    }
                }
                break;
        }

        // Add the activity to the list if the data exists
        if ($activityData) {
            $activities[] = [
                'id' => $activityData['id'],
                'moduleid' => $activityData['moduleid'],
                'type' => $item['item_type'],
                'modulename' => $activityData['modulename'],
                'name' => $activityData['name'],
            ];
        }
    }

    /**
     * Determine the document type based on filetype and path.
     */
    private function getDocumentType(string $filetype, string $path): ?string
    {
        if ('html' === pathinfo($path, PATHINFO_EXTENSION)) {
            return 'page';
        } elseif ('file' === $filetype) {
            return 'resource';
        } /*elseif ('folder' === $filetype) {
            return 'folder';
        }*/

        return null;
    }

    /**
     * Create the section.xml file.
     */
    private function createSectionXml(array $sectionData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<section id="'.$sectionData['id'].'">'.PHP_EOL;
        $xmlContent .= '  <number>'.$sectionData['number'].'</number>'.PHP_EOL;
        $xmlContent .= '  <name>'.htmlspecialchars($sectionData['name']).'</name>'.PHP_EOL;
        $xmlContent .= '  <summary>'.htmlspecialchars($sectionData['summary']).'</summary>'.PHP_EOL;
        $xmlContent .= '  <summaryformat>1</summaryformat>'.PHP_EOL;
        $xmlContent .= '  <sequence>'.implode(',', array_column($sectionData['activities'], 'moduleid')).'</sequence>'.PHP_EOL;
        $xmlContent .= '  <visible>'.$sectionData['visible'].'</visible>'.PHP_EOL;
        $xmlContent .= '  <timemodified>'.$sectionData['timemodified'].'</timemodified>'.PHP_EOL;
        $xmlContent .= '</section>'.PHP_EOL;

        $xmlFile = $destinationDir.'/section.xml';
        file_put_contents($xmlFile, $xmlContent);
    }

    /**
     * Create the inforef.xml file for the section.
     */
    private function createInforefXml(array $sectionData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<inforef>'.PHP_EOL;

        foreach ($sectionData['activities'] as $activity) {
            $xmlContent .= '  <activity id="'.$activity['id'].'">'.htmlspecialchars($activity['name']).'</activity>'.PHP_EOL;
        }

        $xmlContent .= '</inforef>'.PHP_EOL;

        $xmlFile = $destinationDir.'/inforef.xml';
        file_put_contents($xmlFile, $xmlContent);
    }
}
