<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Exception;

class MeetingList
{
    use Pagination;

    /** @var MeetingListItem[] */
    public $meetings;

    /**
     * MeetingList constructor.
     */
    public function __construct()
    {
        $this->meetings = [];
    }

    /**
     * @see JsonDeserializable::itemClass()
     *
     * @param string $propertyName array property name
     *
     * @throws Exception on wrong propertyName
     *
     * @return string
     */
    protected function itemClass($propertyName)
    {
        if ('meetings' === $propertyName) {
            return MeetingListItem::class;
        }
        throw new Exception("No such array property $propertyName");
    }
}
