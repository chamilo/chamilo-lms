<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = "Videoconference";
$strings['plugin_comment'] = "Add a videoconference room in a Chamilo course using BigBlueButton (BBB)";

$strings['Videoconference'] = "Videoconference";
$strings['MeetingOpened'] = "Meeting opened";
$strings['MeetingClosed'] = "Meeting closed";
$strings['MeetingClosedComment'] = "If you have asked for your sessions to be recorded, the recording will be available in the list below when it has been completely generated.";
$strings['CloseMeeting'] = "Close meeting";

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

$strings['host'] = 'BigBlueButton host';
$strings['host_help'] = 'This is the name of the server where your BigBlueButton server is running.
Might be localhost, an IP address (e.g. http://192.168.13.54) or a domain name (e.g. http://my.video.com).';

$strings['salt'] = 'BigBlueButton salt';
$strings['salt_help'] = 'This is the security key of your BigBlueButton server, which will allow your server to authentify the Chamilo installation. Refer to the BigBlueButton documentation to locate it. Try bbb-conf --salt';

$strings['tool_enable'] = 'BigBlueButton videoconference tool enabled';
$strings['tool_enable_help'] = "Choose whether you want to enable the BigBlueButton videoconference tool.
    Once enabled, it will show as an additional course tool in all courses' homepage, and teachers will be able to launch a conference at any time. Students will not be able to launch a conference, only join one. If you don't have a BigBlueButton server, please <a target=\"_blank\" href=\"http://bigbluebutton.org/\">set one up</a> or ask the Chamilo official providers for a quote. BigBlueButton is a free (as in freedom *and* beer), but its installation requires a set of technical skills that might not be immediately available to all. You can install it on your own or seek professional help to assist you or do it for you. This help, however, will generate a certain cost. In the pure logic of the free software, we offer you the tools to make your work easier and recommend professionals (the Chamilo Official Providers) that will be able to help you if this were too difficult.<br />";

$strings['big_blue_button_welcome_message'] = 'Welcome message';
$strings['enable_global_conference'] = 'Enable global conference';
$strings['enable_global_conference_per_user'] = 'Enable global conference per user';
$strings['enable_conference_in_course_groups'] = 'Enable conference in course groups';
$strings['enable_global_conference_link'] = 'Enable the link to the global conference in the homepage';
$strings['disable_download_conference_link'] = 'Disable download conference';
$strings['big_blue_button_record_and_store'] = 'Record and store sessions';
$strings['bbb_enable_conference_in_groups'] = 'Allow conference in groups';
$strings['plugin_tool_bbb'] = 'Video';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'There are not recording for the meeting sessions';
$strings['NoRecording'] = 'No recording';
$strings['ClickToContinue'] = 'Click to continue';
$strings['NoGroup'] = 'No group';
$strings['UrlMeetingToShare'] = 'URL to share';
$strings['AdminView'] = 'View for administrators';
$strings['max_users_limit'] = 'Max users limit';
$strings['max_users_limit_help'] = 'Set this to the maximum number of users you want to allow by course or session-course. Leave empty or set to 0 to disable this limit.';
$strings['MaxXUsersWarning'] = 'This conference room has a maximum number of %s simultaneous users.';
$strings['MaxXUsersReached'] = 'The limit of %s simultaneous users has been reached for this conference room. Please wait for one seat to be freed or for another conference to start in order to join.';
$strings['MaxXUsersReachedManager'] = 'The limit of %s simultaneous users has been reached for this conference room. To increase this limit, please contact the platform administrator.';
$strings['MaxUsersInConferenceRoom'] = 'Max simultaneous users in a conference room';
$strings['global_conference_allow_roles'] = "Global conference link only visible for these user roles";
$strings['CreatedAt'] = 'Created at';
$strings['allow_regenerate_recording'] = 'Allow regenerate recording';
$strings['bbb_force_record_generation'] = 'Force record generation at the end of the meeting';
$strings['disable_course_settings'] = 'Disable course settings';
$strings['UpdateAllCourses'] = 'Update all courses';
$strings['UpdateAllCourseSettings'] = 'Update all course settings';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'This will update at once all your course settings.';
$strings['ThereIsNoVideoConferenceActive'] = 'There is no videoconference currently active';
$strings['RoomClosed'] = 'Room closed';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Meeting duration (in minutes)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Allow students to start conference in their groups.';
$strings['plugin_bbb_multiple_urls_cron_apply_to_all'] = 'Automatically closes all rooms on ALL campuses.';
$strings['plugin_bbb_multiple_urls_cron_apply_to_all_help'] = 'Option for multi-url environments. Allows the CRON task to close all open rooms on mother and child campus.';
