<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Extractor;

use Chamilo\PluginBundle\MigrationMoodle\Task\UsersTask;

/**
 * Class LoadedUsersFilterExtractor.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Extractor
 */
class LoadedUsersFilterExtractor extends FilterExtractor
{
    /**
     * LoadedUsersFilterExtractor constructor.
     */
    public function __construct(array $configuration)
    {
        parent::__construct($configuration);

        $this->calledClass = UsersTask::class;
    }

    /**
     * @return bool
     */
    public function filter(array $sourceData)
    {
        $userId = $sourceData['id'];

        if (isset($sourceData['userid'])) {
            $userId = $sourceData['userid'];
        }

        return !$this->existsExtracted($userId);
    }
}
