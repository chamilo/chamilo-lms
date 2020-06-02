<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Exception;
use Firebase\JWT\JWT;

/**
 * Class JWTClient.
 *
 * @see https://marketplace.zoom.us/docs/api-reference/zoom-api
 * @package Chamilo\PluginBundle\Zoom
 */
class JWTClient
{
    const MEETING_LIST_TYPE_SCHEDULED = 'scheduled'; // all valid past meetings (unexpired),
                                                     // live meetings and upcoming scheduled meetings.
    const MEETING_LIST_TYPE_LIVE = 'live';           // all the ongoing meetings.
    const MEETING_LIST_TYPE_UPCOMING = 'upcoming';   // all upcoming meetings, including live meetings.

    private $token;

    /**
     * JWTClient constructor.
     * Requires JWT app credentials.
     *
     * @param string $apiKey    JWT API Key
     * @param string $apiSecret JWT API Secret
     */
    public function __construct($apiKey, $apiSecret)
    {
        $this->token = JWT::encode(
            [
                'iss' => $apiKey,
                'exp' => (time() + 60) * 1000, // will expire in one minute
            ],
            $apiSecret
        );
    }

    /**
     * Sends a Zoom API-compliant HTTP request and retrieves the response.
     *
     * On success, returns the body of the response
     * On error, throws an exception with an detailed error message
     *
     * @param string $httpMethod          GET, POST, PUT, DELETE ...
     * @param string $relativeQueryString what to append to URL https://api.zoom.us/v2/
     * @param object $requestBody         json-encoded body of the request
     *
     * @throws Exception describing the error (message and code)
     *
     * @return object json-decoded body of the response
     */
    public function send($httpMethod, $relativeQueryString, $requestBody = null)
    {
        $options = [
            CURLOPT_URL => "https://api.zoom.us/v2/$relativeQueryString",
            CURLOPT_CUSTOMREQUEST => $httpMethod,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => [
                'authorization: Bearer '.$this->token,
                'content-type: application/json',
            ],
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ];
        if (!is_null($requestBody)) {
            $jsonRequestBody = json_encode($requestBody);
            if (false === $jsonRequestBody) {
                throw new Exception('Could not generate JSON request body');
            }
            $options[CURLOPT_POSTFIELDS] = $jsonRequestBody;
        }

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $responseBody = curl_exec($curl);
        $responseCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError) {
            throw new Exception("cURL Error: $curlError");
        }

        if (false === $responseBody) {
            throw new Exception('cURL Error');
        }

        if (empty($responseCode)
            || $responseCode < 200
            || $responseCode >= 300
        ) {
            throw new Exception($responseBody, $responseCode);
        }

        if (empty($responseBody)) {
            return null;
        }

        $jsonDecodedResponseBody = json_decode($responseBody);
        if (is_null($jsonDecodedResponseBody)) {
            throw new Exception('Could not decode JSON response body');
        }

        return $jsonDecodedResponseBody;
    }

    /**
     * Returns the list of Zoom user permissions.
     *
     * @throws Exception describing the error (message and code)
     *
     * @return string[] Zoom user permissions
     */
    public function userPermissions()
    {
        return $this->send('GET', '/users/me/permissions')->permissions;
    }

    /**
     * Retrieves a limited list of meetings.
     *
     * @param string $type       MEETING_TYPE_SCHEDULED, MEETING_TYPE_LIVE or MEETING_TYPE_UPCOMING
     * @param int    $pageNumber number of the result page to be returned, starts at 1
     * @param int    $pageSize   how many meetings can fit in one page
     *
     * @throws Exception describing the error (message and code)
     *
     * @return MeetingList|object list of meetings
     */
    public function listMeetings($type, $pageNumber = 1, $pageSize = 30)
    {
        return $this->send('GET', "users/me/meetings?type=$type&page_size=$pageSize&page_number=$pageNumber");
    }

    /**
     * Gets a full list of meetings.
     *
     * @param string $type MEETING_TYPE_SCHEDULED, MEETING_TYPE_LIVE or MEETING_TYPE_UPCOMING
     *
     * @throws Exception describing the error (message and code)
     *
     * @return MeetingListItem[] meetings
     */
    public function listAllMeetings($type)
    {
        $meetings = [];
        $pageCount = 1;
        $pageSize = 300;
        $totalRecords = 0;
        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $response = $this->listMeetings($type, $pageNumber, $pageSize);
            if (!is_null($response)) {
                $meetings = array_merge($meetings, $response->meetings);
                if (0 === $totalRecords) {
                    $pageCount = $response->page_count;
                    $pageSize = $response->page_size;
                    $totalRecords = $response->total_records;
                }
            }
        }
        if (count($meetings) !== $totalRecords) {
            error_log('Zoom announced '.$totalRecords.' records but returned '.count($meetings));
        }

        return $meetings;
    }

    /**
     * Retrieves the list of ended meeting instances.
     *
     * @param int $meetingId meeting ID
     *
     * @throws Exception describing the error (message and code)
     *
     * @return MeetingInstances|object list of meeting instances
     */
    public function listEndedMeetingInstances($meetingId)
    {
        return $this->send('GET', "past_meetings/$meetingId/instances");
    }

    /**
     * Creates a meeting and returns it.
     *
     * @param Meeting $meeting meeting to create with at lead $topic and $type
     *
     * @throws Exception describing the error (message and code)
     *
     * @return Meeting|object meeting
     */
    public function createMeeting($meeting)
    {
        return $this->send('POST', 'users/me/meetings', $meeting);
    }

    /**
     * Retrieves a meeting.
     *
     * @param int $meetingId meeting identifier
     *
     * @throws Exception describing the error (message and code)
     *
     * @return Meeting|object meeting
     */
    public function getMeeting($meetingId)
    {
        return $this->send('GET', 'meetings/'.$meetingId);
    }

    /**
     * Updates a meeting's attributes.
     *
     * @param int     $meetingId meeting identifier
     * @param Meeting $meeting modified meeting object (only need modified properties)
     *
     * @throws Exception describing the error (message and code)
     */
    public function updateMeeting($meetingId, $meeting)
    {
        $this->send('PATCH', 'meetings/'.$meetingId, $meeting);
    }

    /**
     * Ends a meeting.
     *
     * @param int $meetingId meeting identifier
     *
     * @throws Exception describing the error (message and code)
     */
    public function endMeeting($meetingId)
    {
        $this->send('PUT', "meetings/$meetingId/status", (object) [ 'action' => 'end' ]);
    }

    /**
     * Deletes a meeting.
     *
     * @param int $meetingId meeting identifier
     *
     * @throws Exception describing the error (message and code)
     */
    public function deleteMeeting($meetingId)
    {
        $this->send('DELETE', 'meetings/'.$meetingId);
    }

    /**
     * Adds a meeting registrant.
     *
     * @param int               $meetingId meeting identifier
     * @param MeetingRegistrant $registrant with at least 'email' and 'first_name'
     * @param string            $occurrenceIds separated by comma
     *
     * @throws Exception describing the error (message and code)
     *
     * @return CreatedRegistration|object with unique join_url and registrant_id properties
     */
    public function addMeetingRegistrant($meetingId, $registrant, $occurrenceIds = '')
    {
        $path = 'meetings/'.$meetingId.'/registrants';
        if (!empty($occurrenceIds)) {
            $path .= "?occurrence_ids=$occurrenceIds";
        }

        return $this->send('POST', $path, $registrant);
    }

    /**
     * List meeting registrants.
     *
     * @param integer $meetingId
     */
    public function listMeetingRegistrants($meetingId)
    {
        // TODO "/meetings/$meetingId/registrants";
    }

    /**
     * Retrieves a past meeting's details.
     *
     * @param string $meetingUUID the meeting UUID
     *
     * @throws Exception describing the error (message and code)
     *
     * @return PastMeeting|object meeting
     */
    public function getPastMeetingDetails($meetingUUID)
    {
        return $this->send('GET', 'past_meetings/'.$meetingUUID);
    }

    /**
     * Gets all the recordings from a meeting.
     * The recording files can be downloaded via the `download_url` property listed in the response.
     *
     * @param $meetingUUID
     *
     * @throws Exception describing the error (message and code)
     *
     * @return object an object with string property 'share_url' and array property 'recording_files'
     */
    public function listRecordings($meetingUUID)
    {
        return $this->send('GET', 'meetings/'.$this->doubleEncode($meetingUUID).'/recordings');
    }

    /**
     * Retrieves information on participants from a past meeting.
     *
     * @param string $meetingUUID the meeting instance UUID
     * @param int    $pageNumber
     * @param int    $pageSize
     *
     * @throws Exception describing the error (message and code)
     *
     * @return ParticipantList|object
     */
    public function getParticipants($meetingUUID, $pageNumber = 1, $pageSize = 30)
    {
        return $this->send(
            'GET',
            'past_meetings/'.$this->doubleEncode(
                $meetingUUID
            )."/participants?page_size=$pageSize&page_number=$pageNumber"
        );
    }

    /**
     * Gets a full list of participants.
     *
     * @param string $meetingUUID the meeting instance UUID
     *
     * @throws Exception describing the error (message and code)
     *
     * @return ParticipantListItem[] participants
     */
    public function getAllParticipants($meetingUUID)
    {
        $participants = [];
        $pageCount = 1;
        $pageSize = 300;
        $totalRecords = 0;
        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $response = $this->getParticipants($meetingUUID, $pageNumber, $pageSize);
            if (!is_null($response)) {
                $participants = array_merge($participants, $response->participants);
                if (0 === $totalRecords) {
                    $pageCount = $response->page_count;
                    $pageSize = $response->page_size;
                    $totalRecords = $response->total_records;
                }
            }
        }
        if (count($participants) !== $totalRecords) {
            error_log('Zoom announced '.$totalRecords.' records but returned '.count($participants));
        }

        return $participants;
    }

    /**
     * Double-encodes a string.
     * Used for meeting UUIDs that are inserted into a URL.
     *
     * @param string $string the string to double-encode
     *
     * @return string double-encoded string
     */
    private function doubleEncode($string)
    {
        return htmlentities($string, ENT_COMPAT, 'utf-8', true);
    }

}
