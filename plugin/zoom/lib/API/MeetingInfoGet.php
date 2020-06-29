<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Class MeetingInfoGet
 * Full Meeting as returned by the server, with unique identifiers and current status.
 *
 * @package Chamilo\PluginBundle\Zoom\API
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
     * @param Client $client
     * @param int    $id
     *
     * @throws Exception
     *
     * @return static the meeting
     */
    public static function fromId($client, $id)
    {
        return static::fromJson($client->send('GET', "meetings/$id"));
    }

    /**
     * Updates the meeting on server.
     *
     * @param Client $client
     *
     * @throws Exception
     */
    public function update($client)
    {
        $client->send('PATCH', 'meetings/'.$this->id, [], $this);
    }

    /**
     * Ends the meeting on server.
     *
     * @param Client $client
     *
     * @throws Exception
     */
    public function endNow($client)
    {
        $client->send('PUT', "meetings/$this->id/status", [], (object) ['action' => 'end']);
    }

    /**
     * Deletes the meeting on server.
     *
     * @param Client $client
     *
     * @throws Exception
     */
    public function delete($client)
    {
        $client->send('DELETE', "meetings/$this->id");
    }

    /**
     * Adds a registrant to the meeting.
     *
     * @param Client            $client
     * @param MeetingRegistrant $registrant    with at least 'email' and 'first_name'.
     *                                         'last_name' will also be recorded by Zoom.
     *                                         Other properties remain ignored, or not returned by Zoom
     *                                         (at least while using profile "Pro")
     * @param string            $occurrenceIds separated by comma
     *
     * @throws Exception
     *
     * @return CreatedRegistration with unique join_url and registrant_id properties
     */
    public function addRegistrant($client, $registrant, $occurrenceIds = '')
    {
        return CreatedRegistration::fromJson(
            $client->send(
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
     * @param Client              $client
     * @param MeetingRegistrant[] $registrants   registrants to remove (id and email)
     * @param string              $occurrenceIds separated by comma
     *
     * @throws Exception
     */
    public function removeRegistrants($client, $registrants, $occurrenceIds = '')
    {
        if (!empty($registrants)) {
            $client->send(
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
     * @param Client $client
     *
     * @throws Exception
     *
     * @return MeetingRegistrantListItem[] the meeting registrants
     */
    public function getRegistrants($client)
    {
        return MeetingRegistrantList::loadMeetingRegistrants($client, $this->id);
    }

    /**
     * Retrieves the meeting's instances.
     *
     * @param Client $client
     *
     * @throws Exception
     *
     * @return MeetingInstance[]
     */
    public function getInstances($client)
    {
        return MeetingInstances::fromMeetingId($client, $this->id)->meetings;
    }
}
