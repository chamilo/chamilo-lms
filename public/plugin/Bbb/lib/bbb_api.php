<?php
/*
Copyright 2010 Blindside Networks

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

Versions:
   1.0  --  Initial version written by DJP
                   (email: djp [a t ]  architectes DOT .org)
   1.1  --  Updated by Omar Shammas and Sebastian Schneider
                    (email : omar DOT shammas [a t ] g m ail DOT com)
                    (email : seb DOT sschneider [ a t ] g m ail DOT com)
   1.2  --  Updated by Omar Shammas
                    (email : omar DOT shammas [a t ] g m ail DOT com)
   1.3  --  Refactored by Peter Mentzer
 					(email : peter@petermentzerdesign.com)
					- This update will BREAK your external existing code if
					  you've used the previous versions <= 1.2 already so:
						-- update your external code to use new method names if needed
						-- update your external code to pass new parameters to methods
					- Working example of joinIfRunning.php now included
					- Added support for BBB 0.8b recordings
					- Now using Zend coding, naming and style conventions
					- Refactored methods to accept standardized parameters & match BBB API structure
					    -- See included samples for usage examples
*/

use BigBlueButton\BigBlueButton;
use BigBlueButton\Exceptions\BadResponseException;
use BigBlueButton\Parameters\Config\DocumentOptionsStore;
use BigBlueButton\Parameters\CreateMeetingParameters;
use BigBlueButton\Parameters\JoinMeetingParameters;
use BigBlueButton\Parameters\EndMeetingParameters;
use BigBlueButton\Parameters\IsMeetingRunningParameters;
use BigBlueButton\Parameters\GetMeetingInfoParameters;
use BigBlueButton\Parameters\GetRecordingsParameters;
use BigBlueButton\Parameters\PublishRecordingsParameters;
use BigBlueButton\Parameters\DeleteRecordingsParameters;
use BigBlueButton\Enum\Role;
use BigBlueButton\Responses\CreateMeetingResponse;
use BigBlueButton\Responses\IsMeetingRunningResponse;
use BigBlueButton\Responses\GetMeetingsResponse;
use BigBlueButton\Responses\GetMeetingInfoResponse;
use BigBlueButton\Responses\GetRecordingsResponse;
use BigBlueButton\Responses\PublishRecordingsResponse;
use BigBlueButton\Responses\DeleteRecordingsResponse;

/**
 * Adapter for Chamilo Video Conference (Bbb) to use the official BigBlueButton PHP client 2.x.
 */
class BigBlueButtonBN
{
    private BigBlueButton $client;

    public function __construct(string $baseUrl, string $secret, array $curlOpts = [])
    {
        // Initialize the official BBB client with server URL, secret and optional cURL options
        $this->client = new BigBlueButton($baseUrl, $secret, ['curl' => $curlOpts]);
    }

    /**
     * Build a create meeting URL.
     *
     * @param array $p  meetingId, meetingName, attendeePw, moderatorPw, and optional settings
     * @return string   URL to call the BBB create API
     */
    public function getCreateMeetingUrl(array $p): string
    {
        // Prepare parameters object
        $cp = new CreateMeetingParameters($p['meetingId'], $p['meetingName']);
        $cp->setAttendeePassword($p['attendeePw']);
        $cp->setModeratorPassword($p['moderatorPw']);

        // Optional settings
        if (!empty($p['welcomeMsg']))     $cp->setWelcomeMessage($p['welcomeMsg']);
        if (isset($p['dialNumber']))      $cp->setDialNumber($p['dialNumber']);
        if (isset($p['voiceBridge']))     $cp->setVoiceBridge((int)$p['voiceBridge']);
        if (isset($p['webVoice']))        $cp->setWebVoice($p['webVoice']);
        if (isset($p['logoutUrl']))       $cp->setLogoutUrl($p['logoutUrl']);
        if (isset($p['maxParticipants'])) $cp->setMaxParticipants((int)$p['maxParticipants']);
        if (isset($p['record']))          $cp->setRecord((bool)$p['record']);
        if (isset($p['duration']))        $cp->setDuration((int)$p['duration']);

        // Delegate to the official client
        return $this->client->getCreateMeetingUrl($cp);
    }

    /**
     * Create a meeting and return the XML response as an array.
     *
     * @param array $p
     * @return array
     */
    public function createMeetingWithXmlResponseArray(array $p): array
    {
        try {
            $cp = new CreateMeetingParameters($p['meetingId'], $p['meetingName']);
            $cp->setAttendeePassword($p['attendeePw']);
            $cp->setModeratorPassword($p['moderatorPw']);
            if (!empty($p['welcomeMsg']))  $cp->setWelcomeMessage($p['welcomeMsg']);
            if (isset($p['dialNumber']))   $cp->setDialNumber($p['dialNumber']);
            if (isset($p['voiceBridge']))  $cp->setVoiceBridge((int)$p['voiceBridge']);
            if (isset($p['webVoice']))     $cp->setWebVoice($p['webVoice']);
            if (isset($p['logoutUrl']))    $cp->setLogoutUrl($p['logoutUrl']);
            if (isset($p['maxParticipants'])) $cp->setMaxParticipants((int)$p['maxParticipants']);
            if (isset($p['record']))       $cp->setRecord((bool)$p['record']);
            if (isset($p['duration']))     $cp->setDuration((int)$p['duration']);

            if (!empty($p['documents']) && is_array($p['documents'])) {
                foreach ($p['documents'] as $doc) {
                    $opts = new DocumentOptionsStore();
                    $opts->addAttribute('removable', (bool)($doc['removable'] ?? true));
                    $cp->addPresentation(
                        $doc['filename'] ?? basename(parse_url($doc['url'], PHP_URL_PATH) ?: 'document'),
                        file_get_contents($doc['url']),
                        $doc['filename'] ?? 'document',
                        $opts
                    );
                }
            }

            $r   = $this->client->createMeeting($cp);
            $xml = $r->getRawXml();

            return [
                'returncode'           => (string)$xml->returncode,
                'message'              => (string)$xml->message,
                'messageKey'           => (string)$xml->messageKey,
                'meetingId'            => (string)$xml->meetingID,
                'attendeePw'           => (string)$xml->attendeePW,
                'moderatorPw'          => (string)$xml->moderatorPW,
                'hasBeenForciblyEnded' => (string)$xml->hasBeenForciblyEnded,
                'createTime'           => (string)$xml->createTime,
                'internalMeetingID'    => (string)$xml->internalMeetingID,
            ];
        } catch (BadResponseException $e) {
            $http = 0;
            if (preg_match('/HTTP code:\s*(\d+)/i', $e->getMessage(), $m)) {
                $http = (int)$m[1];
            }
            return [
                'returncode' => 'FAILED',
                'messageKey' => $http === 413 ? 'requestEntityTooLarge' : 'badResponse',
                'message'    => $http === 413
                    ? 'One or more presentations exceed the upload limit on the video-conference server.'
                    : $e->getMessage(),
                'httpCode'   => $http,
            ];
        } catch (\Throwable $e) {
            return [
                'returncode' => 'FAILED',
                'messageKey' => 'unexpectedError',
                'message'    => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate a join meeting URL.
     *
     * @param array $p  meetingId, username, password, moderatorPw, userID, webVoiceConf
     * @return string
     */
    public function getJoinMeetingURL(array $p): string
    {
        // 1) Determine role: if password matches moderatorPw, user is moderator
        $role = Role::VIEWER;
        if (
            !empty($p['password']) &&
            !empty($p['moderatorPw']) &&
            $p['password'] === $p['moderatorPw']
        ) {
            $role = Role::MODERATOR;
        }

        // 2) Construct parameters object (password passed in constructor)
        $jp = new JoinMeetingParameters(
            $p['meetingId'],
            $p['username'],
            $p['password']
        );

        // 3) Assign the role explicitly
        $jp->setRole($role);

        // 4) Optional parameters
        if (!empty($p['userID'])) {
            $jp->setUserId((string) $p['userID']);
        }
        if (!empty($p['webVoiceConf'])) {
            $jp->setWebVoiceConf($p['webVoiceConf']);
        }

        // 5) Delegate to the official client (returns full URL with protocol)
        return $this->client->getJoinMeetingUrl($jp);
    }

    /**
     * Generate an end meeting URL.
     *
     * @param array $p  meetingId, password
     * @return string
     */
    public function getEndMeetingURL(array $p): string
    {
        // Only moderators can end
        $ep = new EndMeetingParameters($p['meetingId'], Role::MODERATOR);
        $ep->setPassword($p['password']);
        return $this->client->getEndMeetingURL($ep);
    }

    /**
     * Call endMeeting and return XML response as array.
     *
     * @param array $p
     * @return array
     */
    public function endMeetingWithXmlResponseArray(array $p): array
    {
        $ep = new EndMeetingParameters($p['meetingId'], Role::MODERATOR);
        $ep->setPassword($p['password']);
        /** @var \BigBlueButton\Responses\EndMeetingResponse $r */
        $r = $this->client->endMeeting($ep);

        return [
            'returncode' => $r->getReturnCode(),
            'message'    => $r->getMessage(),
            'messageKey' => $r->getMessageKey(),
        ];
    }

    /**
     * Build URL to check if meeting is running.
     */
    public function getIsMeetingRunningUrl(string $meetingId): string
    {
        $p = new IsMeetingRunningParameters($meetingId);
        return $this->client->getIsMeetingRunningUrl($p);
    }

    /**
     * Check if meeting is running and return result as array.
     */
    public function isMeetingRunningWithXmlResponseArray(string $meetingId): array
    {
        $p = new IsMeetingRunningParameters($meetingId);
        /** @var IsMeetingRunningResponse $r */
        $r = $this->client->isMeetingRunning($p);

        return [
            'returncode' => $r->getReturnCode(),
            'running'    => $r->isRunning(),
        ];
    }

    /**
     * List all meetings (raw XML to array).
     */
    public function getMeetingsWithXmlResponseArray(): array
    {
        /** @var GetMeetingsResponse $r */
        $r   = $this->client->getMeetings();
        $xml = $r->getRawXml();

        $out = [
            'returncode' => (string) $xml->returncode,
            'messageKey' => (string) $xml->messageKey,
            'message'    => (string) $xml->message,
        ];

        if (isset($xml->meetings->meeting)) {
            foreach ($xml->meetings->meeting as $m) {
                $out[] = [
                    'meetingId'   => (string) $m->meetingID,
                    'meetingName' => (string) $m->meetingName,
                    'createTime'  => (string) $m->createTime,
                    'running'     => (string) $m->running,
                ];
            }
        }

        return $out;
    }

    /**
     * Get detailed meeting info as array.
     */
    public function getMeetingInfoWithXmlResponseArray(array $p): array
    {
        /** @var GetMeetingInfoResponse $r */
        $r   = $this->client->getMeetingInfo(
            new GetMeetingInfoParameters($p['meetingId'])
        );
        $xml = $r->getRawXml();

        $out = [
            'returncode'           => (string) $xml->returncode,
            'messageKey'           => (string) $xml->messageKey,
            'message'              => (string) $xml->message,
            'meetingName'          => (string) $xml->meetingName,
            'meetingId'            => (string) $xml->meetingID,
            'createTime'           => (string) $xml->createTime,
            'voiceBridge'          => (string) $xml->voiceBridge,
            'attendeePw'           => (string) $xml->attendeePW,
            'moderatorPw'          => (string) $xml->moderatorPW,
            'running'              => (string) $xml->running,
            'recording'            => (string) $xml->recording,
            'hasBeenForciblyEnded' => (string) $xml->hasBeenForciblyEnded,
            'startTime'            => (string) $xml->startTime,
            'endTime'              => (string) $xml->endTime,
            'participantCount'     => (string) $xml->participantCount,
            'maxUsers'             => (string) $xml->maxUsers,
            'moderatorCount'       => (string) $xml->moderatorCount,
            'internalMeetingID'    => (string) $xml->internalMeetingID,
        ];

        if (isset($xml->attendees->attendee)) {
            foreach ($xml->attendees->attendee as $a) {
                $out[] = [
                    'userId'   => (string) $a->userID,
                    'fullName' => (string) $a->fullName,
                    'role'     => (string) $a->role,
                ];
            }
        }

        return $out;
    }

    /**
     * Fetch recordings list as array.
     */
    public function getRecordingsWithXmlResponseArray(array $p): array
    {
        /** @var GetRecordingsResponse $r */
        $r   = $this->client->getRecordings(
            (new GetRecordingsParameters())->setMeetingId($p['meetingId'])
        );
        $xml = $r->getRawXml();

        $out = [
            'returncode' => (string) $xml->returncode,
            'messageKey' => (string) $xml->messageKey,
            'message'    => (string) $xml->message,
        ];

        if (isset($xml->recordings->recording)) {
            foreach ($xml->recordings->recording as $rec) {
                foreach ($rec->playback->format as $format) {
                    $formats[] = $format;
                }
                $out[] = [
                    'recordId'             => (string) $rec->recordID,
                    'meetingId'            => (string) $rec->meetingID,
                    'name'                 => (string) $rec->name,
                    'published'            => (string) $rec->published,
                    'startTime'            => (string) $rec->startTime,
                    'endTime'              => (string) $rec->endTime,
                    'playbackFormat'       => $formats,
                    'playbackFormatType'   => (string) $rec->playback->format->type,
                    'playbackFormatUrl'    => (string) $rec->playback->format->url,
                    'playbackFormatLength' => (string) $rec->playback->format->length,
                ];
            }
        }

        return $out;
    }

    /**
     * Publish or unpublish recordings.
     */
    public function publishRecordingsWithXmlResponseArray(array $p): array
    {
        /** @var PublishRecordingsResponse $r */
        $q = new PublishRecordingsParameters($p['recordId'], (bool) $p['publish']);
        $r = $this->client->publishRecordings($q);

        return [
            'returncode' => $r->getReturnCode(),
            'published'  => $r->isPublished(),
        ];
    }

    /**
     * Delete recordings by ID.
     */
    public function deleteRecordingsWithXmlResponseArray(array $p): array
    {
        /** @var DeleteRecordingsResponse $r */
        $q = new DeleteRecordingsParameters($p['recordId']);
        $r = $this->client->deleteRecordings($q);

        return [
            'returncode' => $r->getReturnCode(),
            'deleted'    => $r->isDeleted(),
        ];
    }
}
