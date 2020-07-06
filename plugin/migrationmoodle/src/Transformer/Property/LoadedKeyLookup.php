<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;
use Chamilo\PluginBundle\MigrationMoodle\Traits\MapTrait\MapTrait;

/**
 * Class LoadedKeyLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
abstract class LoadedKeyLookup implements TransformPropertyInterface
{
    use MapTrait;

    /**
     * @throws \Exception
     *
     * @return mixed
     */
    public function transform(array $data)
    {
        $id = current($data);

        $migration = $this->search($id);

        return isset($migration['loaded_id']) ? $migration['loaded_id'] : 0;
    }

    /**
     * @param int $searchedId
     *
     * @throws \Exception
     *
     * @return array
     */
    private function search($searchedId)
    {
        if (empty($searchedId)) {
            return null;
        }

        $taskName = $this->getTaskName();

        $itemInfo = \Database::select(
            'i.*',
            'plugin_migrationmoodle_item i INNER JOIN plugin_migrationmoodle_task t ON i.task_id = t.id',
            [
                'where' => [
                    't.name = ? AND i.extracted_id = ?' => [$taskName, $searchedId],
                ],
            ],
            'first'
        );

        return $itemInfo ?: null;
    }
}
