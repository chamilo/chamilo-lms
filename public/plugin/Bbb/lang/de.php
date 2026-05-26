<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Videokonferenz';
$strings['plugin_comment'] = 'Add a videoconference room in a Chamilo course using BigBlueButton (BBB)';

$strings['Videoconference'] = 'Videokonferenz';
$strings['MeetingOpened'] = 'Offen';
$strings['MeetingClosed'] = 'Beendet';
$strings['MeetingClosedComment'] = 'Wenn Sie die Videokonferenz aufgezeichnet haben, ist die Videoaufzeichnung in der Liste unten in wenigen Augenblicken verfügbar.';
$strings['CloseMeeting'] = 'Meeting beenden';

$strings['VideoConferenceXCourseX'] = 'Videokonferenz #%s Lerninsel %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Videokonferenz zum Kalender hinzugefügt';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Videokonferenz zum Link-Tool hinzugefügt';

$strings['GoToTheVideoConference'] = 'Gehe zur Videokonferenz';

$strings['Records'] = 'Videoaufzeichnung';
$strings['Meeting'] = 'Meeting';

$strings['ViewRecord'] = 'Videoaufzeichnung ansehen';
$strings['CopyToLinkTool'] = 'Copy to link tool';

$strings['EnterConference'] = 'Videokonferenz starten';
$strings['RecordList'] = 'Liste der Videoaufzeichnungen';
$strings['ServerIsNotRunning'] = 'Videokonferenzserver läuft nicht';
$strings['ServerIsNotConfigured'] = 'Videokonferenzserver ist nicht konfiguriert';

$strings['XUsersOnLine'] = '%s Benutzer online';

$strings['host'] = 'BigBlueButton host';
$strings['host_help'] = 'This is the name of the server where your BigBlueButton server is running. Might be localhost, an IP address (e.g. http://192.168.13.54) or a domain name (e.g. http://my.video.com).';

$strings['salt'] = 'BigBlueButton salt';
$strings['salt_help'] = 'This is the security key of your BigBlueButton server, which will allow your server to authentify the Chamilo installation. Refer to the BigBlueButton documentation to locate it. Try bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'Willkommensnachricht';
$strings['enable_global_conference'] = 'Globale Konferenz aktivieren';
$strings['enable_global_conference_per_user'] = 'Globale Konferenz pro Benutzer aktivieren';
$strings['enable_conference_in_course_groups'] = 'Konferenz in Kurs-Gruppen aktivieren';
$strings['enable_global_conference_link'] = 'Aktivieren Sie den Link zur globalen Konferenz auf der Website';
$strings['disable_download_conference_link'] = 'Download der Konferenz deaktivieren';
$strings['big_blue_button_record_and_store'] = 'Aufnehmen und Speichern von Meetings';
$strings['bbb_enable_conference_in_groups'] = 'Konferenz in Gruppen zulassen';
$strings['plugin_tool_bbb'] = 'Video';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Es gibt keine Aufnahmen von den Meetings';
$strings['No recording'] = 'Keine Videoaufzeichnung';
$strings['ClickToContinue'] = 'Klicken um fortzufahren';
$strings['NoGroup'] = 'Keine Gruppe';
$strings['UrlMeetingToShare'] = 'URL teilen';
$strings['AdminView'] = 'Administrator Ansicht';
$strings['max_users_limit'] = 'Maximale Anzahl von Benutzern';
$strings['max_users_limit_help'] = 'Setzen Sie diese auf die maximale Anzahl der Benutzer, die Sie nach Kurs oder Session-Kurs erlauben möchten. Leer lassen oder auf 0 setzen, um dieses Limit zu deaktivieren.';
$strings['MaxXUsersWarning'] = 'Dieser Konferenzraum hat eine maximale Anzahl von %s gleichzeitigen Benutzern.';
$strings['MaxXUsersReached'] = 'The limit of %s simultaneous users has been reached for this conference room. Please wait for one seat to be freed or for another conference to start in order to join.';
$strings['MaxXUsersReachedManager'] = 'The limit of %s simultaneous users has been reached for this conference room. To increase this limit, please contact the platform administrator.';
$strings['MaxUsersInConferenceRoom'] = 'Max simultaneous users in a conference room';
$strings['global_conference_allow_roles'] = 'Globaler Konferenz-Link nur für diese Benutzerrollen sichtbar';
$strings['CreatedAt'] = 'Erstellt am';
$strings['allow_regenerate_recording'] = 'Wiederholte Generierung der Aufzeichnung erlauben';
$strings['bbb_force_record_generation'] = 'Die Erstellung der Aufzeichnung am Ende des Meetings erzwingen.';
$strings['disable_course_settings'] = 'Kurs-Einstellungen deaktivieren';
$strings['UpdateAllCourses'] = 'Alle Kurse aktualisieren';
$strings['UpdateAllCourseSettings'] = 'Alle Kurseinstellungen aktualisieren';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Dadurch werden alle Einstellungen in allen Kursen aktualisiert.';
$strings['ThereIsNoVideoConferenceActive'] = 'Derzeit ist keine Videokonferenz aktiv';
$strings['RoomClosed'] = 'Raum geschlossen';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Sitzungsdauer (in Minuten)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Den Lernenden ermöglichen, die Videokonferenzen ihrer Gruppen zu starten.';
$strings['hide_conference_link'] = 'Konferenz-Link im Kurs-Tool ausblenden';
$strings['hide_conference_link_comment'] = 'Block mit Link zur Videokonferenz neben dem Beitritts-Button anzeigen oder ausblenden, damit Benutzer ihn kopieren und in einem anderen Browserfenster einfügen oder andere einladen können. Eine Authentifizierung ist weiterhin für den Zugriff auf nicht-öffentliche Konferenzen erforderlich.';
$strings['delete_recordings_on_course_delete'] = 'Aufzeichnungen löschen, wenn der Kurs gelöscht wird';
$strings['defaultVisibilityInCourseHomepage'] = 'Standardsichtbarkeit auf der Kursstartseite';
$strings['ViewActivityDashboard'] = 'Aktivitäts-Dashboard anzeigen';
$strings['Participants'] = 'Teilnehmer';
$strings['CountUsers'] = 'Benutzer zählen';
$strings['Review meetings, recordings and available actions.'] = 'Überprüfen Sie Besprechungen, Aufzeichnungen und verfügbare Aktionen.';
$strings['Select the PDF or PPTX files you want to pre-load as slides for the conference.'] = 'Wählen Sie die PDF- oder PPTX-Dateien aus, die Sie als Präsentationsfolien für die Konferenz vorab laden möchten.';
$strings['Max total: %d MB'] = 'Maximale Gesamtgröße: %d MB';
$strings['Pre-upload Documents'] = 'Dokumente vorab hochladen';
$strings['No documents found'] = 'Keine Dokumente gefunden';
$strings['Failed to load documents'] = 'Dokumente konnten nicht geladen werden';
$strings['No meetings or recordings are available yet.'] = 'Es sind noch keine Meetings oder Aufzeichnungen verfügbar.';
$strings['Share the conference link with allowed participants.'] = 'Link mit zugelassenen Teilnehmenden teilen';
