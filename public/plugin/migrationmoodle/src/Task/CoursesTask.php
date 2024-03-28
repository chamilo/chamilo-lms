<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\BaseExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\CoursesLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\CourseCategoryLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\CourseVisibility;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\DateTimeObject;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\Language;

/**
 * Class CoursesTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class CoursesTask extends BaseTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        $query = "SELECT * FROM mdl_course";

        $userFilter = $this->plugin->getUserFilterSetting();

        if (!empty($userFilter)) {
            $query = "SELECT DISTINCT c.*
                FROM mdl_course c
                INNER JOIN mdl_context ctx ON c.id = ctx.instanceid
                INNER JOIN mdl_role_assignments ra ON ctx.id = ra.contextid
                INNER JOIN mdl_user u ON ra.userid = u.id
                WHERE u.username LIKE '$userFilter%'";
        }

        return [
            'class' => BaseExtractor::class,
            'query' => $query,
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
                'title' => 'fullname',
                'wanted_code' => 'shortname',
                'course_category' => [
                    'class' => CourseCategoryLookup::class,
                    'properties' => ['category'],
                ],
                'course_language' => [
                    'class' => Language::class,
                    'properties' => ['lang'],
                ],
                'visibility' => [
                    'class' => CourseVisibility::class,
                    'properties' => ['visible'],
                ],
                'description' => 'summary',
                'creation_date' => [
                    'class' => DateTimeObject::class,
                    'properties' => ['timecreated'],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => CoursesLoader::class,
        ];
    }
}
