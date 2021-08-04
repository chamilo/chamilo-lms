<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Class PastMeeting.
 * A past meeting, really a past meeting instance, as returned from the server.
 *
 * Each past meeting instance is identified by its own UUID.
 * Many past meeting instances can be part of the same meeting, identified by property 'id'.
 * Each instance has its own start time, participants and recording files.
 */
class PastMeeting extends Meeting
{
    /** @var string unique meeting instance ID */
    public $uuid;

    /** @var string meeting number */
    public $id;

    /** @var string host Zoom user id */
    public $host_id;

    /** @var string user display name */
    public $user_name;

    /** @var string */
    public $user_email;

    /** @var string "yyyy-MM-dd'T'HH:mm:ss'Z'" (GMT) */
    public $start_time;

    /** @var string "yyyy-MM-dd'T'HH:mm:ss'Z'" (GMT) */
    public $end_time;

    /** @var int sum of meeting minutes from all participants in the meeting. */
    public $total_minutes;

    /** @var int number of meeting participants */
    public $participants_count;

    /** @var string undocumented */
    public $dept;

    /**
     * Retrieves a past meeting instance from its identifier.
     *
     * @param string $uuid
     *
     * @throws Exception
     *
     * @return PastMeeting the past meeting
     */
    public static function fromUUID($uuid)
    {
        return static::fromJson(Client::getInstance()->send('GET', 'past_meetings/'.htmlentities($uuid)));
    }

    /**
     * Retrieves information on participants from a past meeting instance.
     *
     * @throws Exception
     *
     * @return ParticipantListItem[] participants
     */
    public function getParticipants()
    {
        return ParticipantList::loadInstanceParticipants($this->uuid);
    }
}
