<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Class GlobalDialInNumber.
 * A list of these is included in a meeting settings.
 */
class GlobalDialInNumber
{
    use JsonDeserializableTrait;

    /** @var string Country code. For example, BR. */
    public $country;

    /** @var string Full name of country. For example, Brazil. */
    public $country_name;

    /** @var string City of the number, if any. For example, Chicago. */
    public $city;

    /** @var string Phone number. For example, +1 2332357613. */
    public $number;

    /** @var string Type of number. Either "toll" or "tollfree". */
    public $type;

    /**
     * {@inheritdoc}
     */
    public function itemClass($propertyName)
    {
        throw new Exception("No such array property $propertyName");
    }
}
