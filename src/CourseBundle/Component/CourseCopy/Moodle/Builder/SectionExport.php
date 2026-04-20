<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\ActivityExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\AssignExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\FeedbackExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\FolderExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\ForumExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\GlossaryExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\LabelExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\PageExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\QuizExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\ResourceExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\UrlExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\WikiExport;
use DocumentManager;
use Exception;

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
     * @var array<int,array<int,array<string,mixed>>>
     */
    private array $activitiesBySection = [];

    /**
     * @param object $course the course object to be exported
     * @param array<int,array<int,array<string,mixed>>> $activitiesBySection
     */
    public function __construct(object $course, array $activitiesBySection = [])
    {
        $this->course = $course;
        $this->activitiesBySection = $activitiesBySection;
    }

    /**
     * Export a section and its activities to the specified directory.
     */
    public function exportSection(int $sectionId, string $exportDir): void
    {
        $sectionDir = $exportDir.'/sections/section_'.$sectionId;

        if (!is_dir($sectionDir)) {
            mkdir($sectionDir, api_get_permissions_for_new_directories(), true);
        }

        if ($sectionId > 0) {
            $learnpath = $this->getLearnpathById($sectionId);
            if (null === $learnpath) {
                throw new Exception('Learnpath with ID '.$sectionId.' not found.');
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
     * Get all general items not linked to any lesson.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getGeneralItems(): array
    {
        $generalItems = [];

        $resourceTypes = [
            \defined('RESOURCE_DOCUMENT') ? RESOURCE_DOCUMENT : 'document' => 'source_id',
            \defined('RESOURCE_QUIZ') ? RESOURCE_QUIZ : 'quiz' => 'source_id',
            \defined('RESOURCE_GLOSSARY') ? RESOURCE_GLOSSARY : 'glossary' => 'glossary_id',
            \defined('RESOURCE_LINK') ? RESOURCE_LINK : 'link' => 'source_id',
            \defined('RESOURCE_WORK') ? RESOURCE_WORK : 'work' => 'source_id',
            \defined('RESOURCE_FORUM') ? RESOURCE_FORUM : 'forum' => 'source_id',
            \defined('RESOURCE_SURVEY') ? RESOURCE_SURVEY : 'survey' => 'source_id',
            \defined('RESOURCE_TOOL_INTRO') ? RESOURCE_TOOL_INTRO : 'tool_intro' => 'source_id',
            \defined('RESOURCE_COURSEDESCRIPTION') ? RESOURCE_COURSEDESCRIPTION : 'course_description' => 'source_id',
            \defined('RESOURCE_WIKI') ? RESOURCE_WIKI : 'wiki' => 'source_id',
        ];

        foreach ($resourceTypes as $resourceType => $idKey) {
            $bucket = $this->course->resources[$resourceType] ?? null;
            if (!is_array($bucket) || empty($bucket)) {
                continue;
            }

            foreach ($bucket as $id => $resource) {
                if (!is_object($resource)) {
                    continue;
                }

                if ($this->isItemInLearnpath($resource, (string) $resourceType)) {
                    continue;
                }

                $title = '';
                if ((string) $resourceType === (\defined('RESOURCE_WORK') ? RESOURCE_WORK : 'work')) {
                    $title = (string) ($resource->params['title'] ?? '');
                } else {
                    $title = (string) ($resource->title ?? $resource->name ?? '');
                }

                $generalItems[] = [
                    'id' => $resource->$idKey ?? $id,
                    'item_type' => (string) $resourceType,
                    'path' => $id,
                    'title' => $title,
                ];
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
        if (isset($this->activitiesBySection[0]) && is_array($this->activitiesBySection[0])) {
            return $this->activitiesBySection[0];
        }

        $generalLearnpath = (object) [
            'items' => $this->getGeneralItems(),
            'source_id' => 0,
        ];

        $activities = $this->getActivitiesForSection($generalLearnpath, true);

        $hasFolder = false;
        foreach ($activities as $activity) {
            if (($activity['modulename'] ?? '') === 'folder') {
                $hasFolder = true;
                break;
            }
        }

        if (!$hasFolder) {
            $activities[] = [
                'id' => ActivityExport::DOCS_MODULE_ID,
                'moduleid' => ActivityExport::DOCS_MODULE_ID,
                'modulename' => 'folder',
                'name' => 'Documents',
                'title' => 'Documents',
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
        $learnpaths =
            $this->course->resources[\defined('RESOURCE_LEARNPATH') ? RESOURCE_LEARNPATH : 'learnpath']
            ?? $this->course->resources['learnpath']
            ?? [];

        if (!is_array($learnpaths)) {
            return null;
        }

        foreach ($learnpaths as $learnpath) {
            $lp = (\is_object($learnpath) && isset($learnpath->obj) && \is_object($learnpath->obj))
                ? $learnpath->obj
                : $learnpath;

            if (!is_object($lp)) {
                continue;
            }

            if ((int) ($lp->source_id ?? $lp->id ?? 0) === $sectionId) {
                return $lp;
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
        $sectionId = (int) ($learnpath->source_id ?? $learnpath->id ?? 0);

        $sectionName = trim((string) ($learnpath->name ?? $learnpath->title ?? ''));
        if ('' === $sectionName) {
            $sectionName = 'Section '.$sectionId;
        }

        return [
            'id' => $sectionId,
            'number' => $this->resolveSectionNumber($sectionId),
            'name' => $sectionName,
            'summary' => (string) ($learnpath->description ?? ''),
            'sequence' => $sectionId,
            'visible' => (int) ($learnpath->visibility ?? 1),
            'timemodified' => !empty($learnpath->modified_on) ? strtotime((string) $learnpath->modified_on) : time(),
            'activities' => $this->getActivitiesForSection($learnpath),
        ];
    }


    /**
     * Resolve a stable sequential section number for Moodle topics format.
     */
    private function resolveSectionNumber(int $sectionId): int
    {
        if ($sectionId <= 0) {
            return 0;
        }

        $learnpaths =
            $this->course->resources[\defined('RESOURCE_LEARNPATH') ? RESOURCE_LEARNPATH : 'learnpath']
            ?? $this->course->resources['learnpath']
            ?? [];

        if (!\is_array($learnpaths) || empty($learnpaths)) {
            return 1;
        }

        $ordered = [];
        foreach ($learnpaths as $learnpathWrap) {
            $learnpath = (\is_object($learnpathWrap) && isset($learnpathWrap->obj) && \is_object($learnpathWrap->obj))
                ? $learnpathWrap->obj
                : $learnpathWrap;

            if (!\is_object($learnpath) || (int) ($learnpath->lp_type ?? 0) !== 1) {
                continue;
            }

            $ordered[] = $learnpath;
        }

        usort(
            $ordered,
            static function (object $a, object $b): int {
                $oa = (int) ($a->display_order ?? 0);
                $ob = (int) ($b->display_order ?? 0);
                if ($oa !== $ob) {
                    if ($oa <= 0) {
                        return 1;
                    }
                    if ($ob <= 0) {
                        return -1;
                    }

                    return $oa <=> $ob;
                }

                return ((int) ($a->source_id ?? $a->id ?? 0)) <=> ((int) ($b->source_id ?? $b->id ?? 0));
            }
        );

        $position = 1;
        foreach ($ordered as $learnpath) {
            if ((int) ($learnpath->source_id ?? $learnpath->id ?? 0) === $sectionId) {
                return $position;
            }
            $position++;
        }

        return 1;
    }

    /**
     * Get the activities for a specific section.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getActivitiesForSection(object $learnpath, bool $isGeneral = false): array
    {
        $sectionId = $isGeneral ? 0 : (int) ($learnpath->source_id ?? $learnpath->id ?? 0);

        if (isset($this->activitiesBySection[$sectionId]) && is_array($this->activitiesBySection[$sectionId])) {
            return $this->activitiesBySection[$sectionId];
        }

        $activities = [];
        foreach ((array) ($learnpath->items ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

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
            'label' => LabelExport::class,
            'wiki' => WikiExport::class,
        ];

        foreach ($activities as $activity) {
            $moduleName = (string) ($activity['modulename'] ?? '');
            if (!isset($exportClasses[$moduleName])) {
                throw new Exception("Export for module '".$moduleName."' is not supported.");
            }

            $exportClass = $exportClasses[$moduleName];
            $exportInstance = new $exportClass($this->course);
            $exportInstance->export(
                (int) ($activity['id'] ?? 0),
                $exportDir,
                (int) ($activity['moduleid'] ?? 0),
                $sectionId
            );
        }
    }

    /**
     * Normalize resource / LP item types for reliable comparison.
     */
    private function normalizeItemTypeForLpComparison(string $type): string
    {
        switch ($type) {
            case 'student_publication':
            case 'work':
            case 'assign':
                return 'work';

            case 'link':
            case 'url':
                return 'link';

            case 'survey':
            case 'feedback':
                return 'survey';

            case 'page':
            case 'resource':
                return 'document';

            default:
                return $type;
        }
    }

    /**
     * Check if an item is associated with any learnpath.
     */
    private function isItemInLearnpath(object $item, string $type): bool
    {
        $learnpaths =
            $this->course->resources[\defined('RESOURCE_LEARNPATH') ? RESOURCE_LEARNPATH : 'learnpath']
            ?? $this->course->resources['learnpath']
            ?? [];

        if (!is_array($learnpaths) || empty($learnpaths)) {
            return false;
        }

        $normalizedType = $this->normalizeItemTypeForLpComparison($type);
        $itemSourceId = isset($item->source_id) ? (string) $item->source_id : '';

        foreach ($learnpaths as $learnpath) {
            $lp = (\is_object($learnpath) && isset($learnpath->obj) && \is_object($learnpath->obj))
                ? $learnpath->obj
                : $learnpath;

            if (!is_object($lp) || empty($lp->items)) {
                continue;
            }

            foreach ((array) $lp->items as $learnpathItem) {
                if (!is_array($learnpathItem)) {
                    continue;
                }

                $lpType = isset($learnpathItem['item_type'])
                    ? $this->normalizeItemTypeForLpComparison((string) $learnpathItem['item_type'])
                    : '';

                if ($lpType !== $normalizedType) {
                    continue;
                }

                $lpPath = isset($learnpathItem['path']) ? (string) $learnpathItem['path'] : '';

                if ('' !== $itemSourceId && $lpPath === $itemSourceId) {
                    return true;
                }

                if ('document' === $normalizedType && '' !== $itemSourceId && ctype_digit($itemSourceId)) {
                    $doc = DocumentManager::get_document_data_by_id((int) $itemSourceId, (string) ($this->course->code ?? ''));
                    if (!empty($doc['path'])) {
                        foreach ($this->buildDocumentLpCandidates((string) $doc['path']) as $candidate) {
                            if ($lpPath === $candidate) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Add an activity to the activities list.
     *
     * @param array<string,mixed> $item
     * @param array<int,array<string,mixed>> $activities
     */
    private function addActivityToList(array $item, int $sectionId, array &$activities): void
    {
        if (0 === $sectionId && !$this->hasFolderActivity($activities)) {
            $activities[] = [
                'id' => ActivityExport::DOCS_MODULE_ID,
                'moduleid' => ActivityExport::DOCS_MODULE_ID,
                'type' => 'folder',
                'modulename' => 'folder',
                'name' => 'Documents',
                'title' => 'Documents',
                'sectionid' => 0,
            ];
        }

        $activityData = null;

        $activityClassMap = [
            'quiz' => QuizExport::class,
            'glossary' => GlossaryExport::class,
            'assign' => AssignExport::class,
            'url' => UrlExport::class,
            'forum' => ForumExport::class,
            'page' => PageExport::class,
            'resource' => ResourceExport::class,
            'feedback' => FeedbackExport::class,
            'label' => LabelExport::class,
            'wiki' => WikiExport::class,
        ];

        if (($item['id'] ?? null) === 'course_homepage' || ($item['path'] ?? null) === 'course_homepage') {
            $item['item_type'] = 'page';
            $item['path'] = 0;
        }

        $itemType = (string) ($item['item_type'] ?? '');

        if ('link' === $itemType) {
            $itemType = 'url';
        } elseif ('work' === $itemType || 'student_publication' === $itemType) {
            $itemType = 'assign';
        } elseif ('survey' === $itemType) {
            $itemType = 'feedback';
        } elseif ('coursedescription' === $itemType || 'course_description' === $itemType) {
            $itemType = 'label';
        }

        switch ($itemType) {
            case 'quiz':
            case 'glossary':
            case 'assign':
            case 'url':
            case 'forum':
            case 'feedback':
            case 'page':
            case 'label':
            case 'wiki':
                $activityId = 'glossary' === $itemType ? 1 : (int) ($item['path'] ?? 0);
                if ('wiki' === $itemType && $activityId <= 0) {
                    $activityId = 48000003;
                }

                $exportClass = $activityClassMap[$itemType];
                $exportInstance = new $exportClass($this->course);
                $activityData = $exportInstance->getData($activityId, $sectionId);
                break;

            case 'document':
                $documentId = (int) ($item['path'] ?? 0);
                $document = DocumentManager::get_document_data_by_id($documentId, (string) ($this->course->code ?? ''));

                if (!empty($document)) {
                    $documentType = $this->getDocumentType((string) ($document['filetype'] ?? ''), (string) ($document['path'] ?? ''));

                    if ($documentType && isset($activityClassMap[$documentType])) {
                        $activityClass = $activityClassMap[$documentType];
                        $exportInstance = new $activityClass($this->course);
                        $activityData = $exportInstance->getData($documentId, $sectionId);
                    }
                }
                break;
        }

        if (!empty($activityData) && $sectionId > 0) {
            $lpItemId = isset($item['id']) ? (int) $item['id'] : 0;
            $modName = (string) ($activityData['modulename'] ?? '');

            if ($lpItemId > 0 && !in_array($modName, ['folder', 'glossary'], true)) {
                $activityData['moduleid'] = 900000000 + $lpItemId;
            }
        }

        if ($activityData) {
            $activities[] = [
                'id' => (int) ($activityData['id'] ?? 0),
                'moduleid' => (int) ($activityData['moduleid'] ?? 0),
                'type' => (string) ($item['item_type'] ?? ''),
                'modulename' => (string) ($activityData['modulename'] ?? ''),
                'name' => (string) ($activityData['name'] ?? ''),
                'title' => (string) ($activityData['name'] ?? ''),
                'sectionid' => $sectionId,
            ];
        }
    }

    /**
     * Determine the document type based on filetype and path.
     */
    private function getDocumentType(string $filetype, string $path): ?string
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['html', 'htm'], true)) {
            return 'page';
        }

        if ('file' === $filetype) {
            return 'resource';
        }

        return null;
    }

    /**
     * Create the section.xml file.
     *
     * @param array<string,mixed> $sectionData
     */
    private function createSectionXml(array $sectionData, string $destinationDir): void
    {
        $sequence = array_map(
            static fn (array $activity): string => (string) ((int) ($activity['moduleid'] ?? 0)),
            (array) ($sectionData['activities'] ?? [])
        );

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<section id="'.(int) $sectionData['id'].'">'.PHP_EOL;
        $xmlContent .= '  <number>'.(int) $sectionData['number'].'</number>'.PHP_EOL;
        $xmlContent .= '  <name>'.htmlspecialchars((string) $sectionData['name']).'</name>'.PHP_EOL;
        $xmlContent .= '  <summary>'.htmlspecialchars((string) $sectionData['summary']).'</summary>'.PHP_EOL;
        $xmlContent .= '  <summaryformat>1</summaryformat>'.PHP_EOL;
        $xmlContent .= '  <sequence>'.implode(',', $sequence).'</sequence>'.PHP_EOL;
        $xmlContent .= '  <visible>'.(int) $sectionData['visible'].'</visible>'.PHP_EOL;
        $xmlContent .= '  <timemodified>'.(int) $sectionData['timemodified'].'</timemodified>'.PHP_EOL;
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

        foreach ((array) ($sectionData['activities'] ?? []) as $activity) {
            $refId = isset($activity['moduleid']) ? (int) $activity['moduleid'] : (int) ($activity['id'] ?? 0);
            $xmlContent .= '  <activity id="'.$refId.'">'.htmlspecialchars((string) ($activity['name'] ?? '')).'</activity>'.PHP_EOL;
        }

        $xmlContent .= '</inforef>'.PHP_EOL;

        file_put_contents($destinationDir.'/inforef.xml', $xmlContent);
    }

    /**
     * Build normalized document path candidates for LP comparisons.
     *
     * @return array<int,string>
     */
    private function buildDocumentLpCandidates(string $documentPath): array
    {
        $normalized = ltrim(str_replace('\\', '/', $documentPath), '/');
        $normalized = (string) preg_replace('#^document/#', '', $normalized);

        return array_values(array_unique([
            $normalized,
            '/'.$normalized,
            'document/'.$normalized,
            '/document/'.$normalized,
        ]));
    }

    /**
     * Returns true when the section activity list already contains the virtual folder.
     *
     * @param array<int,array<string,mixed>> $activities
     */
    private function hasFolderActivity(array $activities): bool
    {
        foreach ($activities as $activity) {
            if (($activity['modulename'] ?? '') === 'folder') {
                return true;
            }
        }

        return false;
    }
}
