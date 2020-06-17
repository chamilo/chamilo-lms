<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

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
     * {@inheritdoc}
     */
    public function itemClass($propertyName)
    {
        if ('meetings' === $propertyName) {
            return MeetingListItem::class;
        }
        throw new Exception("No such array property $propertyName");
    }
}
