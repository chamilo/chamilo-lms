<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\AssignExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\FeedbackExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\FolderExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\ForumExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\GlossaryExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\PageExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\QuizExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\ResourceExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\UrlExport;
use DocumentManager;
use Exception;

use const PATHINFO_EXTENSION;
use const PHP_EOL;

/**
 * Handles the export of course sections and their activities.
 */
class SectionExport
{
    /**
     * @var object
     */
    private $course;

    /**
     * @param object $course the course object to be exported
     */
    public function __construct(object $course)
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
            if (null === $learnpath) {
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

        $this->exportActivities($sectionData['activities'], $exportDir, (int) $sectionData['id']);
        $this->createSectionXml($sectionData, $sectionDir);
        $this->createInforefXml($sectionData, $sectionDir);
    }

    /**
     * Get all general items not linked to any learnpath.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getGeneralItems(): array
    {
        $generalItems = [];

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
                    if (!$this->isItemInLearnpath($resource, (string) $resourceType)) {
                        $title = RESOURCE_WORK === $resourceType
                            ? ($resource->params['title'] ?? '')
                            : ($resource->title ?? $resource->name);
                        $generalItems[] = [
                            'id' => $resource->{$idKey},
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
     *
     * @return array<int,array<string,mixed>>
     */
    public function getActivitiesForGeneral(): array
    {
        $generalLearnpath = (object) [
            'items' => $this->getGeneralItems(),
            'source_id' => 0,
        ];

        $activities = $this->getActivitiesForSection($generalLearnpath, true);

        if (!\in_array('folder', array_column($activities, 'modulename'), true)) {
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
        foreach (($this->course->resources[RESOURCE_LEARNPATH] ?? []) as $learnpath) {
            if (($learnpath->source_id ?? null) == $sectionId) {
                return $learnpath;
            }
        }

        return null;
    }

    /**
     * Get section data for a learnpath.
     *
     * @return array<string,mixed>
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
     *
     * @return array<int,array<string,mixed>>
     */
    public function getActivitiesForSection(object $learnpath, bool $isGeneral = false): array
    {
        $activities = [];
        $sectionId = $isGeneral ? 0 : (int) $learnpath->source_id;

        foreach ($learnpath->items as $item) {
            $this->addActivityToList($item, $sectionId, $activities);
        }

        return $activities;
    }

    /**
     * Export the activities of a section.
     *
     * @param array<int,array<string,mixed>> $activities
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
                $exportClass = $exportClasses[$moduleName];
                $exporter = new $exportClass($this->course);
                $exporter->export((int) $activity['id'], $exportDir, (int) $activity['moduleid'], $sectionId);
            } else {
                throw new Exception("Export for module '$moduleName' is not supported.");
            }
        }
    }

    /**
     * Check if an item is associated with any learnpath.
     */
    private function isItemInLearnpath(object $item, string $type): bool
    {
        foreach (($this->course->resources[RESOURCE_LEARNPATH] ?? []) as $learnpath) {
            foreach (($learnpath->items ?? []) as $learnpathItem) {
                $lpType = ($learnpathItem['item_type'] ?? '') === 'student_publication' ? 'work' : ($learnpathItem['item_type'] ?? '');
                if ($lpType === $type && (string) ($learnpathItem['path'] ?? '') === (string) ($item->source_id ?? '')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Add an activity to the activities list.
     *
     * @param array<string,mixed>            $item
     * @param array<int,array<string,mixed>> $activities (by ref)
     */
    private function addActivityToList(array $item, int $sectionId, array &$activities): void
    {
        static $documentsFolderAdded = false;
        if (!$documentsFolderAdded && 0 === $sectionId) {
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

        if ('course_homepage' == $item['id']) {
            $item['item_type'] = 'page';
            $item['path'] = 0;
        }

        $itemType = 'link' === $item['item_type'] ? 'url'
            : (('work' === $item['item_type'] || 'student_publication' === $item['item_type']) ? 'assign'
                : ('survey' === $item['item_type'] ? 'feedback' : $item['item_type']));

        switch ($itemType) {
            case 'quiz':
            case 'glossary':
            case 'assign':
            case 'url':
            case 'forum':
            case 'feedback':
            case 'page':
                $activityId = 'glossary' === $itemType ? 1 : (int) $item['path'];
                $exportClass = $activityClassMap[$itemType];
                $exportInstance = new $exportClass($this->course);
                $activityData = $exportInstance->getData($activityId, $sectionId);

                break;

            case 'document':
                $documentId = (int) $item['path'];
                $document = DocumentManager::get_document_data_by_id($documentId, $this->course->code);

                if ($document) {
                    $isRoot = 1 === substr_count($document['path'], '/');
                    $documentType = $this->getDocumentType($document['filetype'], $document['path']);
                    if ('page' === $documentType && $isRoot) {
                        $exportInstance = new PageExport($this->course);
                        $activityData = $exportInstance->getData((int) $item['path'], $sectionId);
                    } elseif ($sectionId > 0 && $documentType && isset($activityClassMap[$documentType])) {
                        $exportClass = $activityClassMap[$documentType];
                        $exportInstance = new $exportClass($this->course);
                        $activityData = $exportInstance->getData((int) $item['path'], $sectionId);
                    }
                }

                break;
        }

        if ($activityData) {
            $activities[] = [
                'id' => (int) $activityData['id'],
                'moduleid' => (int) $activityData['moduleid'],
                'type' => (string) $item['item_type'],
                'modulename' => (string) $activityData['modulename'],
                'name' => (string) $activityData['name'],
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
        }
        if ('file' === $filetype) {
            return 'resource';
        }

        // if ('folder' === $filetype) return 'folder';
        return null;
    }

    /**
     * Create the section.xml file.
     *
     * @param array<string,mixed> $sectionData
     */
    private function createSectionXml(array $sectionData, string $destinationDir): void
    {
        $seen = [];
        $cmIds = [];
        foreach ($sectionData['activities'] as $a) {
            $name = (string) ($a['modulename'] ?? '');
            $mid = isset($a['moduleid']) ? (int) $a['moduleid'] : null;
            if ('' === $name || null === $mid || $mid < 0) {
                continue;
            }

            $key = $name.':'.$mid;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $cmIds[] = (string) $mid;
        }

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<section id="'.$sectionData['id'].'">'.PHP_EOL;
        $xmlContent .= ' <number>'.$sectionData['number'].'</number>'.PHP_EOL;
        $xmlContent .= ' <name>'.htmlspecialchars((string) $sectionData['name']).'</name>'.PHP_EOL;
        $xmlContent .= ' <summary>'.htmlspecialchars((string) $sectionData['summary']).'</summary>'.PHP_EOL;
        $xmlContent .= ' <summaryformat>1</summaryformat>'.PHP_EOL;
        $xmlContent .= ' <sequence>'.implode(',', $cmIds).'</sequence>'.PHP_EOL;
        $xmlContent .= ' <visible>'.$sectionData['visible'].'</visible>'.PHP_EOL;
        $xmlContent .= ' <timemodified>'.$sectionData['timemodified'].'</timemodified>'.PHP_EOL;
        $xmlContent .= '</section>'.PHP_EOL;

        file_put_contents($destinationDir.'/section.xml', $xmlContent);
    }

    /**
     * Create the inforef.xml file for the section.
     *
     * @param array<string,mixed> $sectionData
     */
    private function createInforefXml(array $sectionData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<inforef>'.PHP_EOL;

        foreach ($sectionData['activities'] as $activity) {
            $xmlContent .= '  <activity id="'.(int) $activity['id'].'">'
                .htmlspecialchars((string) $activity['name']).'</activity>'.PHP_EOL;
        }

        $xmlContent .= '</inforef>'.PHP_EOL;

        file_put_contents($destinationDir.'/inforef.xml', $xmlContent);
    }
}
