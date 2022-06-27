<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class Explode.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class Explode implements TransformPropertyInterface
{
    public const SEPARATOR = '@mm@';

    /**
     * @return mixed
     */
    public function transform(array $data)
    {
        $value = current($data);

        return explode(self::SEPARATOR, $value);
    }
}
