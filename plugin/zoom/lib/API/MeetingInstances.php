<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

class MeetingInstances
{
    use JsonDeserializableTrait;

    /** @var MeetingInstance[] List of ended meeting instances. */
    public $meetings;

    /**
     * MeetingInstances constructor.
     */
    public function __construct()
    {
        $this->meetings = [];
    }

    /**
     * {@inheritdoc}
     */
    protected function itemClass($propertyName)
    {
        if ('meetings' === $propertyName) {
            return MeetingInstance::class;
        }
        throw new Exception("No such array property $propertyName");
    }
}
