<?php

class OpenMeetings
{
    private $_securitySalt;
    private $_omServerBaseUrl;
    function __construct()
    {
            $this->_securitySalt  = CONFIG_SECURITY_SALT;
            $this->_omServerBaseUrl  = CONFIG_SERVER_BASE_URL;
    }
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
