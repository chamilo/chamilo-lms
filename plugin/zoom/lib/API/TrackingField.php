<?php


namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

class TrackingField
{
    use JsonDeserializableTrait;

    /** @var string Tracking fields type */
    public $field;

    /** @var string Tracking fields value */
    public $value;

    /**
     * @inheritDoc
     */
    public function itemClass($propertyName)
    {
        throw new Exception("no such array property $propertyName");
    }
}
