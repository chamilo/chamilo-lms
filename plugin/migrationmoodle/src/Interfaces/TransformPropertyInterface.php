<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Interfaces;

/**
 * Interface TransformPropertyInterface.
 */
interface TransformPropertyInterface
{
    /**
     * @return mixed
     */
    public function transform(array $data);
}
