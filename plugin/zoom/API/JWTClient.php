<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Exception;
use Firebase\JWT\JWT;

/**
 * Class JWTClient.
 *
 * @see https://marketplace.zoom.us/docs/api-reference/zoom-api
 *
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
     * @param string $httpMethod   GET, POST, PUT, DELETE ...
     * @param string $relativePath to append to https://api.zoom.us/v2/
     * @param array  $parameters   request query parameters
     * @param object $requestBody  json-encoded body of the request
     *
     * @throws Exception describing the error (message and code)
     *
     * @return string response body (not json-decoded)
     */
    public function send($httpMethod, $relativePath, $parameters = [], $requestBody = null)
    {
        $options = [
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

        $url = "https://api.zoom.us/v2/$relativePath";
        if (!empty($parameters)) {
            $url .= '?'.http_build_query($parameters);
        }
        $curl = curl_init($url);
        if (false === $curl) {
            throw new Exception("curl_init returned false");
        }
        curl_setopt_array($curl, $options);
        $responseBody = curl_exec($curl);
        $responseCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError) {
            throw new Exception("cURL Error: $curlError");
        }

        if (false === $responseBody || !is_string($responseBody)) {
            throw new Exception('cURL Error');
        }

        if (empty($responseCode)
            || $responseCode < 200
            || $responseCode >= 300
        ) {
            throw new Exception($responseBody, $responseCode);
        }

        return $responseBody;
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
    public function getMeetings($type)
    {
        return $this->getFullList("users/me/meetings", MeetingList::class, 'meetings', ['type'=>$type]);
    }

    /**
     * Retrieves the list of ended meeting instances.
     *
     * @param int $meetingId meeting ID
     *
     * @throws Exception describing the error (message and code)
     *
     * @return MeetingInstance[] list of meeting instances
     */
    public function getEndedMeetingInstances($meetingId)
    {
        return MeetingInstances::fromJson($this->send('GET', "past_meetings/$meetingId/instances"))->meetings;
    }

    /**
     * Creates a meeting and returns it.
     *
     * @param Meeting $meeting meeting to create with at lead $topic and $type
     *
     * @throws Exception describing the error (message and code)
     *
     * @return MeetingInfoGet meeting
     */
    public function createMeeting($meeting)
    {
        return MeetingInfoGet::fromJson($this->send('POST', 'users/me/meetings', [], $meeting));
    }

    /**
     * Retrieves a meeting.
     *
     * @param int $meetingId meeting identifier
     *
     * @throws Exception describing the error (message and code)
     *
     * @return Meeting meeting
     */
    public function getMeeting($meetingId)
    {
        return MeetingInfoGet::fromJson($this->send('GET', 'meetings/'.$meetingId));
    }

    /**
     * Updates a meeting's attributes.
     *
     * @param int     $meetingId meeting identifier
     * @param Meeting $meeting   modified meeting object (only need modified properties)
     *
     * @throws Exception describing the error (message and code)
     */
    public function updateMeeting($meetingId, $meeting)
    {
        $this->send('PATCH', 'meetings/'.$meetingId, [], $meeting);
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
        $this->send('PUT', "meetings/$meetingId/status", [], (object) ['action' => 'end']);
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
     * @param int               $meetingId     meeting identifier
     * @param MeetingRegistrant $registrant    with at least 'email' and 'first_name'
     * @param string            $occurrenceIds separated by comma
     *
     * @throws Exception describing the error (message and code)
     *
     * @return CreatedRegistration with unique join_url and registrant_id properties
     */
    public function addRegistrant($meetingId, $registrant, $occurrenceIds = '')
    {
        $path = 'meetings/'.$meetingId.'/registrants';
        if (!empty($occurrenceIds)) {
            $path .= "?occurrence_ids=$occurrenceIds";
        }

        return CreatedRegistration::fromJson($this->send('POST', $path, [], $registrant));
    }

    /**
     * List meeting registrants.
     *
     * @param int $meetingId
     *
     * @throws Exception
     *
     * @return MeetingRegistrantListItem[] the meeting registrants
     */
    public function getRegistrants($meetingId)
    {
        return $this->getFullList(
            "meetings/$meetingId/registrants",
            MeetingRegistrantList::class,
            'registrants'
        );
    }

    /**
     * Retrieves a past meeting's details.
     *
     * @param string $meetingUUID the meeting UUID
     *
     * @throws Exception describing the error (message and code)
     *
     * @return PastMeeting meeting
     */
    public function getPastMeetingDetails($meetingUUID)
    {
        return PastMeeting::fromJson($this->send('GET', 'past_meetings/'.$meetingUUID));
    }

    /**
     * Gets the recordings from a meeting.
     *
     * @param string $meetingUUID
     *
     * @throws Exception describing the error (message and code)
     *
     * @return RecordingMeeting the recordings for this meeting
     */
    public function getRecordings($meetingUUID)
    {
        return RecordingMeeting::fromJson(
            $this->send(
                'GET',
                'meetings/'.$this->doubleEncode($meetingUUID).'/recordings'
            )
        );
    }

    /**
     * Retrieves information on participants from a past meeting.
     *
     * @param string $meetingUUID the meeting instance UUID
     *
     * @throws Exception describing the error (message and code)
     *
     * @return ParticipantListItem[] participants
     */
    public function getParticipants($meetingUUID)
    {
        return $this->getFullList(
            'past_meetings/'.$this->doubleEncode($meetingUUID).'/participants',
            ParticipantList::class,
            'participants'
        );
    }

    /**
     * Retrieves a full list of items using one or more API calls to the Zoom server
     *
     * @param string $relativePath      @see self::send
     * @param string $listClassName     name of the API's list class, such as 'MeetingList'
     * @param string $arrayPropertyName name of the class property that contains the actual items, such as 'meetings'
     *
     * @throws Exception on API, JSON or other error
     *
     * @return array whose items are expected API class instances, such as MeetingListItems
     */
    private function getFullList($relativePath, $listClassName, $arrayPropertyName, $parameters = [])
    {
        $items = [];
        $pageCount = 1;
        $pageSize = 300;
        $totalRecords = 0;
        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $response = $listClassName::fromJson(
                $this->send(
                    'GET',
                    $relativePath,
                    array_merge(['page_size' => $pageSize, 'page_number' => $pageNumber], $parameters)
                )
            );
            $items = array_merge($items, $response->$arrayPropertyName);
            if (0 === $totalRecords) {
                $pageCount = $response->page_count;
                $pageSize = $response->page_size;
                $totalRecords = $response->total_records;
            }
        }
        if (count($items) !== $totalRecords) {
            error_log('Zoom announced '.$totalRecords.' records but returned '.count($items));
        }

        return $items;
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
