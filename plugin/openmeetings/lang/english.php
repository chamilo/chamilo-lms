<?php
/**
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */

//Needed in order to show the plugin title
$strings['plugin_title'] = "OpenMeetings";
$strings['plugin_comment'] = "[Deprecated] Add a videoconference room in a Chamilo course using OpenMeetings";

$strings['Videoconference'] = "Videoconference";
$strings['MeetingOpened'] = "Meeting opened";
$strings['MeetingClosed'] = "Meeting closed";
$strings['MeetingClosedComment'] = "If you have asked for your sessions to be recorded, the recording will be available in the list below when it has been completely generated.";
$strings['CloseMeeting'] = "Close meeting";

$strings['MeetingDeleted'] = "Delete meeting";
$strings['MeetingDeletedComment'] = "";

$strings['VideoConferenceXCourseX'] = "Videoconference #%s course %s";
$strings['VideoConferenceAddedToTheCalendar'] = "Videoconference added to the calendar";
$strings['VideoConferenceAddedToTheLinkTool'] = "Videoconference added to the link tool";

$strings['GoToTheVideoConference'] = "Go to the videoconference";

$strings['Records'] = "Recording";
$strings['Meeting'] = "Meeting";

$strings['ViewRecord'] = "View recording";
$strings['CopyToLinkTool'] = "Copy to link tool";

$strings['EnterConference'] = "Enter the videoconference";
$strings['RecordList'] = "Recording list";
$strings['ServerIsNotRunning'] = "Videoconference server is not running";
$strings['ServerIsNotConfigured'] = "Videoconference server is not configured";

$strings['XUsersOnLine'] = "%s user(s) online";

$strings['host'] = 'OpenMeetings host';
$strings['host_help'] = 'This is the full address of your OpenMeeetings server interface. Might be http://localhost:5080/openmeetings, an IP address (e.g. http://192.168.13.54:5080/openmeetings) or a domain name (e.g. http://my.video.com:5080/openmeetings).';

$strings['salt'] = 'OpenMeetings salt';
$strings['salt_help'] = 'This is the security key of your OpenMeetings server, which will allow your server to authentify the Chamilo installation. Refer to the OpenMeetings documentation to locate it.';

$strings['tool_enable'] = 'OpenMeetings videoconference tool enabled';
$strings['tool_enable_help'] = 'Choose whether you want to enable the OpenMeetings videoconference tool. Once enabled, it will show as an additional course tool in all courses homepage, and teachers will be able to launch a conference at any time. Students will not be able to launch a conference, only join one. If you don\'t have an OpenMeetings server, please <a target="_blank" href="http://openmeetings.apache.org/">set one up</a> or ask the Chamilo official providers for a quote. OpenMeetings is a free (as in freedom *and* beer), but its installation requires a set of technical skills that might not be immediately available to all. You can install it on your own or seek professional help to assist you or do it for you. This help, however, will generate a certain cost. In the pure logic of the free software, we offer you the tools to make your work easier and recommend professionals (the Chamilo Official Providers) that will be able to help you if this were too difficult.<br />';

$strings['openmeetings_welcome_message'] = 'Welcome message';
$strings['openmeetings_record_and_store'] = 'Record and store sessions';

$strings['plugin_tool_openmeetings'] = 'Video';

$strings['ThereAreNotRecordingsForTheMeetings'] = 'There are not recording for the meeting sessions';
$strings['NoRecording'] = 'No recording';
