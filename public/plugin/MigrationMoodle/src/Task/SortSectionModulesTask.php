<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\SortSectionModuleLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseSectionLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\SectionSequenceLookup;

/**
 * Class SortSectionModulesTask.
 *
 * Task to fix the display order for learning path items.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class SortSectionModulesTask extends BaseTask
{
    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedCoursesFilterExtractor::class,
            'query' => "SELECT cm.id, cs.course, cm.section, cs.sequence
                FROM mdl_course_modules cm
                INNER JOIN mdl_modules m ON cm.module = m.id
                INNER JOIN mdl_course_sections cs ON (cm.course = cs.course AND cm.section = cs.id )
                WHERE cs.course NOT IN (
                    SELECT sco.course
                    FROM mdl_scorm sco
                    INNER JOIN mdl_course_modules cm ON (sco.course = cm.course AND cm.instance = sco.id)
                    INNER JOIN mdl_modules m ON cm.module = m.id
                    INNER JOIN mdl_course_sections cs ON (cm.course = cs.course AND cm.section = cs.id )
                    WHERE m.name = 'scorm'
                )
                ORDER BY cs.course, cs.section, FIND_IN_SET(cm.id, cs.sequence)",
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformConfiguration()
    {
        return [
            'class' => BaseTransformer::class,
            'map' => [
                'c_id' => [
                    'class' => LoadedCourseLookup::class,
                    'properties' => ['course'],
                ],
                'lp_id' => [
                    'class' => LoadedCourseSectionLookup::class,
                    'properties' => ['section'],
                ],
                'order_list' => [
                    'class' => SectionSequenceLookup::class,
                    'properties' => ['sequence'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => SortSectionModuleLoader::class,
        ];
    }
}
