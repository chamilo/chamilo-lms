<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Interfaces;

/**
 * Interface TransformerInterface.
 */
interface TransformerInterface
{
    /**
     * @param array $sourceData
     *
     * @throws \Exception
     *
     * @return array
     */
    public function transform(array $sourceData);
}
