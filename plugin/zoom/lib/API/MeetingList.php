<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Class MeetingList. Lists Meetings.
 *
 * @see MeetingListItem
 */
class MeetingList
{
    use Pagination;

    public const TYPE_SCHEDULED = 'scheduled'; // all valid past meetings (unexpired),
    // live meetings and upcoming scheduled meetings.
    public const TYPE_LIVE = 'live';           // all the ongoing meetings.
    public const TYPE_UPCOMING = 'upcoming';   // all upcoming meetings, including live meetings.

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
     * Retrieves all meetings of a type.
     *
     * @param int $type TYPE_SCHEDULED, TYPE_LIVE or TYPE_UPCOMING
     *
     * @throws Exception
     *
     * @return MeetingListItem[] all meetings
     */
    public static function loadMeetings($type)
    {
        return static::loadItems('meetings', 'users/me/meetings', ['type' => $type]);
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
