<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

class FollowUpUsers
{
    use JsonDeserializableTrait;

    /**
     * @var bool
     */
    public $enable;
    /**
     * @var
     */
    public $type;

    public function itemClass($propertyName)
    {
        throw new Exception("No such array property $propertyName");
    }
}
