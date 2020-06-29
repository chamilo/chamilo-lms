<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Exception;

/**
 * Class UserMeetingRegistrantList. A list of users registered to a meeting.
 *
 * @see UserMeetingRegistrantListItem
 *
 * @package Chamilo\PluginBundle\Zoom
 */
class UserMeetingRegistrantList extends API\MeetingRegistrantList
{
    /**
     * {@inheritdoc}
     */
    public function itemClass($propertyName)
    {
        if ('registrants' === $propertyName) {
            return UserMeetingRegistrantListItem::class;
        }

        return parent::itemClass($propertyName);
    }

    /**
     * Retrieves all registrant for a meeting.
     *
     * @param API\Client $client
     * @param int        $meetingId
     *
     * @throws Exception
     *
     * @return UserMeetingRegistrantListItem[] all registrants of the meeting
     */
    public static function loadUserMeetingRegistrants($client, $meetingId)
    {
        return static::loadItems('registrants', $client, "meetings/$meetingId/registrants");
    }
}
