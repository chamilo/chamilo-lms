<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedScormsFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\ScormScoLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseCodeLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedScormLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\ScormScoParentLookup;

/**
 * Class ScormScoesTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class ScormScoesTask extends BaseTask
{
    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedScormsFilterExtractor::class,
            'query' => "SELECT i.id, i.title, i.scormtype, i.launch, i.identifier, i.scorm, i.parent, s.course
                FROM mdl_scorm_scoes i
                INNER JOIN mdl_scorm s ON i.scorm = s.id
                WHERE i.parent != '/' ORDER BY s.id, i.sortorder",
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
                'title' => 'title',
                'item_type' => 'scormtype',
                'path' => 'launch',
                'ref' => 'identifier',
                'lp_id' => [
                    'class' => LoadedScormLookup::class,
                    'properties' => ['scorm'],
                ],
                'parent_item_id' => [
                    'class' => ScormScoParentLookup::class,
                    'properties' => ['parent', 'scorm'],
                ],
                'c_code' => [
                    'class' => LoadedCourseCodeLookup::class,
                    'properties' => ['course'],
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
            'class' => ScormScoLoader::class,
        ];
    }
}
