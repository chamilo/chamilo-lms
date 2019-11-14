<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\BaseExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\LpItemsLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseCodeLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLpDirFromLessonLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLpFromLessonLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLpItemLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LpItemTypeLookup;

/**
 * Class LpItemsTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class LpItemsTask extends BaseTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => BaseExtractor::class,
            'query' => 'SELECT lp.id, lp.lessonid, lp.prevpageid, lp.nextpageid, lp.qtype, lp.title, l.course
                FROM mdl_lesson_pages lp
                INNER JOIN mdl_lesson l ON lp.lessonid = l.id
                WHERE lp.qtype NOT IN (21, 30, 31)
                ORDER BY
                    l.id,
                    CASE
                        WHEN lp.id > lp.prevpageid THEN lp.prevpageid
                        WHEN lp.id < lp.prevpageid THEN lp.id
                    END',
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
                    'class' => LoadedLpFromLessonLookup::class,
                    'properties' => ['lessonid']
                ],
                'parent' => [
                    'class' => LoadedLpDirFromLessonLookup::class,
                    'properties' => ['lessonid']
                ],
                'previous' => [
                    'class' => LoadedLpItemLookup::class,
                    'properties' => ['prevpageid'],
                ],
                'item_type' => [
                    'class' => LpItemTypeLookup::class,
                    'properties' => ['qtype'],
                ],
                'title' => 'title',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => LpItemsLoader::class,
        ];
    }
}
