<?php
/**
 * This file contains the helper libraries for the BigBlueButton conference plugin. 
 * It is based on code written by Dual Code Inc in GNU/GPLv2
 * Copyright (C) 2010 Dual Code Inc. (www.dualcode.com)
 * Copyright (C) 2010 BeezNest Belgium SPRL (www.beeznest.com) - Yannick Warnier - y@beeznest.com
 * @package chamilo.plugin.bigbluebutton
*/
/**
 * Inserts an item into the plugin_bbb table
 */
function bigbluebutton_insert_record($table,$object) {
    //ignore the first param (used for compatibility with existing code)
    $table = Database::get_main_table('plugin_bbb');
    $sql = "INSERT INTO $table (course_id, name, meeting_name, meeting_id, attendee_pw, moderator_pw, auto_login, new_window, welcome_msg)" .
            "VALUES (" .
            intval($object->course) .", " .
            "'".Database::escape_string($object->name)."'," .
            "'".Database::escape_string($object->meetingname)."'," .
            "'".Database::escape_string($object->meetingid)."'," .
            "'".Database::escape_string($object->attendeepw)."'," .
            "'".Database::escape_string($object->moderatorpw)."'," .
            "'".Database::escape_string($object->autologin)."'," .
            "'".Database::escape_string($object->newwindow)."'," .
            "'".Database::escape_string($object->welcomemsg)."'" .
            ")";
    Database::query($sql);
    return Database::insert_id();
}
/**
 * Updates a bigbluebutton record
 */
function bigbluebutton_update_record($table, $object) {
    //ignore the first param (used for compatibility with existing code)    $table = Database::get_main_table('plugin_bbb');
    $sql = "UPDATE $table (course_id, name, meeting_name, meeting_id, attendee_pw, moderator_pw, auto_login, new_window, welcome_msg)" .
            " SET course_id = ".intval($object->course) .", " .
            " name = '".Database::escape_string($object->name)."'," .
            " meeting_name = '".Database::escape_string($object->meetingname)."'," .
            " meeting_id = '".Database::escape_string($object->meetingid)."'," .
            " attendee_pw = '".Database::escape_string($object->attendeepw)."'," .
            " moderator_pw = '".Database::escape_string($object->moderatorpw)."'," .
            " auto_login = '".Database::escape_string($object->autologin)."'," .
            " new_window = '".Database::escape_string($object->newwindow)."'," .
            " welcome_msg = '".Database::escape_string($object->welcomemsg)."'," .
            " WHERE id = " .intval($object->id).
            ")";
    Database::query($sql);
    return $oject->id;
}
/**
 * Gets a bigbluebutton room record from an ID
 */
function bigbluebutton_get_record($table,$field,$id) {
    //ignore the first param (used for compatibility with existing code)    
    $table = Database::get_main_table('plugin_bbb');
    $sql = "SELECT * FROM $table WHERE id = ".intval($id);
    $res = Database::query($sql);
    if (Database::num_rows($res)>0) {
    	$row = Database::fetch_assoc($res);
        $room = null;
        $room->id = $id;
        $room->course = $row['course_id'];
        $room->name = $row['name'];
        $room->meetingname = $row['meeting_name'];
        $room->meetingid = $row['meeting_id'];
        $room->attendeepw = $row['attendee_pw'];
        $room->moderatorpw = $row['moderator_pw'];
        $room->autologin = $row['auto_login'];
        $room->newwindow = $row['new_window'];
        $room->welcomemsg = $row['welcome_msg'];
        return $room;
    } else {
    	return null;
    }
}
/**
 * Gets a bigbluebutton room record from an ID
 */
function bigbluebutton_delete_records($table,$field,$id) {
    //ignore the first param (used for compatibility with existing code)    
    $table = Database::get_main_table('plugin_bbb');
    $sql = "DELETE FROM $table WHERE id = ".intval($id);
}
/**
 * Add an event
 */
function bigbluebutton_add_event($event) {
	//
}
/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will create a new instance and return the id number
 * of the new instance.
 */
function bigbluebutton_add_instance($bigbluebutton) {
    $bigbluebutton->timemodified = time();
    if ($returnid = bigbluebutton_insert_record('bigbluebutton', $bigbluebutton)) {
        $event = NULL;
        $event->courseid    = $bigbluebutton->course;
        $event->name        = $bigbluebutton->name;
        $event->meetingname = $bigbluebutton->meetingname;
        $event->meetingid   = $bigbluebutton->meetingid;
        $event->attendeepw  = $bigbluebutton->attendeepw;
        $event->moderatorpw = $bigbluebutton->moderatorpw;
        $event->autologin   = $bigbluebutton->autologin;
        $event->newwindow   = $bigbluebutton->newwindow;
        $event->welcomemsg  = $bigbluebutton->welcomemsg;
        bigbluebutton_add_event($event);
    }
    return $returnid;
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will update an existing instance with new data.
 */
function bigbluebutton_update_instance($bigbluebutton) {
    $bigbluebutton->timemodified = time();
    $bigbluebutton->id = $bigbluebutton->instance;
    if ($returnid = bigbluebutton_update_record('bigbluebutton', $bigbluebutton)) {
        /*
        $event = NULL;
        if ($event->id = bigbluebutton_get_field('event', 'id', 'modulename', 'bigbluebutton', 'instance', $bigbluebutton->id)) {
            $event->courseid    = $bigbluebutton->course;
            $event->name        = $bigbluebutton->name;
            $event->meetingname = $bigbluebutton->meetingname;
            $event->meetingid   = $bigbluebutton->meetingid;
            $event->attendeepw  = $bigbluebutton->attendeepw;
            $event->moderatorpw = $bigbluebutton->moderatorpw;
            $event->autologin   = $bigbluebutton->autologin;
            $event->newwindow   = $bigbluebutton->newwindow;
            $event->welcomemsg  = $bigbluebutton->welcomemsg;   
            bigbluebutton_update_event($event);
        }*/
    }
    return $returnid;
}



/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 */
function bigbluebutton_delete_instance($id) {
    if (! $bigbluebutton = bigbluebutton_get_record('bigbluebutton', 'id', $id)) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (! bigbluebutton_delete_records('bigbluebutton', 'id', $bigbluebutton->id)) {
        $result = false;
    }
/*
    $pagetypes = page_import_types('mod/bigbluebutton/');
    foreach($pagetypes as $pagetype) {
        if(!delete_records('block_instance', 'pageid', $bigbluebutton->id, 'pagetype', $pagetype)) {
            $result = false;
        }
    }
*/
/*
    if (! bigbluebutton_delete_records('event', 'modulename', 'bigbluebutton', 'instance', $bigbluebutton->id)) {
        $result = false;
    }
*/
    return $result;
}


/**
 * Create string where we check if the meeting is running
 */
function wc_isMeetingRunningURL($myIP,$mySecuritySalt,$myMeetingID) {
    $checkAPI = "/bigbluebutton/api/isMeetingRunning?";
    $queryStr = "meetingID=".$myMeetingID;
    $checksum = sha1('isMeetingRunning'.$queryStr.$mySecuritySalt);
    $secQueryURL = "http://".$myIP.$checkAPI.$queryStr."&checksum=".$checksum;
    return $secQueryURL;
}


/**
 * Determine if the meeting is already running (e.g. has attendees in it)
 */
function wc_isMeetingRunning($myIP,$mySecuritySalt,$myMeetingID) {
    $secQueryURL = wc_isMeetingRunningURL($myIP,$mySecuritySalt,$myMeetingID);
    $myResponse = @file_get_contents($secQueryURL);
    if ($myResponse === false) { return false;}
    $doc = new DOMDocument();
    $doc->loadXML($myResponse);
    $returnCodeNode = $doc->getElementsByTagName("returncode");
    $returnCode = $returnCodeNode->item(0)->nodeValue;
    $runningNode = $doc->getElementsByTagName("running");
    $isRunning = $runningNode->item(0)->nodeValue;
    return $isRunning;
}

/**
 * Create meeting if it's not already running
 */
function wc_createMeeting($myIP,$mySecuritySalt,$myMeetingName,$myMeetingID,$myAttendeePW,$myModeratorPW,$myWelcomeMsg,$myLogoutURL) {
    $createAPI = "/bigbluebutton/api/create?";
    $myVoiceBridge = rand(70000,79999);
    $queryStr = "name=".urlencode($myMeetingName)."&meetingID=".urlencode($myMeetingID)."&attendeePW=".urlencode($myAttendeePW)."&moderatorPW=".urlencode($myModeratorPW)."&voiceBridge=".$myVoiceBridge."&welcome=".urlencode($myWelcomeMsg)."&logoutURL=".urlencode($myLogoutURL);
    $checksum = sha1('create'.$queryStr.$mySecuritySalt);
    $secQueryURL = "http://".$myIP.$createAPI.$queryStr."&checksum=".$checksum;
    $myResponse = @file_get_contents($secQueryURL);
    if ($myResponse === false) { return false; }
    $doc= new DOMDocument();
    $doc->loadXML($myResponse);
    $returnCodeNode = $doc->getElementsByTagName("returncode");
    $returnCode = $returnCodeNode->item(0)->nodeValue;

    if ($returnCode=="SUCCESS") {
      return $returnCode;
    } else {
      $messageKeyNode = $doc->getElementsByTagName("messageKey");
      $messageKey = $messageKeyNode->item(0)->nodeValue;
      return $messageKey;
    }
}


/**
 * Create a URL to join the meeting
 */
function wc_joinMeetingURL($myIP,$mySecuritySalt,$myName,$myMeetingID,$myPassword,$userID) {
    $joinAPI = "/bigbluebutton/api/join?";
    $queryStr = "fullName=".urlencode($myName)."&meetingID=".urlencode($myMeetingID)."&password=".urlencode($myPassword)."&userID=".$userID;
    $checksum = sha1('join'.$queryStr.$mySecuritySalt);
    $createStr = "http://".$myIP.$joinAPI.$queryStr."&checksum=".$checksum;
    
    return $createStr;
}

/**
 * This API is not yet supported in bigbluebutton
 */
function wc_endMeeting($myIP,$mySecuritySalt,$myMeetingID,$myModeratorPW) {
    $endAPI = "/bigbluebutton/api/end?";
    $myVoiceBridge = rand(70000,79999);
    $queryStr = "meetingID=".$myMeetingID."&moderatorPW=".$myModeratorPW."&voiceBridge=".$myVoiceBridge;
    $checksum = sha1('create'.$queryStr.$mySecuritySalt);
    $secQueryURL = "http://".$myIP.$endAPI.$queryStr."&checksum=".$checksum;
    $myResponse = @file_get_contents($secQueryURL);
    if ($myResponse === false) { return false; }
    $doc= new DOMDocument();
    $doc->loadXML($myResponse);
    $returnCodeNode = $doc->getElementsByTagName("returncode");
    $returnCode = $returnCodeNode->item(0)->nodeValue;
    if ($returnCode=="SUCCESS") {
      return $returnCode;
    } else {
      $messageKeyNode = $doc->getElementsByTagName("messageKey");
      $messageKey = $messageKeyNode->item(0)->nodeValue;
      return $messageKey;
    }
}

/**
 * This API is not yet supported in bigbluebutton
 */
function wc_listAttendees() {
    return false;
}

/**
 * This API is not yet supported in bigbluebutton
 */
function wc_getMeetingInfo($myIP,$mySecuritySalt,$meetingID,$modPW) {
    $checkAPI = "/bigbluebutton/api/getMeetingInfo?";
    $queryStr = 'meetingID='.$meetingID.'&password='.$modPW;
    $checksum = sha1('getMeetingInfo'.$queryStr.$mySecuritySalt);
    $secQueryURL = "http://".$myIP.$checkAPI.$queryStr."&checksum=".$checksum;
    $myResponse = @file_get_contents($secQueryURL);
    if ($myResponse === false) { return false;}
    $doc = new DOMDocument();
    $doc->loadXML($myResponse);
    $returnCodeNode = $doc->getElementsByTagName("returncode");
    $returnCode = $returnCodeNode->item(0)->nodeValue;
    $createTimeNode = $doc->getElementsByTagName("createTime");
    $createTime = $createTimeNode->item(0)->nodeValue;
    $runningNode = $doc->getElementsByTagName("running");
    $running = $runningNode->item(0)->nodeValue;
    $attendeesNode = $doc->getElementsByTagName("attendee");
    $attendees = array();
    foreach ($attendeesNode as $attendeeNode) {
        $attendee = array();
        if ($attendeeNode->childNodes->length) {
            foreach ($attendeeNode->childNodes as $i) {
                //see http://code.google.com/p/bigbluebutton/wiki/API#Get_Meeting_Info for details
                $attendee[$i->nodeName] = $i->nodeValue;
            }
        }
        $attendees[] = $attendee;
    }
    $info = array('returnCode'=>$returnCode,'createTime'=>$createTime,'attendees'=>$attendees,'running'=>$running);
    return $info;
}

/**
 * Determine the URL of the current page (for logoutURL)
 */
function wc_currentPageURL() {
  $isHTTPS = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on");
  $port = (isset($_SERVER["SERVER_PORT"]) && ((!$isHTTPS && $_SERVER["SERVER_PORT"] != "80") || ($isHTTPS && $_SERVER["SERVER_PORT"] != "443")));
  $port = ($port) ? ':'.$_SERVER["SERVER_PORT"] : '';
  $pageURL = ($isHTTPS ? 'https://' : 'http://').$_SERVER["SERVER_NAME"].$port.$_SERVER["REQUEST_URI"];
  return $pageURL;
}


/**
 * Determine the IP/Domain of the current Corporate University
 */
function wc_currentDomain() {
  $currentDomain = $_SERVER["SERVER_NAME"];
  return $currentDomain;
}


/**
 * Determine if a new version of the plug-in is available
 */
function wc_needUpgrade() {
  $returnValue = false;
  $installedVersion = "20100805";
  $availableVersion = dc_getVersion();
  if ((int)$installedVersion < (int)$availableVersion) {
      $returnValue = true;
  }
  return $returnValue;
}

/**
 * Gets a list of all meetings currently running 
 */
function wc_getRunningMeetings($myIP,$mySecuritySalt) {
    $checkAPI = "/bigbluebutton/api/getMeetings?";
    $queryStr = '';
    $checksum = sha1('getMeetings'.$queryStr.$mySecuritySalt);
    $secQueryURL = "http://".$myIP.$checkAPI.$queryStr."&checksum=".$checksum;
    $myResponse = @file_get_contents($secQueryURL);
    if ($myResponse === false) { return false;}
    $doc = new DOMDocument();
    $doc->loadXML($myResponse);
    $returnCodeNode = $doc->getElementsByTagName("returncode");
    $returnCode = $returnCodeNode->item(0)->nodeValue;
    $meetingsNode = $doc->getElementsByTagName("meeting");
    $meetings = array();
    foreach ($meetingsNode as $meetingNode) {
        $meeting = array();
        if ($meetingNode->childNodes->length) {
            foreach ($meetingNode->childNodes as $i) {
                //see http://code.google.com/p/bigbluebutton/wiki/API#Get_Meetings for details
                $meeting[$i->nodeName] = $i->nodeValue;
            }
        }
        $meetings[] = $meeting;
    }
    return $meetings;
}
