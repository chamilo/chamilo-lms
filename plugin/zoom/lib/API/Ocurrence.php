<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

class Ocurrence
{
    use JsonDeserializableTrait;

    public $occurrence_id;
    public $start_time;
    public $duration;
    public $status;

    public function itemClass($propertyName)
    {
        throw new Exception("No such array property $propertyName");
    }
}
