<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Extractor;

use Chamilo\PluginBundle\MigrationMoodle\Task\EfcUsersTask;

/**
 * Class UserExtractor.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Extractor
 */
class UserExtractor extends FilterExtractor
{
    /**
     * UserExtractor constructor.
     *
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        parent::__construct($configuration);

        $this->calledClass = EfcUsersTask::class;
    }

    /**
     * @param array $sourceData
     *
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
