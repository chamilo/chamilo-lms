<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\BaseExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\QuestionCategoriesLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseFromQuestionCategoryLookup;

/**
 * Class QuestionCategoriesTask.
 *
 * Task for create categories for Chamilo quiz questions coming from a Moodle questions categories.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class QuestionCategoriesTask extends BaseTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => BaseExtractor::class,
            'query' => "SELECT qc.id, qc.name, qc.info, c.contextlevel, c.instanceid
                FROM mdl_question_categories qc
                INNER JOIN mdl_context c ON qc.contextid = c.id
                WHERE c.contextlevel IN (50, 70)
                    AND qc.parent != 0",
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
                'c_id' => [
                    'class' => LoadedCourseFromQuestionCategoryLookup::class,
                    'properties' => ['contextlevel', 'instanceid'],
                ],
                'name' => 'name',
                'description' => 'info',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => QuestionCategoriesLoader::class,
        ];
    }
}
