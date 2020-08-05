<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Class MeetingInstance
 * A meeting (numerical id) can have one or more instances (string UUID).
 * Each instance has its own start time, participants and recording files.
 *
 * @see MeetingInstances
 * @see PastMeeting for the full record
 */
class MeetingInstance
{
    /** @var string */
    public $uuid;

    /** @var string */
    public $start_time;

    /**
     * Retrieves the recording of the instance.
     *
     * @throws Exception with code 404 when there is no recording for this meeting
     *
     * @return RecordingMeeting the recording
     */
    public function getRecordings()
    {
        return RecordingMeeting::fromJson(
            Client::getInstance()->send('GET', 'meetings/'.htmlentities($this->uuid).'/recordings')
        );
    }

    /**
     * Retrieves the instance's participants.
     *
     * @throws Exception
     *
     * @return ParticipantListItem[]
     */
    public function getParticipants()
    {
        return ParticipantList::loadInstanceParticipants($this->uuid);
    }
}
