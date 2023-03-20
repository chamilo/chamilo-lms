<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = "Videokonferenz";
$strings['plugin_comment'] = "Add a videoconference room in a Chamilo course using BigBlueButton (BBB)";

$strings['Videoconference'] = "Videokonferenz";
$strings['MeetingOpened'] = "Offen";
$strings['MeetingClosed'] = "Beendet";
$strings['MeetingClosedComment'] = "Wenn Sie die Videokonferenz aufgezeichnet haben, ist die Videoaufzeichnung in der Liste unten in wenigen Augenblicken verfügbar.";
$strings['CloseMeeting'] = "Meeting beenden";

$strings['VideoConferenceXCourseX'] = "Videokonferenz #%s Lerninsel %s";
$strings['VideoConferenceAddedToTheCalendar'] = "Videokonferenz zum Kalender hinzugefügt";
$strings['VideoConferenceAddedToTheLinkTool'] = "Videokonferenz zum Link-Tool hinzugefügt";

$strings['GoToTheVideoConference'] = "Gehe zur Videokonferenz";

$strings['Records'] = "Videoaufzeichnung";
$strings['Meeting'] = "Meeting";

$strings['ViewRecord'] = "Videoaufzeichnung ansehen";
$strings['CopyToLinkTool'] = "Copy to link tool";

$strings['EnterConference'] = "Videokonferenz starten";
$strings['RecordList'] = "Liste der Videoaufzeichnungen";
$strings['ServerIsNotRunning'] = "Videokonferenzserver läuft nicht";
$strings['ServerIsNotConfigured'] = "Videokonferenzserver ist nicht konfiguriert";

$strings['XUsersOnLine'] = "%s Benutzer online";

$strings['host'] = 'BigBlueButton host';
$strings['host_help'] = 'This is the name of the server where your BigBlueButton server is running. Might be localhost, an IP address (e.g. http://192.168.13.54) or a domain name (e.g. http://my.video.com).';

$strings['salt'] = 'BigBlueButton salt';
$strings['salt_help'] = 'This is the security key of your BigBlueButton server, which will allow your server to authentify the Chamilo installation. Refer to the BigBlueButton documentation to locate it. Try bbb-conf --salt';

$strings['tool_enable'] = 'BigBlueButton Videokonferenz-Tool aktiviert';
$strings['tool_enable_help'] = "Choose whether you want to enable the BigBlueButton videoconference tool.
    Once enabled, it will show as an additional course tool in all courses' homepage, and teachers will be able to launch a conference at any time. Students will not be able to launch a conference, only join one. If you don't have a BigBlueButton server, please <a target=\"_blank\" href=\"http://bigbluebutton.org/\">set one up</a> or ask the Chamilo official providers for a quote. BigBlueButton is a free (as in freedom *and* beer), but its installation requires a set of technical skills that might not be immediately available to all. You can install it on your own or seek professional help to assist you or do it for you. This help, however, will generate a certain cost. In the pure logic of the free software, we offer you the tools to make your work easier and recommend professionals (the Chamilo Official Providers) that will be able to help you if this were too difficult.<br />";

$strings['big_blue_button_welcome_message'] = 'Willkommensnachricht';
$strings['enable_global_conference'] = 'Globale Konferenz aktivieren';
$strings['enable_global_conference_per_user'] = 'Globale Konferenz pro Benutzer aktivieren';
$strings['enable_conference_in_course_groups'] = 'Konferenz in Kurs-Gruppen aktivieren';
$strings['enable_global_conference_link'] = 'Aktivieren Sie den Link zur globalen Konferenz auf der Website';

$strings['big_blue_button_record_and_store'] = 'Aufnehmen und Speichern von Meetings';
$strings['bbb_enable_conference_in_groups'] = 'Konferenz in Gruppen zulassen';
$strings['plugin_tool_bbb'] = 'Video';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Es gibt keine Aufnahmen von den Meetings';
$strings['NoRecording'] = 'Keine Videoaufzeichnung';
$strings['ClickToContinue'] = 'Klicken um fortzufahren';
$strings['NoGroup'] = 'Keine Gruppe';
$strings['UrlMeetingToShare'] = 'URL zu teilen';

$strings['AdminView'] = 'Administrator Ansicht';
$strings['max_users_limit'] = 'Maximale Anzahl von Benutzern';
$strings['max_users_limit_help'] = 'Setzen Sie diese auf die maximale Anzahl der Benutzer, die Sie nach Kurs oder Session-Kurs erlauben möchten. Leer lassen oder auf 0 setzen, um dieses Limit zu deaktivieren.';
$strings['MaxXUsersWarning'] = 'Dieser Konferenzraum hat eine maximale Anzahl von %s gleichzeitigen Benutzern.';
$strings['MaxXUsersReached'] = 'The limit of %s simultaneous users has been reached for this conference room. Please wait for one seat to be freed or for another conference to start in order to join.';
$strings['MaxXUsersReachedManager'] = 'The limit of %s simultaneous users has been reached for this conference room. To increase this limit, please contact the platform administrator.';
$strings['MaxUsersInConferenceRoom'] = 'Max simultaneous users in a conference room';
$strings['global_conference_allow_roles'] = "Globaler Konferenz-Link nur für diese Benutzerrollen sichtbar";
$strings['CreatedAt'] = "Erstellt am";
