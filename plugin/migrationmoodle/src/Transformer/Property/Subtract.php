<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class Subtract.
 *
 * Make a subtract from a minuend nd subtrahend.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class Subtract implements TransformPropertyInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform(array $data)
    {
        list($minuend, $subtrahend) = array_values($data);

        return $minuend - $subtrahend;
    }
}
