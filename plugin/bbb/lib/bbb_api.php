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

/* _______________________________________________________________________*/

/* get the config values */
//require_once "config.php";

class BigBlueButtonBN {

	private $_securitySalt;
	private $_bbbServerBaseUrl;

	/* ___________ General Methods for the BigBlueButton Class __________ */

	function __construct() {
	/*
	Establish just our basic elements in the constructor:
	*/
		// BASE CONFIGS - set these for your BBB server in config.php and they will
		// simply flow in here via the constants:
		$this->_securitySalt 		= CONFIG_SECURITY_SALT;
		$this->_bbbServerBaseUrl 	= CONFIG_SERVER_BASE_URL;
	}

	private function _processXmlResponse($url){
	/*
	A private utility method used by other public methods to process XML responses.
	*/
		if (extension_loaded('curl')) {
			$ch = curl_init() or die ( curl_error($ch) );
			$timeout = 10;
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			$data = curl_exec( $ch );
			curl_close( $ch );

			if($data)
				return (new SimpleXMLElement($data));
			else
				return false;
		}
		return (simplexml_load_file($url));
	}

	private function _requiredParam($param) {
		/* Process required params and throw errors if we don't get values */
		if ((isset($param)) && ($param != '')) {
			return $param;
		}
		elseif (!isset($param)) {
			throw new Exception('Missing parameter.');
		}
		else {
			throw new Exception(''.$param.' is required.');
		}
	}

	private function _optionalParam($param) {
		/* Pass most optional params through as set value, or set to '' */
		/* Don't know if we'll use this one, but let's build it in case. */
		if ((isset($param)) && ($param != '')) {
			return $param;
		}
		else {
			$param = '';
			return $param;
		}
	}

	/* __________________ BBB ADMINISTRATION METHODS _________________ */
	/* The methods in the following section support the following categories of the BBB API:
	-- create
	-- join
	-- end
	*/

	public function getCreateMeetingUrl($creationParams) {
		/*
		USAGE:
		(see $creationParams array in createMeetingArray method.)
		*/
		$this->_meetingId = $this->_requiredParam($creationParams['meetingId']);
		$this->_meetingName = $this->_requiredParam($creationParams['meetingName']);
		// Set up the basic creation URL:
		$creationUrl = $this->_bbbServerBaseUrl."api/create?";
		// Add params:
		$params =
		'name='.urlencode($this->_meetingName).
		'&meetingID='.urlencode($this->_meetingId).
		'&attendeePW='.urlencode($creationParams['attendeePw']).
		'&moderatorPW='.urlencode($creationParams['moderatorPw']).
		'&dialNumber='.urlencode($creationParams['dialNumber']).
		'&voiceBridge='.urlencode($creationParams['voiceBridge']).
		'&webVoice='.urlencode($creationParams['webVoice']).
		'&logoutURL='.urlencode($creationParams['logoutUrl']).
		'&maxParticipants='.urlencode($creationParams['maxParticipants']).
		'&record='.urlencode($creationParams['record']).
		'&duration='.urlencode($creationParams['duration']);
		//'&meta_category='.urlencode($creationParams['meta_category']);
		$welcomeMessage = $creationParams['welcomeMsg'];
		if(trim($welcomeMessage))
			$params .= '&welcome='.urlencode($welcomeMessage);
		// Return the complete URL:
		return ( $creationUrl.$params.'&checksum='.sha1("create".$params.$this->_securitySalt) );
	}

	public function createMeetingWithXmlResponseArray($creationParams) {
		/*
		USAGE:
		$creationParams = array(
			'name' => 'Meeting Name',	-- A name for the meeting (or username)
			'meetingId' => '1234',		-- A unique id for the meeting
			'attendeePw' => 'ap',  		-- Set to 'ap' and use 'ap' to join = no user pass required.
			'moderatorPw' => 'mp', 		-- Set to 'mp' and use 'mp' to join = no user pass required.
			'welcomeMsg' => '', 		-- ''= use default. Change to customize.
			'dialNumber' => '', 		-- The main number to call into. Optional.
			'voiceBridge' => '', 		-- 5 digits PIN to join voice. Required.
			'webVoice' => '', 			-- Alphanumeric to join voice. Optional.
			'logoutUrl' => '', 			-- Default in bigbluebutton.properties. Optional.
			'maxParticipants' => '-1', 	-- Optional. -1 = unlimitted. Not supported in BBB. [number]
			'record' => 'false', 		-- New. 'true' will tell BBB to record the meeting.
			'duration' => '0', 			-- Default = 0 which means no set duration in minutes. [number]
			'meta_category' => '', 		-- Use to pass additional info to BBB server. See API docs to enable.
		);
		*/
		$xml = $this->_processXmlResponse($this->getCreateMeetingURL($creationParams));

        if ($xml) {
			if($xml->meetingID)
				return array(
					'returncode' => $xml->returncode->__toString(),
					'message' => $xml->message->__toString(),
					'messageKey' => $xml->messageKey->__toString(),
					'meetingId' => $xml->meetingID->__toString(),
					'attendeePw' => $xml->attendeePW->__toString(),
					'moderatorPw' => $xml->moderatorPW->__toString(),
					'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded->__toString(),
					'createTime' => $xml->createTime->__toString()
					);
			else
				return array(
					'returncode' => $xml->returncode->__toString(),
					'message' => $xml->message->__toString(),
					'messageKey' => $xml->messageKey->__toString()
					);
		}
		else {
			return null;
		}
	}

	public function getJoinMeetingURL($joinParams) {
		/*
		NOTE: At this point, we don't use a corresponding joinMeetingWithXmlResponse here because the API
		doesn't respond on success, but you can still code that method if you need it. Or, you can take the URL
		that's returned from this method and simply send your users off to that URL in your code.
		USAGE:
		$joinParams = array(
			'meetingId' => '1234',		-- REQUIRED - A unique id for the meeting
			'username' => 'Jane Doe',	-- REQUIRED - The name that will display for the user in the meeting
			'password' => 'ap',			-- REQUIRED - The attendee or moderator password, depending on what's passed here
			'createTime' => '',			-- OPTIONAL - string. Leave blank ('') unless you set this correctly.
			'userID' => '',				-- OPTIONAL - string
			'webVoiceConf' => ''		-- OPTIONAL - string
		);
		*/
		$this->_meetingId = $this->_requiredParam($joinParams['meetingId']);
		$this->_username = $this->_requiredParam($joinParams['username']);
		$this->_password = $this->_requiredParam($joinParams['password']);
		// Establish the basic join URL:
		$joinUrl = $this->_bbbServerBaseUrl."api/join?";
		// Add parameters to the URL:
		$params =
		'meetingID='.urlencode($this->_meetingId).
		'&fullName='.urlencode($this->_username).
		'&password='.urlencode($this->_password).
		'&userID='.urlencode($joinParams['userID']).
		'&webVoiceConf='.urlencode($joinParams['webVoiceConf']);
		// Only use createTime if we really want to use it. If it's '', then don't pass it:
		if (((isset($joinParams['createTime'])) && ($joinParams['createTime'] != ''))) {
			$params .= '&createTime='.urlencode($joinParams['createTime']);
		}

		if (isset($joinParams['interface']) && (int) $joinParams['interface'] === BBBPlugin::INTERFACE_HTML5) {
			$bbbHost = api_remove_trailing_slash(CONFIG_SERVER_URL_WITH_PROTOCOL);
            $params .= '&redirectClient=true&clientURL='.$bbbHost.'/html5client/join';
        }

		// Return the URL:
		$url = $joinUrl.$params.'&checksum='.sha1('join'.$params.$this->_securitySalt);

		return $url;
	}

	public function getEndMeetingURL($endParams) {
		/* USAGE:
		$endParams = array (
			'meetingId' => '1234',		-- REQUIRED - The unique id for the meeting
			'password' => 'mp'			-- REQUIRED - The moderator password for the meeting
		);
		*/
		$this->_meetingId = $this->_requiredParam($endParams['meetingId']);
		$this->_password = $this->_requiredParam($endParams['password']);
		$endUrl = $this->_bbbServerBaseUrl."api/end?";
		$params =
		'meetingID='.urlencode($this->_meetingId).
		'&password='.urlencode($this->_password);
		return ($endUrl.$params.'&checksum='.sha1("end".$params.$this->_securitySalt));
	}

	public function endMeetingWithXmlResponseArray($endParams) {
		/* USAGE:
		$endParams = array (
			'meetingId' => '1234',		-- REQUIRED - The unique id for the meeting
			'password' => 'mp'			-- REQUIRED - The moderator password for the meeting
		);
		*/
		$xml = $this->_processXmlResponse($this->getEndMeetingURL($endParams));
		if ($xml) {
			return array(
				'returncode' => $xml->returncode->__toString(),
				'message' => $xml->message->__toString(),
				'messageKey' => $xml->messageKey->__toString()
				);
		}
		else {
			return null;
		}

	}

	/* __________________ BBB MONITORING METHODS _________________ */
	/* The methods in the following section support the following categories of the BBB API:
	-- isMeetingRunning
	-- getMeetings
	-- getMeetingInfo
	*/

	public function getIsMeetingRunningUrl($meetingId) {
		/* USAGE:
		$meetingId = '1234'		-- REQUIRED - The unique id for the meeting
		*/
		$this->_meetingId = $this->_requiredParam($meetingId);
		$runningUrl = $this->_bbbServerBaseUrl."api/isMeetingRunning?";
		$params =
		'meetingID='.urlencode($this->_meetingId);
		return ($runningUrl.$params.'&checksum='.sha1("isMeetingRunning".$params.$this->_securitySalt));
	}

	public function isMeetingRunningWithXmlResponseArray($meetingId) {
		/* USAGE:
		$meetingId = '1234'		-- REQUIRED - The unique id for the meeting
		*/
		$xml = $this->_processXmlResponse($this->getIsMeetingRunningUrl($meetingId));
		if($xml) {
			return array(
				'returncode' => $xml->returncode->__toString(),
				'running' => $xml->running->__toString() 	// -- Returns true/false.
			);
		}
		else {
			return null;
		}

	}

	public function getGetMeetingsUrl() {
		/* Simply formulate the getMeetings URL
		We do this in a separate function so we have the option to just get this
		URL and print it if we want for some reason.
		*/
		$getMeetingsUrl = $this->_bbbServerBaseUrl."api/getMeetings?checksum=".sha1("getMeetings".$this->_securitySalt);
		return $getMeetingsUrl;
	}

	public function getMeetingsWithXmlResponseArray() {
		/* USAGE:
		We don't need to pass any parameters with this one, so we just send the query URL off to BBB
		and then handle the results that we get in the XML response.
		*/
		$xml = $this->_processXmlResponse($this->getGetMeetingsUrl());
		if($xml) {
			// If we don't get a success code, stop processing and return just the returncode:
			if ($xml->returncode != 'SUCCESS') {
				$result = array(
					'returncode' => $xml->returncode->__toString()
				);
				return $result;
			}
			elseif ($xml->messageKey == 'noMeetings') {
				/* No meetings on server, so return just this info: */
				$result = array(
					'returncode' => $xml->returncode->__toString(),
					'messageKey' => $xml->messageKey->__toString(),
					'message' => $xml->message->__toString()
				);
				return $result;
			}
			else {
				// In this case, we have success and meetings. First return general response:
				$result = array(
					'returncode' => $xml->returncode->__toString(),
					'messageKey' => $xml->messageKey->__toString(),
					'message' => $xml->message->__toString()
				);
				// Then interate through meeting results and return them as part of the array:
				foreach ($xml->meetings->meeting as $m) {
					$result[] = array(
						'meetingId' => $m->meetingID->__toString(),
						'meetingName' => $m->meetingName->__toString(),
						'createTime' => $m->createTime->__toString(),
						'attendeePw' => $m->attendeePW->__toString(),
						'moderatorPw' => $m->moderatorPW->__toString(),
						'hasBeenForciblyEnded' => $m->hasBeenForciblyEnded->__toString(),
						'running' => $m->running->__toString()
					);
				}
				return $result;
			}
		}
		else {
			return null;
		}

	}

	public function getMeetingInfoUrl($infoParams) {
		/* USAGE:
		$infoParams = array(
			'meetingId' => '1234',		-- REQUIRED - The unique id for the meeting
			'password' => 'mp'			-- REQUIRED - The moderator password for the meeting
		);
		*/
		$this->_meetingId = $this->_requiredParam($infoParams['meetingId']);
		$this->_password = $this->_requiredParam($infoParams['password']);
		$infoUrl = $this->_bbbServerBaseUrl."api/getMeetingInfo?";
		$params =
		'meetingID='.urlencode($this->_meetingId).
		'&password='.urlencode($this->_password);
		return ($infoUrl.$params.'&checksum='.sha1("getMeetingInfo".$params.$this->_securitySalt));
	}

	public function getMeetingInfoWithXmlResponseArray($infoParams) {
		/* USAGE:
		$infoParams = array(
			'meetingId' => '1234',		-- REQUIRED - The unique id for the meeting
			'password' => 'mp'			-- REQUIRED - The moderator password for the meeting
		);
		*/
		$xml = $this->_processXmlResponse($this->getMeetingInfoUrl($infoParams));
		if($xml) {
			// If we don't get a success code or messageKey, find out why:
			if (($xml->returncode != 'SUCCESS') || ($xml->messageKey == null)) {
				$result = array(
					'returncode' => $xml->returncode->__toString(),
					'messageKey' => $xml->messageKey->__toString(),
					'message' => $xml->message->__toString()
				);
				return $result;
			}
			else {
				// In this case, we have success and meeting info:
				$result = array(
					'returncode' => $xml->returncode->__toString(),
					'meetingName' => $xml->meetingName->__toString(),
					'meetingId' => $xml->meetingID->__toString(),
					'createTime' => $xml->createTime->__toString(),
					'voiceBridge' => $xml->voiceBridge->__toString(),
					'attendeePw' => $xml->attendeePW->__toString(),
					'moderatorPw' => $xml->moderatorPW->__toString(),
					'running' => $xml->running->__toString(),
					'recording' => $xml->recording->__toString(),
					'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded->__toString(),
					'startTime' => $xml->startTime->__toString(),
					'endTime' => $xml->endTime->__toString(),
					'participantCount' => $xml->participantCount->__toString(),
					'maxUsers' => $xml->maxUsers->__toString(),
					'moderatorCount' => $xml->moderatorCount->__toString(),
				);
				// Then interate through attendee results and return them as part of the array:
				foreach ($xml->attendees->attendee as $a) {
					$result[] = array(
						'userId' => $a->userID->__toString(),
						'fullName' => $a->fullName->__toString(),
						'role' => $a->role->__toString()
					);
				}
				return $result;
			}
		}
		else {
			return null;
		}

	}

	/* __________________ BBB RECORDING METHODS _________________ */
	/* The methods in the following section support the following categories of the BBB API:
	-- getRecordings
	-- publishRecordings
	-- deleteRecordings
	*/

	public function getRecordingsUrl($recordingParams) {
		/* USAGE:
		$recordingParams = array(
			'meetingId' => '1234',		-- OPTIONAL - comma separate if multiple ids
		);
		*/
		$recordingsUrl = $this->_bbbServerBaseUrl."api/getRecordings?";
		$params = 'meetingID='.urlencode($recordingParams['meetingId']);
		return ($recordingsUrl.$params.'&checksum='.sha1("getRecordings".$params.$this->_securitySalt));

	}

	public function getRecordingsWithXmlResponseArray($recordingParams) {
		/* USAGE:
		$recordingParams = array(
			'meetingId' => '1234',		-- OPTIONAL - comma separate if multiple ids
		);
		NOTE: 'duration' DOES work when creating a meeting, so if you set duration
		when creating a meeting, it will kick users out after the duration. Should
		probably be required in user code when 'recording' is set to true.
		*/
		$xml = $this->_processXmlResponse($this->getRecordingsUrl($recordingParams));
		if($xml) {
			// If we don't get a success code or messageKey, find out why:
			if (($xml->returncode != 'SUCCESS') || ($xml->messageKey == null)) {
				$result = array(
					'returncode' => $xml->returncode->__toString(),
					'messageKey' => $xml->messageKey->__toString(),
					'message' => $xml->message->__toString()
				);
				return $result;
			}
			else {
				// In this case, we have success and recording info:
				$result = array(
					'returncode' => $xml->returncode->__toString(),
					'messageKey' => $xml->messageKey->__toString(),
					'message' => $xml->message->__toString()
				);

				foreach ($xml->recordings->recording as $r) {
					$result[] = array(
						'recordId' => $r->recordID->__toString(),
						'meetingId' => $r->meetingID->__toString(),
						'name' => $r->name->__toString(),
						'published' => $r->published->__toString(),
						'startTime' => $r->startTime->__toString(),
						'endTime' => $r->endTime->__toString(),
						'playbackFormatType' => $r->playback->format->type->__toString(),
						'playbackFormatUrl' => $r->playback->format->url->__toString(),
						'playbackFormatLength' => $r->playback->format->length->__toString(),
						'metadataTitle' => $r->metadata->title->__toString(),
						'metadataSubject' => $r->metadata->subject->__toString(),
						'metadataDescription' => $r->metadata->description->__toString(),
						'metadataCreator' => $r->metadata->creator->__toString(),
						'metadataContributor' => $r->metadata->contributor->__toString(),
						'metadataLanguage' => $r->metadata->language->__toString(),
						// Add more here as needed for your app depending on your
						// use of metadata when creating recordings.
						);
					}
				return $result;
			}
		}
		else {
			return null;
		}
	}

	/**
	 * @param $array recordingParams
	 *
	 * @return array|null
	 */
	public function getRecordings($recordingParams)
	{
		/* USAGE:
		$recordingParams = array(
			'meetingId' => '1234',		-- OPTIONAL - comma separate if multiple ids
		);
		NOTE: 'duration' DOES work when creating a meeting, so if you set duration
		when creating a meeting, it will kick users out after the duration. Should
		probably be required in user code when 'recording' is set to true.
		*/
		$xml = $this->_processXmlResponse($this->getRecordingsUrl($recordingParams));
		if($xml) {
			// If we don't get a success code or messageKey, find out why:
			if (($xml->returncode != 'SUCCESS') || ($xml->messageKey == null)) {
				$result = array(
					'returncode' => $xml->returncode->__toString(),
					'messageKey' => $xml->messageKey->__toString(),
					'message' => $xml->message->__toString()
				);
				return $result;
			}
			else {
				// In this case, we have success and recording info:
				$result = array(
					'returncode' => $xml->returncode->__toString(),
					'messageKey' => $xml->messageKey->__toString(),
					'message' => $xml->message->__toString()
				);
				$result['records'] = [];
				if (!empty($xml->recordings->recording)) {
					foreach ($xml->recordings->recording as $r) {
						$result['records'][] = array(
							'recordId' => $r->recordID->__toString(),
							'meetingId' => $r->meetingID->__toString(),
							'name' => $r->name->__toString(),
							'published' => $r->published->__toString(),
							'startTime' => $r->startTime->__toString(),
							'endTime' => $r->endTime->__toString(),
							'playbackFormatType' => $r->playback->format->type->__toString(),
							'playbackFormatUrl' => $r->playback->format->url->__toString(),
							'playbackFormatLength' => $r->playback->format->length->__toString(),
							'metadataTitle' => $r->metadata->title->__toString(),
							'metadataSubject' => $r->metadata->subject->__toString(),
							'metadataDescription' => $r->metadata->description->__toString(),
							'metadataCreator' => $r->metadata->creator->__toString(),
							'metadataContributor' => $r->metadata->contributor->__toString(),
							'metadataLanguage' => $r->metadata->language->__toString(),
						);
					}
				}

				return $result;
			}
		}

		return null;
	}

	public function getPublishRecordingsUrl($recordingParams) {
		/* USAGE:
		$recordingParams = array(
			'recordId' => '1234',		-- REQUIRED - comma separate if multiple ids
			'publish' => 'true',		-- REQUIRED - boolean: true/false
		);
		*/
		$recordingsUrl = $this->_bbbServerBaseUrl."api/publishRecordings?";
		$params =
		'recordID='.urlencode($recordingParams['recordId']).
		'&publish='.urlencode($recordingParams['publish']);
		return ($recordingsUrl.$params.'&checksum='.sha1("publishRecordings".$params.$this->_securitySalt));

	}

	public function publishRecordingsWithXmlResponseArray($recordingParams) {
		/* USAGE:
		$recordingParams = array(
			'recordId' => '1234',		-- REQUIRED - comma separate if multiple ids
			'publish' => 'true',		-- REQUIRED - boolean: true/false
		);
		*/
		$xml = $this->_processXmlResponse($this->getPublishRecordingsUrl($recordingParams));
		if($xml) {
			return array(
				'returncode' => $xml->returncode->__toString(),
				'published' => $xml->published->__toString() 	// -- Returns true/false.
			);
		}
		else {
			return null;
		}


	}

	public function getDeleteRecordingsUrl($recordingParams) {
		/* USAGE:
		$recordingParams = array(
			'recordId' => '1234',		-- REQUIRED - comma separate if multiple ids
		);
		*/
		$recordingsUrl = $this->_bbbServerBaseUrl."api/deleteRecordings?";
		$params =
		'recordID='.urlencode($recordingParams['recordId']);
		return ($recordingsUrl.$params.'&checksum='.sha1("deleteRecordings".$params.$this->_securitySalt));
	}

	public function deleteRecordingsWithXmlResponseArray($recordingParams) {
		/* USAGE:
		$recordingParams = array(
			'recordId' => '1234',		-- REQUIRED - comma separate if multiple ids
		);
		*/

		$xml = $this->_processXmlResponse($this->getDeleteRecordingsUrl($recordingParams));
		if($xml) {
			return array(
				'returncode' => $xml->returncode->__toString(),
				'deleted' => $xml->deleted->__toString() 	// -- Returns true/false.
			);
		}
		else {
			return null;
		}

	}



} // END OF BIGBLUEBUTTON CLASS

?>
