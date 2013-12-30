<?php
/**
 * OpenMeetings API
 * @package chamilo.plugin.openmeetings
 */
/**
 * Class OpenMeetingsAPI
 */
class OpenMeetingsAPI
{
    private $_securitySalt;
    private $_serverBaseUrl;

    /**
     * Constructor
     */
    function __construct()
    {
            $this->_securitySalt  = CONFIG_SECURITY_SALT;
            $this->_serverBaseUrl  = CONFIG_OPENMEETINGS_SERVER_BASE_URL;
    }

    /**
     * Gets info about a given meeting
     * @param $infoParams
     * @return array|null
     */
    function getMeetingInfoArray($infoParams)
    {
            
        $xml = $this->_processXmlResponse($this->getMeetingInfoUrl($infoParams));
        if ($xml) {
            // If we don't get a success code or messageKey, find out why:
            if (($xml->returncode != 'SUCCESS') || ($xml->messageKey == null)) {
                $result = array(
                    'returncode' => $xml->returncode,
                    'messageKey' => $xml->messageKey,
                    'message' => $xml->message
                );
                return $result;
            } else {
                // In this case, we have success and meeting info:
                $result = array(
                    'returncode' => $xml->returncode,
                    'meetingName' => $xml->meetingName,
                    'meetingId' => $xml->meetingID,
                    'createTime' => $xml->createTime,
                    'voiceBridge' => $xml->voiceBridge,
                    'attendeePw' => $xml->attendeePW,
                    'moderatorPw' => $xml->moderatorPW,
                    'running' => $xml->running,
                    'recording' => $xml->recording,
                    'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded,
                    'startTime' => $xml->startTime,
                    'endTime' => $xml->endTime,
                    'participantCount' => $xml->participantCount,
                    'maxUsers' => $xml->maxUsers,
                    'moderatorCount' => $xml->moderatorCount,
                );
                // Then interate through attendee results and return them as part of the array:
                foreach ($xml->attendees->attendee as $a) {
                    $result[] = array(
                        'userId' => $a->userID,
                        'fullName' => $a->fullName,
                        'role' => $a->role
                    );
                }
                return $result;
            }
        } else {
            return null;
        }

    }
}
