<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Class MeetingInfoGet
 * Full Meeting as returned by the server, with unique identifiers and current status.
 */
class MeetingInfoGet extends MeetingInfo
{
    /** @var string unique meeting instance ID */
    public $uuid;

    /** @var string meeting number */
    public $id;

    /** @var string host Zoom user id */
    public $host_id;

    /** @var string meeting status, either "waiting", "started" or "finished" */
    public $status;

    /** @var string undocumented */
    public $pstn_password;

    /** @var string Encrypted password for third party endpoints (H323/SIP). */
    public $encrypted_password;

    /**
     * Retrieves a meeting from its numeric identifier.
     *
     * @param int $id
     *
     * @throws Exception
     *
     * @return static the meeting
     */
    public static function fromId($id)
    {
        return static::fromJson(Client::getInstance()->send('GET', "meetings/$id"));
    }

    /**
     * Updates the meeting on server.
     *
     * @throws Exception
     */
    public function update(): void
    {
        Client::getInstance()->send('PATCH', 'meetings/'.$this->id, [], $this);
    }

    /**
     * Ends the meeting on server.
     *
     * @throws Exception
     */
    public function endNow()
    {
        Client::getInstance()->send('PUT', "meetings/$this->id/status", [], (object) ['action' => 'end']);
    }

    /**
     * Deletes the meeting on server.
     *
     * @throws Exception
     */
    public function delete()
    {
        Client::getInstance()->send('DELETE', "meetings/$this->id");
    }

    /**
     * Adds a registrant to the meeting.
     *
     * @param RegistrantSchema $registrant    with at least 'email' and 'first_name'.
     *                                        'last_name' will also be recorded by Zoom.
     *                                        Other properties remain ignored, or not returned by Zoom
     *                                        (at least while using profile "Pro")
     * @param string           $occurrenceIds separated by comma
     *
     * @throws Exception
     *
     * @return CreatedRegistration with unique join_url and registrant_id properties
     */
    public function addRegistrant(RegistrantSchema $registrant, string $occurrenceIds = ''): CreatedRegistration
    {
        return CreatedRegistration::fromJson(
            Client::getInstance()->send(
                'POST',
                "meetings/$this->id/registrants",
                empty($occurrenceIds) ? [] : ['occurrence_ids' => $occurrenceIds],
                $registrant
            )
        );
    }

    /**
     * Removes registrants from the meeting.
     *
     * @param MeetingRegistrant[] $registrants   registrants to remove (id and email)
     * @param string              $occurrenceIds separated by comma
     *
     * @throws Exception
     */
    public function removeRegistrants($registrants, $occurrenceIds = '')
    {
        if (!empty($registrants)) {
            Client::getInstance()->send(
                'PUT',
                "meetings/$this->id/registrants/status",
                empty($occurrenceIds) ? [] : ['occurrence_ids' => $occurrenceIds],
                (object) [
                    'action' => 'cancel',
                    'registrants' => $registrants,
                ]
            );
        }
    }

    /**
     * Retrieves meeting registrants.
     *
     * @throws Exception
     *
     * @return MeetingRegistrantListItem[] the meeting registrants
     */
    public function getRegistrants()
    {
        return MeetingRegistrantList::loadMeetingRegistrants($this->id);
    }

    /**
     * Retrieves the meeting's instances.
     *
     * @throws Exception
     *
     * @return MeetingInstance[]
     */
    public function getInstances()
    {
        return MeetingInstances::fromMeetingId($this->id)->meetings;
    }
}
