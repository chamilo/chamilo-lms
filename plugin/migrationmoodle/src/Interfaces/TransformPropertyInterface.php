<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Interfaces;

/**
 * Interface TransformPropertyInterface.
 */
interface TransformPropertyInterface
{
    /**
     * @param array $data
     *
     * @return mixed
     */
    public function transform(array $data);
}
