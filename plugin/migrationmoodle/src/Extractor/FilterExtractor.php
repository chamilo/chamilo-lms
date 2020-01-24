<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Extractor;

use Chamilo\PluginBundle\MigrationMoodle\Traits\MapTrait\MapTrait;

/**
 * Class FilterExtractor.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Extractor
 */
abstract class FilterExtractor extends BaseExtractor
{
    use MapTrait;

    /**
     * @param int $id
     *
     * @return bool
     */
    protected function existsExtracted($id)
    {
        $taskName = $this->getTaskName();

        $result = \Database::select(
            'COUNT(1) AS c',
            'plugin_migrationmoodle_item i INNER JOIN plugin_migrationmoodle_task t ON i.task_id = t.id',
            [
                'where' => [
                    't.name = ? AND i.extracted_id = ?' => [$taskName, $id],
                ],
            ],
            'first'
        );

        return $result['c'] > 0;
    }
}
