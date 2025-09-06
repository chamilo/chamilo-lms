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
$strings['delete_recordings_on_course_delete'] = 'Aufzeichnungen löschen, wenn der Kurs gelöscht wird';
$strings['defaultVisibilityInCourseHomepage'] = 'Standardsichtbarkeit auf der Kursstartseite';
