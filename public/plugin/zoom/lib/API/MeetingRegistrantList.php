<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Class MeetingRegistrantList. List of meeting registrants.
 *
 * @see MeetingRegistrantListItem
 */
class MeetingRegistrantList
{
    use Pagination;

    /** @var MeetingRegistrantListItem[] */
    public $registrants;

    /**
     * MeetingRegistrantList constructor.
     */
    public function __construct()
    {
        $this->registrants = [];
    }

    /**
     * Retrieves all registrant for a meeting.
     *
     * @param int $meetingId
     *
     * @throws Exception
     *
     * @return MeetingRegistrantListItem[] all registrants of the meeting
     */
    public static function loadMeetingRegistrants($meetingId)
    {
        return static::loadItems('registrants', "meetings/$meetingId/registrants");
    }

    /**
     * {@inheritdoc}
     */
    public function itemClass($propertyName)
    {
        if ('registrants' === $propertyName) {
            return MeetingRegistrantListItem::class;
        }
        throw new Exception("no such array property $propertyName");
    }
}
