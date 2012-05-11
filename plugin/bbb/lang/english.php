<?php
/**
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */

//Needed in order to show the plugin title
$strings['plugin_title']        = "BigBlueButton (BBB)";
$strings['plugin_comment']      = "Adds a videoconference room Chamilo courses";

$strings['Videoconference']     = "Videoconference";
$strings['MeetingOpened']       = "Meeting opened";
$strings['MeetingClosed']       = "Meeting closed";
$strings['CloseMeeting']        = "Close meeting";

$strings['VideoConferenceXCourseX']             = "Videoconference #%s course %s";
$strings['VideoConferenceAddedToTheCalendar']   = "Videoconference added to the calendar";
$strings['VideoConferenceAddedToTheLinkTool']   = "Videoconference added to the link tool";

$strings['GoToTheVideoConference']   = "Go to the videoconference";

$strings['Records']             = "Records";
$strings['Meeting']             = "Meeting";

$strings['ViewRecord']          = "View record";
$strings['CopyToLinkTool']      = "Copy to link tool";

$strings['EnterConference']     = "Enter to the videoconference";
$strings['RecordList']          = "Record list";
$strings['ServerIsNotRunning']  = "Videoconference server is not running";
$strings['ServerIsNotConfigured']  = "Videoconference server is not configured";

$strings['XUsersOnLine']        = "%s user(s) online";

$strings['host'] = 'BigBlueButton host';
$strings['host_help'] = 'This is the name of the server where your BigBlueButton server is running. Might be localhost, an IP address (e.g. 192.168.13.54) or a domain name (e.g. my.video.com).';

$strings['salt'] = 'BigBlueButton salt';
$strings['salt_help'] = 'This is the security key of your BigBlueButton server, which will allow your server to authentify the Chamilo installation. Refer to the BigBlueButton documentation to locate it. Try bbb-conf --salt';

$strings['tool_enable'] = 'BigBlueButton videoconference tool enabled';
$strings['tool_enable_help'] = "Choose whether you want to enable the BigBlueButton videoconference tool. 
    Once enabled, it will show as an additional course tool in all courses' homepage, and teachers will be able to launch a conference at any time. Students will not be able to launch a conference, only join one. If you don't have a BigBlueButton server, please <a target=\"_blank\" href=\"http://bigbluebutton.org/\">set one up</a> or ask the Chamilo official providers for a quote. BigBlueButton is a free (as in freedom *and* beer), but its installation requires a set of technical skills that might not be immediately available to all. You can install it on your own or seek professional help to assist you or do it for you. This help, however, will generate a certain cost. In the pure logic of the free software, we offer you the tools to make your work easier and recommend professionals (the Chamilo Official Providers) that will be able to help you if this were too difficult.<br />";