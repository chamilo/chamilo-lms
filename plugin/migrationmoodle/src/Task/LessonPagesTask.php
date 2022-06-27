<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\LessonPagesLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LessonPageType;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseCodeLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseModuleLessonLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseSectionFromLessonLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLessonPageLookup;

/**
 * Class LessonPagesTask.
 *
 * Task to conver the Moodle lesson pages in items for Chamilo learning paths.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class LessonPagesTask extends BaseTask
{
    public const TYPE_END_BRANCH = 21;
    public const TYPE_CLUSTER = 30;
    public const TYPE_END_CLUSTER = 31;

    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedCoursesFilterExtractor::class,
            'query' => "WITH RECURSIVE lesson_pages_ordered (id, title, qtype, prev, next, lesson, display_order) AS
                (
                    SELECT id, title, qtype, prevpageid, nextpageid, lessonid, '01'
                    FROM mdl_lesson_pages
                    WHERE prevpageid = 0
                    UNION
                    SELECT lp.id, lp.title, lp.qtype, lp.prevpageid, lp.nextpageid, lp.lessonid, lpo.display_order + 1
                    FROM lesson_pages_ordered lpo
                    LEFT JOIN mdl_lesson_pages lp
                        ON (lpo.next = lp.id AND lpo.lesson = lp.lessonid)
                )
                SELECT
                    lpo.id,
                    lpo.title,
                    lpo.qtype,
                    lpo.prev,
                    lpo.lesson,
                    CAST(lpo.display_order AS SIGNED) lpo_display_order,
                    l.course
                FROM lesson_pages_ordered lpo
                INNER JOIN mdl_lesson l ON lpo.lesson = l.id
                WHERE lpo.qtype NOT IN (".self::TYPE_END_BRANCH.", ".self::TYPE_CLUSTER.", ".self::TYPE_END_CLUSTER.")
                ORDER BY l.course, lpo.lesson, lpo_display_order",
        ];
    }

    /**
     * @return array
     */
    public function getTransformConfiguration()
    {
        return [
            'class' => BaseTransformer::class,
            'map' => [
                'c_code' => [
                    'class' => LoadedCourseCodeLookup::class,
                    'properties' => ['course'],
                ],
                'lp_id' => [
                    'class' => LoadedCourseSectionFromLessonLookup::class,
                    'properties' => ['lesson'],
                ],
                'parent' => [
                    'class' => LoadedCourseModuleLessonLookup::class,
                    'properties' => ['lesson'],
                ],
                'previous' => [
                    'class' => LoadedLessonPageLookup::class,
                    'properties' => ['prev'],
                ],
                'item_type' => [
                    'class' => LessonPageType::class,
                    'properties' => ['qtype'],
                ],
                'title' => 'title',
                'display_order' => 'lpo_display_order',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => LessonPagesLoader::class,
        ];
    }
}
