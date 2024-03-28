<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Interfaces;

/**
 * Interface TransformerInterface.
 */
interface TransformerInterface
{
    /**
     * @throws \Exception
     *
     * @return array
     */
    public function transform(array $sourceData);
}
