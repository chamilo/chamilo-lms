<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\UserExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\UserScormProgressLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserLookup;

/**
 * Class UsersScormsProgressTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class UsersScormsProgressTask extends EfcUsersTask
{
    /**
     * @inheritDoc
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => UserExtractor::class,
            'query' => "SELECT DISTINCT userid id FROM mdl_scorm_scoes_track"
        ];
    }

    /**
     * @inheritDoc
     */
    public function getTransformConfiguration()
    {
        return [
            'class' => BaseTransformer::class,
            'map' => [
                'user_id' => [
                    'class' => LoadedUserLookup::class,
                    'properties' => ['id'],
                ],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => UserScormProgressLoader::class,
        ];
    }
}
