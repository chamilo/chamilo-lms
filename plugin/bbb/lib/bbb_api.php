<?php
/*
Copyright 2010-2011 Blindside Networks

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
                    (email : omar [at] b l i n ds i de n  e t w o r ks [dt] com)
                    (email : seb DOT sschneider [ a t ] g m ail DOT com)
   1.2  --  Updated by Omar Shammas
                    (email : omar [at] b l i n ds i de n  e t w o r ks [dt] com)
   1.3  --  Reviewed and extended by Jesus Federico
                    (email : jesus [at] b l i n ds i de n  e t w o r ks [dt] com)
   0.8_1.4.10  --  Extended by Jesus Federico to support BigBlueButton 0.8 version
                    (email : jesus [at] b l i n ds i de n  e t w o r ks [dt] com)
*/


/*
@param
$userName = userName AND meetingID (string)
$welcomeString = welcome message (string)

$modPW = moderator password (string)
$vPW = viewer password (string)
$voiceBridge = voice bridge (integer)
$logout = logout url (url)
*/
// create a meeting and return the url to join as moderator


// TODO::
// create some set methods
class BigBlueButtonBN {

	var $userName = array();
	var $meetingID; // the meeting id

	var $welcomeString;
	// the next 2 fields are maybe not needed?!?
	var $modPW; // the moderator password
	var $attPW; // the attendee pw

	var $securitySalt; // the security salt; gets encrypted with sha1
	var $URL; // the url the bigbluebuttonbn server is installed
	var $sessionURL; // the url for the administrator to join the sessoin
	var $userURL;

	var $conferenceIsRunning = false;

	// this constructor is used to create a BigBlueButton Object
	// use this object to create servers
	// Use is either 0 arguments or all 7 arguments
	public function __construct() {
		$numargs = func_num_args();

		if( $numargs == 0 ) {
		#	echo "Constructor created";
		}
		// pass the information to the class variables
		else if( $numargs >= 6 ) {
			$this->userName = func_get_arg(0);
			$this->meetingID = func_get_arg(1);
			$this->welcomeString = func_get_arg(2);
			$this->modPW = func_get_arg(3);
			$this->attPW = func_get_arg(4);
			$this->securitySalt = func_get_arg(5);
			$this->URL = func_get_arg(6);


			$arg_list = func_get_args();
		}// end else if
	}

	//------------------------------------------------GET URLs-------------------------------------------------
	/**
	*This method returns the url to join the specified meeting.
	*
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebuttonbn server
	*@param username -- the display name to be used when the user joins the meeting
	*@param PW -- the attendee or moderator password of the meeting
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*@param URL -- the url of the bigbluebuttonbn server
	*
	*@return The url to join the meeting
	*/
	public static function joinURL( $meetingID, $userName, $PW, $SALT, $URL ) {
		$url_join = $URL."api/join?";
		$params = 'meetingID='.urlencode($meetingID).'&fullName='.urlencode($userName).'&password='.urlencode($PW);
		return ($url_join.$params.'&checksum='.sha1("join".$params.$SALT) );
	}


	/**
	*This method returns the url to join the specified meeting.
	*
	*@param name -- a name fot the meeting
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebuttonbn server
	*@param attendeePW -- the attendee of the meeting
	*@param moderatorPW -- the moderator of the meeting
	*@param welcome -- the welcome message that gets displayed on the chat window
	*@param logoutURL -- the URL that the bbb client will go to after users logouut
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*@param URL -- the url of the bigbluebuttonbn server
	*@param record -- the flag which indicate if the meetings will be recorded or not record=true|false, default false
	*@param duration -- this value indicate the duration of a meeting to be recorded. Duration is represented in munutes
	*
	*@return The url to join the meeting
	*/
	public static function createMeetingURL($name, $meetingID, $attendeePW, $moderatorPW, $welcome, $logoutURL, $SALT, $URL, $record = 'false', $duration=0, $voiceBridge=0, $metadata = array() ) {
        $url_create = $URL."api/create?";
        if ( $voiceBridge == 0)
            $voiceBridge = 70000 + rand(0, 9999);

        $meta = '';
        while ($data = current($metadata)) {
            $meta = $meta.'&'.key($metadata).'='.urlencode($data);
            next($metadata);
        }


        $params = 'name='.urlencode($name).'&meetingID='.urlencode($meetingID).'&attendeePW='.urlencode($attendeePW).'&moderatorPW='.urlencode($moderatorPW).'&voiceBridge='.$voiceBridge.'&logoutURL='.urlencode($logoutURL).'&record='.$record.$meta;

        $duration = intval($duration);
        if( $duration > 0 )
            $params .= '&duration='.$duration;

        if( trim( $welcome ) )
            $params .= '&welcome='.urlencode($welcome);

        return ( $url_create.$params.'&checksum='.sha1("create".$params.$SALT) );
    }


	/**
	*This method returns the url to check if the specified meeting is running.
	*
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebuttonbn server
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*@param URL -- the url of the bigbluebuttonbn server
	*
	*@return The url to check if the specified meeting is running.
	*/
	public static function isMeetingRunningURL( $meetingID, $URL, $SALT ) {
		$base_url = $URL."api/isMeetingRunning?";
		$params = 'meetingID='.urlencode($meetingID);
		return ($base_url.$params.'&checksum='.sha1("isMeetingRunning".$params.$SALT) );
	}

	/**
	*This method returns the url to getMeetingInfo of the specified meeting.
	*
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebuttonbn server
	*@param modPW -- the moderator password of the meeting
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*@param URL -- the url of the bigbluebuttonbn server
	*
	*@return The url to check if the specified meeting is running.
	*/
	public static function getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT ) {
		$base_url = $URL."api/getMeetingInfo?";
		$params = 'meetingID='.urlencode($meetingID).'&password='.urlencode($modPW);
		return ( $base_url.$params.'&checksum='.sha1("getMeetingInfo".$params.$SALT));
	}

	/**
	*This method returns the url for listing all meetings in the bigbluebuttonbn server.
	*
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*@param URL -- the url of the bigbluebuttonbn server
	*
	*@return The url of getMeetings.
	*/
	public static function getMeetingsURL($URL, $SALT) {
		$base_url = $URL."api/getMeetings?";
		$params = '';
		return ( $base_url.$params.'&checksum='.sha1("getMeetings".$params.$SALT));
	}

	/**
	*This method returns the url to end the specified meeting.
	*
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebuttonbn server
	*@param modPW -- the moderator password of the meeting
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*@param URL -- the url of the bigbluebuttonbn server
	*
	*@return The url to end the specified meeting.
	*/
	public static function endMeetingURL( $meetingID, $modPW, $URL, $SALT ) {
		$base_url = $URL."api/end?";
		$params = 'meetingID='.urlencode($meetingID).'&password='.urlencode($modPW);
		return ( $base_url.$params.'&checksum='.sha1("end".$params.$SALT) );
	}

	//-----------------------------------------------CREATE----------------------------------------------------
	/**
	*This method creates a meeting and returnS the join url for moderators.
	*
	*@param username
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebuttonbn server
	*@param welcomeString -- the welcome message to be displayed when a user logs in to the meeting
	*@param mPW -- the moderator password of the meeting
	*@param aPW -- the attendee password of the meeting
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*@param URL -- the url of the bigbluebuttonbn server
	*@param logoutURL -- the url the user should be redirected to when they logout of bigbluebuttonbn
	*@param record -- the flag which indicate if the meetings will be recorded or not record=true|false, default false
	*
	*@return The joinURL if successful or an error message if unsuccessful
	*/
	public static function createMeetingAndGetJoinURL( $username, $meeting_name, $meetingID, $welcomeString, $mPW, $aPW, $SALT, $URL, $logoutURL, $record = 'false', $duration=0, $voiceBridge=0, $metadata = array()) {

		$xml = BigBlueButtonBN::_wrap_simplexml_load_file( BigBlueButtonBN::createMeetingURL($meeting_name, $meetingID, $aPW, $mPW, $welcomeString, $logoutURL, $SALT, $URL, $record, $duration, $voiceBridge, $metadata ) );

		if( $xml && $xml->returncode == 'SUCCESS' ) {
			return ( BigBlueButtonBN::joinURL( $meetingID, $username, $mPW, $SALT, $URL ) );
		}
		else if( $xml ) {
			return ( $xml->messageKey.' : '.$xml->message );
		}
		else {
			return ('Unable to fetch URL '.$url_create.$params.'&checksum='.sha1("create".$params.$SALT) );
		}
	}

	/**
	*This method creates a meeting and return an array of the xml packet
	*
	*@param username
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebuttonbn server
	*@param welcomeString -- the welcome message to be displayed when a user logs in to the meeting
	*@param mPW -- the moderator password of the meeting
	*@param aPW -- the attendee password of the meeting
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*@param URL -- the url of the bigbluebuttonbn server
	*@param logoutURL -- the url the user should be redirected to when they logout of bigbluebuttonbn
	*@param record -- the flag which indicate if the meetings will be recorded or not record=true|false, default false
	*
	*@return
	*	- Null if unable to reach the bigbluebuttonbn server
	*	- If failed it returns an array containing a returncode, messageKey, message.
	*	- If success it returns an array containing a returncode, messageKey, message, meetingID, attendeePW, moderatorPW, hasBeenForciblyEnded.
	*/
	public static function createMeetingArray( $username, $meetingID, $welcomeString, $mPW, $aPW, $SALT, $URL, $logoutURL, $record='false', $duration=0, $voiceBridge=0, $metadata = array() ) {

		$xml = BigBlueButtonBN::_wrap_simplexml_load_file( BigBlueButtonBN::createMeetingURL($username, $meetingID, $aPW, $mPW, $welcomeString, $logoutURL, $SALT, $URL, $record, $duration, $voiceBridge, $metadata ) );

		if( $xml ) {
			if($xml->meetingID) return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey, 'meetingID' => $xml->meetingID, 'attendeePW' => $xml->attendeePW, 'moderatorPW' => $xml->moderatorPW, 'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded );
			else return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey );
		}
		else {
			return null;
		}
	}

	//-------------------------------------------getMeetingInfo---------------------------------------------------
	/**
	*This method calls the getMeetingInfo on the bigbluebuttonbn server and returns an xml packet.
	*
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebuttonbn server
	*@param modPW -- the moderator password of the meeting
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*@param URL -- the url of the bigbluebuttonbn server
	*
	*@return An xml packet.
	*	If failed it returns an xml packet containing a returncode, messagekey, and message.
	*	If success it returnsan xml packet containing a returncode,
	*/
	public static function getMeetingInfo( $meetingID, $modPW, $URL, $SALT ) {
		$xml = BigBlueButtonBN::_wrap_simplexml_load_file( BigBlueButtonBN::getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT ) );
		if($xml){
			return ( str_replace('</response>', '', str_replace("<?xml version=\"1.0\"?>\n<response>", '', $xml->asXML())));
		}
		return false;
	}

	/**
	*This method calls the getMeetingInfo on the bigbluebuttonbn server and returns an array.
	*
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebuttonbn server
	*@param modPW -- the moderator password of the meeting
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*@param URL -- the url of the bigbluebuttonbn server
	*
	*@return An Array.
	*	- Null if unable to reach the bigbluebuttonbn server
	*	- If failed it returns an array containing a returncode, messagekey, message.
	*	- If success it returns an array containing a meetingID, moderatorPW, attendeePW, hasBeenForciblyEnded, running, startTime, endTime,
		  participantCount, moderatorCount, attendees.
	*/
	public static function getMeetingInfoArray( $meetingID, $modPW, $URL, $SALT ) {
		$xml = BigBlueButtonBN::_wrap_simplexml_load_file( BigBlueButtonBN::getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT ) );

		if( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey == null){//The meetings were returned
			return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey );
		}
		else if($xml && $xml->returncode == 'SUCCESS'){ //If there were meetings already created
			return array( 'meetingID' => $xml->meetingID, 'moderatorPW' => $xml->moderatorPW, 'attendeePW' => $xml->attendeePW, 'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded, 'running' => $xml->running, 'recording' => $xml->recording, 'startTime' => $xml->startTime, 'endTime' => $xml->endTime, 'participantCount' => $xml->participantCount, 'moderatorCount' => $xml->moderatorCount, 'attendees' => $xml->attendees, 'metadata' => $xml->metadata );
		}
		else if( ($xml && $xml->returncode == 'FAILED') || $xml) { //If the xml packet returned failure it displays the message to the user
			return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
                        //return array('returncode' => $xml->returncode, 'message' => $xml->errors->error['message'], 'messageKey' => $xml->errors->error['key']);  //For API version 0.8
		}
		else { //If the server is unreachable, then prompts the user of the necessary action
			return null;
		}

	}

	//-----------------------------------------------getMeetings------------------------------------------------------
	/**
	*This method calls getMeetings on the bigbluebuttonbn server, then calls getMeetingInfo for each meeting and concatenates the result.
	*
	*@param URL -- the url of the bigbluebuttonbn server
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*
	*@return
	*	- If failed then returns a boolean of false.
	*	- If succeeded then returns an xml of all the meetings.
	*/
	public static function getMeetings( $URL, $SALT ) {
		$xml = BigBlueButtonBN::_wrap_simplexml_load_file( BigBlueButtonBN::getMeetingsURL( $URL, $SALT ) );
		if( $xml && $xml->returncode == 'SUCCESS' ) {
			if( $xml->messageKey )
				return ( $xml->message->asXML() );
			ob_start();
			echo '<meetings>';
			if( count( $xml->meetings ) && count( $xml->meetings->meeting ) ) {
				foreach ($xml->meetings->meeting as $meeting)
				{
					echo '<meeting>';
					echo BigBlueButtonBN::getMeetingInfo($meeting->meetingID, $meeting->moderatorPW, $URL, $SALT);
					echo '</meeting>';
				}
			}
			echo '</meetings>';
			return (ob_get_clean());
		}
		else {
			return (false);
		}
	}

	/**
	*This method calls getMeetings on the bigbluebuttonbn server, then calls getMeetingInfo for each meeting and concatenates the result.
	*
	*@param URL -- the url of the bigbluebuttonbn server
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*
	*@return
	*	- Null if the server is unreachable
	*	- If FAILED then returns an array containing a returncode, messageKey, message.
	*	- If SUCCESS then returns an array of all the meetings. Each element in the array is an array containing a meetingID,
		  moderatorPW, attendeePW, hasBeenForciblyEnded, running.
	*/
	public static function getMeetingsArray( $URL, $SALT ) {
		$xml = BigBlueButtonBN::_wrap_simplexml_load_file( BigBlueButtonBN::getMeetingsURL( $URL, $SALT ) );

		if( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey ) {//The meetings were returned
			return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
		}
		else if($xml && $xml->returncode == 'SUCCESS'){ //If there were meetings already created

			foreach ($xml->meetings->meeting as $meeting)
			{
				$meetings[] = array( 'meetingID' => $meeting->meetingID, 'moderatorPW' => $meeting->moderatorPW, 'attendeePW' => $meeting->attendeePW, 'hasBeenForciblyEnded' => $meeting->hasBeenForciblyEnded, 'running' => $meeting->running );
			}

			return $meetings;

		}
		else if( $xml ) { //If the xml packet returned failure it displays the message to the user
			return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
		}
		else { //If the server is unreachable, then prompts the user of the necessary action
			return null;
		}
	}

	//----------------------------------------------getUsers---------------------------------------
	/**
	*This method prints the usernames of the attendees in the specified conference.
	*
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebuttonbn server
	*@param modPW -- the moderator password of the meeting
	*@param URL -- the url of the bigbluebuttonbn server
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*@param UNAME -- is a boolean to determine how the username is formatted when printed. Default if false.
	*
	*@return A boolean of true if the attendees were printed successfully and false otherwise.
	*/
	public static function getUsers( $meetingID, $modPW, $URL, $SALT, $UNAME = false ) {
		$xml = BigBlueButtonBN::_wrap_simplexml_load_file( BigBlueButtonBN::getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT ) );
		if( $xml && $xml->returncode == 'SUCCESS' ) {
			ob_start();
			if( count( $xml->attendees ) && count( $xml->attendees->attendee ) ) {
				foreach ( $xml->attendees->attendee as $attendee ) {
					if( $UNAME  == true ) {
						echo "User name: ".$attendee->fullName.'<br />';
					}
					else {
						echo $attendee->fullName.'<br />';
					}
				}
			}
			return (ob_end_flush());
		}
		else {
			return (false);
		}
	}

	/**
	*This method returns an array of the attendees in the specified meeting.
	*
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebuttonbn server
	*@param modPW -- the moderator password of the meeting
	*@param URL -- the url of the bigbluebuttonbn server
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*
	*@return
	*	- Null if the server is unreachable.
	*	- If FAILED, returns an array containing a returncode, messageKey, message.
	*	- If SUCCESS, returns an array of array containing the userID, fullName, role of each attendee
	*/
	public static function getUsersArray( $meetingID, $modPW, $URL, $SALT ) {
		$xml = BigBlueButtonBN::_wrap_simplexml_load_file( BigBlueButtonBN::getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT ) );

		if( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey == null ) {//The meetings were returned
			return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
		}
		else if($xml && $xml->returncode == 'SUCCESS'){ //If there were meetings already created
			foreach ($xml->attendees->attendee as $attendee){
					$users[] = array(  'userID' => $attendee->userID, 'fullName' => $attendee->fullName, 'role' => $attendee->role );
			}
			return $users;
		}
		else if( $xml ) { //If the xml packet returned failure it displays the message to the user
			return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
		}
		else { //If the server is unreachable, then prompts the user of the necessary action
			return null;
		}
	}


	//------------------------------------------------Other Methods------------------------------------
	/**
	*This method calls end meeting on the specified meeting in the bigbluebuttonbn server.
	*
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebuttonbn server
	*@param modPW -- the moderator password of the meeting
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*@param URL -- the url of the bigbluebuttonbn server
	*
	*@return
	*	- Null if the server is unreachable
	* 	- An array containing a returncode, messageKey, message.
	*/
	public static function endMeeting( $meetingID, $modPW, $URL, $SALT ) {
		$xml = BigBlueButtonBN::_wrap_simplexml_load_file( BigBlueButtonBN::endMeetingURL( $meetingID, $modPW, $URL, $SALT ) );

		if( $xml ) { //If the xml packet returned failure it displays the message to the user
			return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
		}
		else { //If the server is unreachable, then prompts the user of the necessary action
			return null;
		}

	}

	/**
	*This method check the BigBlueButton server to see if the meeting is running (i.e. there is someone in the meeting)
	*
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebuttonbn server
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*@param URL -- the url of the bigbluebuttonbn server
	*
	*@return A boolean of true if the meeting is running and false if it is not running
	*/
	public static function isMeetingRunning( $meetingID, $URL, $SALT ) {
		$xml = BigBlueButtonBN::_wrap_simplexml_load_file( BigBlueButtonBN::isMeetingRunningURL( $meetingID, $URL, $SALT ) );
		if( $xml && $xml->returncode == 'SUCCESS' )
			return ( ( $xml->running == 'true' ) ? true : false);
		else
			return ( false );
	}

	/**
	*This method calls isMeetingRunning on the BigBlueButton server.
	*
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebuttonbn server
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*@param URL -- the url of the bigbluebuttonbn server
	*
	*@return
	* 	- If SUCCESS it returns an xml packet
	* 	- If the FAILED or the server is unreachable returns a string of 'false'
	*/
	public static function getMeetingXML( $meetingID, $URL, $SALT ) {
		$xml = BigBlueButtonBN::_wrap_simplexml_load_file( BigBlueButtonBN::isMeetingRunningURL( $meetingID, $URL, $SALT ) );
		if( $xml && $xml->returncode == 'SUCCESS')
			return ( str_replace('</response>', '', str_replace("<?xml version=\"1.0\"?>\n<response>", '', $xml->asXML())));
		else
			return 'false';
	}


	// TODO: WRITE AN ITERATOR WHICH GOES OVER WHATEVER IT IS BEING TOLD IN THE API AND LIST INFORMATION
	/* we have to define at least 2 variable fields for getInformation to read out information at any position
	The first is: An identifier to chose if we look for attendees or the meetings or something else
	The second is: An identifier to chose what integrated functions are supposed to be used

	@param IDENTIFIER -- needs to be put in for the function to identify the information to print out
				 current values which can be used are 'attendee' and 'meetings'
	@param meetingID -- needs to be put in to identify the meeting
	@param modPW -- needs to be put in if the users are supposed to be shown or to retrieve information about the meetings
	@param URL -- needs to be put in the URL to the bigbluebuttonbn server
	@param SALT -- needs to be put in for the security salt calculation

	Note: If 'meetings' is used, then only the parameters URL and SALT needs to be used
		  If 'attendee' is used, then all the parameters needs to be used
	*/
	public static function getInformation( $IDENTIFIER, $meetingID, $modPW, $URL, $SALT ) {
		// if the identifier is null or '', then return false
		if( $IDENTIFIER == "" || $IDENTIFIER == null ) {
			echo "You need to type in a valid value into the identifier.";
			return false;
		}
		// if the identifier is attendee, call getUsers
		else if( $IDENTIFIER == 'attendee' ) {
			return BigBlueButtonBN::getUsers( $meetingID, $modPW, $URL, $SALT );
		}
		// if the identifier is meetings, call getMeetings
		else if( $IDENTIFIER == 'meetings' ) {
			return BigBlueButtonBN::getMeetings( $URL, $SALT );
		}
		// return nothing
		else {
			return true;
		}

	}


	function getServerIP() {
		// get the server url
		$sIP = $_SERVER['SERVER_ADDR'];
		return $serverIP = 'http://'.$sIP.'/bigbluebuttonbn/';
	}


	/**
	*This method check the BigBlueButton server to see if the meeting has been created
	*
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebuttonbn server
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*@param URL -- the url of the bigbluebuttonbn server
	*
	*@return A boolean of true if the meeting has been created, doesn't matter if is running or not and false if it does not exist
	*/
	public static function isMeetingCreated( $meetingID, $URL, $SALT ) {

                $xml = BigBlueButtonBN::_wrap_simplexml_load_file( BigBlueButtonBN::getMeetingsURL( $URL, $SALT ) );
                if( $xml && $xml->returncode == 'SUCCESS' )
                    foreach ($xml->meetings->meeting as $meeting)
                        if ( $meeting->meetingID == $meetingID && $meeting->hasBeenForciblyEnded == 'false' )
                            return true;
                return false;

	}

	/**
	*This method creates a new meeting room in the BigBlueButton server
	*
	*@param name -- a name fot the meeting
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebuttonbn server
	*@param attendeePW -- the attendee of the meeting
	*@param moderatorPW -- the moderator of the meeting
	*@param welcome -- the welcome message that gets displayed on the chat window
	*@param logoutURL -- the URL that the bbb client will go to after users logouut
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*@param URL -- the url of the bigbluebuttonbn server
	*@param record -- the flag which indicate if the meetings will be recorded or not record=true|false, default false
	*
	*@return A boolean of true if the meeting has been created, doesn't matter if is running or not and false if it was an error
	*/

	public static function createMeeting($name, $meetingID, $attendeePW, $moderatorPW, $welcome, $logoutURL, $SALT, $URL, $record = 'false', $duration=0, $voiceBridge=0, $metadata = array() ) {

		$xml = BigBlueButtonBN::_wrap_simplexml_load_file( BigBlueButtonBN::createMeetingURL($name, $meetingID, $attendeePW, $moderatorPW, $welcome, $logoutURL, $SALT, $URL, $record, $duration, $voiceBridge, $metadata ) );
		if( $xml && $xml->returncode == 'SUCCESS' )
			return true;
		else
			return false;

	}


////////////////////////TO DO: CHANGE THE DESCRIPTION OF THE NEW METHODS

	public static function getRecordingsURL($meetingID, $URL, $SALT ) {
		$base_url_record = $URL."api/getRecordings?";
		$params = "meetingID=".urlencode($meetingID);

		return ($base_url_record.$params."&checksum=".sha1("getRecordings".$params.$SALT) );
	}

	/**
	*This method calls getMeetings on the bigbluebuttonbn server, then calls getMeetingInfo for each meeting and concatenates the result.
	*
	*@param URL -- the url of the bigbluebuttonbn server
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*
	*@return
	*	- Null if the server is unreachable
	*	- If FAILED then returns an array containing a returncode, messageKey, message.
	*	- If SUCCESS then returns an array of all the meetings. Each element in the array is an array containing a meetingID,
		  moderatorPW, attendeePW, hasBeenForciblyEnded, running.
	*/
	public static function getRecordingsArray($meetingID, $URL, $SALT ) {
            $xml = BigBlueButtonBN::_wrap_simplexml_load_file( BigBlueButtonBN::getRecordingsURL( $meetingID, $URL, $SALT ) );
            if( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey ) {//The meetings were returned
                return array('returncode' => (string) $xml->returncode, 'message' => (string) $xml->message, 'messageKey' => (string) $xml->messageKey);
            } else if($xml && $xml->returncode == 'SUCCESS'){ //If there were meetings already created
		$recordings = array();

		foreach ($xml->recordings->recording as $recording) {
                    $recordings[(string) $recording->recordID] = array( 'recordID' => (string) $recording->recordID, 'meetingID' => (string) $recording->meetingID, 'meetingName' => (string) $recording->name, 'published' => (string) $recording->published, 'startTime' => (string) $recording->startTime, 'endTime' => (string) $recording->endTime );
                    $recordings[(string) $recording->recordID]['playbacks'] = array();
                    foreach ( $recording->playback->format as $format ){
                        $recordings[(string) $recording->recordID]['playbacks'][(string) $format->type] = array( 'type' => (string) $format->type, 'url' => (string) $format->url );
                    }
                    // THIS IS FOR TESTING MULTIPLE FORMATS, DO REMOVE IT FOR FINAL RELEASE
                    //$recordings[(string) $recording->recordID]['playbacks']['desktop'] = array( 'type' => 'desktop', 'url' => (string) $recording->playback->format->url );

                    //Add the metadata to the recordings array
                    $metadata = get_object_vars($recording->metadata);
                    while ($data = current($metadata)) {
                        $recordings[(string) $recording->recordID]['meta_'.key($metadata)] = $data;
                        next($metadata);
                    }
		}

                ksort($recordings);

		return $recordings;

            } else if( $xml ) { //If the xml packet returned failure it displays the message to the user
		return array('returncode' => (string) $xml->returncode, 'message' => (string) $xml->message, 'messageKey' => (string) $xml->messageKey);
            } else { //If the server is unreachable, then prompts the user of the necessary action
		return NULL;
            }
	}


	public static function deleteRecordingsURL( $recordID, $URL, $SALT ) {
		$url_delete = $URL."api/deleteRecordings?";
		$params = 'recordID='.urlencode($recordID);
		return ($url_delete.$params.'&checksum='.sha1("deleteRecordings".$params.$SALT) );
	}


	public static function deleteRecordings( $recordIDs, $URL, $SALT ) {

		$ids = 	explode(",", $recordIDs);
		foreach( $ids as $id){
			$xml = BigBlueButtonBN::_wrap_simplexml_load_file( BigBlueButtonBN::deleteRecordingsURL($id, $URL, $SALT) );
			if( $xml && $xml->returncode != 'SUCCESS' )
				return false;
		}
		return true;
	}



	public static function setPublishRecordingsURL( $recordID, $set, $URL, $SALT ) {
		$url_delete = $URL."api/publishRecordings?";
		$params = 'recordID='.$recordID."&publish=".$set;
		return ($url_delete.$params.'&checksum='.sha1("publishRecordings".$params.$SALT) );
	}


	public static function setPublishRecordings( $recordIDs, $set, $URL, $SALT ) {

		$ids = 	explode(",", $recordIDs);
		foreach( $ids as $id){
			$xml = BigBlueButtonBN::_wrap_simplexml_load_file( BigBlueButtonBN::setPublishRecordingsURL($id, $set, $URL, $SALT) );
			if( $xml && $xml->returncode != 'SUCCESS' )
				return false;
		}
		return true;
	}



	public static function getServerVersion( $URL ){
		$base_url_record = $URL."api";

		$xml = BigBlueButtonBN::_wrap_simplexml_load_file( $base_url_record );
		if( $xml && $xml->returncode == 'SUCCESS' )
			return $xml->version;
		else
			return NULL;

	}

	public static function isServerRunning( $URL ){
		$base_url_record = $URL."api";

		$xml = BigBlueButtonBN::_wrap_simplexml_load_file( $base_url_record );
		if( $xml && $xml->returncode == 'SUCCESS' )
			return true;
		else
			return false;

	}


    public function _wrap_simplexml_load_file($url){

        if (extension_loaded('curl')) {
            $ch = curl_init() or die ( curl_error() );
            $timeout = 10;
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $data = curl_exec( $ch );
            curl_close( $ch );

            if($data)
                return (new SimpleXMLElement($data,LIBXML_NOCDATA));
            else
                return false;
        }

        return (simplexml_load_file($url,'SimpleXMLElement', LIBXML_NOCDATA));
    }


}
//----End