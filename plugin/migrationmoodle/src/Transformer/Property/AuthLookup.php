<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class AuthLookup.
 */
class AuthLookup implements TransformPropertyInterface
{
    /**
     * @return string
     */
    public function transform(array $data)
    {
        $auth = $data['auth'];

        return $auth === 'manual' ? 'platform' : $auth;
    }
}
