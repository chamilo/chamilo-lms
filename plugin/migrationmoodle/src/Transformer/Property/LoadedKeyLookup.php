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
     * @param array $data
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function transform(array $data)
    {
        $id = current($data);

        $mapLog = $this->parseMapFile();

        $migration = $this->search($mapLog, $id);

        return $migration['loaded'];
    }

    /**
     * @param array $mapLog
     * @param int   $searchedId
     *
     * @return array
     */
    private function search(array $mapLog, $searchedId)
    {
        if (empty($searchedId) || empty($mapLog)) {
            return null;
        }

        $filtered = array_filter(
            $mapLog,
            function (array $item) use ($searchedId) {
                return $item['extracted'] == $searchedId;
            }
        );

        return current($filtered) ?: null;
    }
}
