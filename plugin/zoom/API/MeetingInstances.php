<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Exception;

class MeetingInstances
{
    use JsonDeserializable;

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
     * @see JsonDeserializable::itemClass()
     *
     * @param string $propertyName array property name
     * @throws Exception on wrong propertyName
     */
    protected function itemClass($propertyName)
    {
        if ('meetings' === $propertyName) {
            return MeetingInstance::class;
        }
        throw new Exception("No such array property $propertyName");
    }
}
