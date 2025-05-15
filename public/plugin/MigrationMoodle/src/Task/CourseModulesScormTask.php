<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\CourseModulesScormLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\DateTimeObject;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;

/**
 * Class CourseModulesScormTask.
 *
 * Task to convert Moodle scorm in Chamilo scorm.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class CourseModulesScormTask extends BaseTask
{
    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedCoursesFilterExtractor::class,
            'query' => "SELECT
                    sco.id,
                    sco.course,
                    sco.name,
                    sco.reference,
                    sco.version,
                    sco.maxgrade,
                    sco.hidetoc,
                    i.identifier,
                    cm.added,
                    sco.timemodified
                FROM mdl_scorm sco
                INNER JOIN mdl_scorm_scoes i on sco.id = i.scorm
                INNER JOIN mdl_course_modules cm ON (sco.course = cm.course AND cm.instance = sco.id)
                INNER JOIN mdl_modules m ON cm.module = m.id
                INNER JOIN mdl_course_sections cs ON (cm.course = cs.course AND cm.section = cs.id )
                WHERE m.name = 'scorm'
                    AND i.parent = '/'
                ORDER BY cs.id, FIND_IN_SET(cm.id, cs.sequence)",
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
                'name' => 'name',
                'ref' => 'identifier',
                'path' => 'reference',
                'use_max_score' => 'maxgrade',
                'hide_toc_frame' => 'hidetoc',
                'created_on' => [
                    'class' => DateTimeObject::class,
                    'properties' => ['added'],
                ],
                'modified_on' => [
                    'class' => DateTimeObject::class,
                    'properties' => ['timemodified'],
                ],
                'publicated_on' => [
                    'class' => DateTimeObject::class,
                    'properties' => ['added'],
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
            'class' => CourseModulesScormLoader::class,
        ];
    }
}
