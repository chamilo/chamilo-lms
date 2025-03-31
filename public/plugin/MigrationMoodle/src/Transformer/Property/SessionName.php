<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class SessionName.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class SessionName implements TransformPropertyInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform(array $data)
    {
        $pieces = [
            '[',
            $data['username'],
            ']',
            ' ',
            $data['session_name'],
        ];

        return implode('', $pieces);
    }
}
