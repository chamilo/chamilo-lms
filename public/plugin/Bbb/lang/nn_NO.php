<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Videokonferanse';
$strings['plugin_comment'] = 'Legg til eit videokonferanserom i ein Chamilo-kurs ved hjelp av BigBlueButton (BBB)';

$strings['Videoconference'] = 'Videokonferanse';
$strings['MeetingOpened'] = 'Møte opna';
$strings['MeetingClosed'] = 'Møte lukka';
$strings['MeetingClosedComment'] = 'Om du har bede om at sesjonane dine skal bli spelt inn, blir opptaka tilgjengeleg i lista under når den er heilt generert.';
$strings['CloseMeeting'] = 'Lukk møte';

$strings['VideoConferenceXCourseX'] = 'Videokonferanse #%s kurs %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Videokonferanse lagt til i kalenderen';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Videokonferanse lagt til i lenkeverket';

$strings['GoToTheVideoConference'] = 'Gå til videokonferansen';

$strings['Records'] = 'Opptak';
$strings['Meeting'] = 'Møte';

$strings['ViewRecord'] = 'Vis opptak';
$strings['CopyToLinkTool'] = 'Kopier til lenkeverket';

$strings['EnterConference'] = 'Gå inn i videokonferansen';
$strings['RecordList'] = 'Opptaksliste';
$strings['ServerIsNotRunning'] = 'Videokonferanse-serveren køyrer ikkje';
$strings['ServerIsNotConfigured'] = 'Videokonferanse-serveren er ikkje konfigurert';

$strings['XUsersOnLine'] = '%s brukar(ar) pålogga';

$strings['host'] = 'BigBlueButton-vert';
$strings['host_help'] = 'Dette er namnet på serveren der BigBlueButton-serveren din køyrer.
Kan vere localhost, ei IP-adresse (t.d. http://192.168.13.54) eller eit domene (t.d. http://my.video.com).';

$strings['salt'] = 'BigBlueButton-salt';
$strings['salt_help'] = 'Dette er sikkerheitsnøkkelen til BigBlueButton-serveren din, som vil la serveren din autentifisere Chamilo-installasjonen. Sjå BigBlueButton-dokumentasjonen for å finne den. Prøv bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'Velkomstmelding';
$strings['enable_global_conference'] = 'Aktiver global konferanse';
$strings['enable_global_conference_per_user'] = 'Aktiver global konferanse per brukar';
$strings['enable_conference_in_course_groups'] = 'Aktiver konferanse i kursgrupper';
$strings['enable_global_conference_link'] = 'Aktiver lenka til global konferanse på hovudsida';
$strings['disable_download_conference_link'] = 'Deaktiver nedlasting av konferanse';
$strings['big_blue_button_record_and_store'] = 'Spel inn og lagre sesjonar';
$strings['bbb_enable_conference_in_groups'] = 'Tillat konferanse i grupper';
$strings['plugin_tool_bbb'] = 'Video';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Det finst ikkje opptak for møtesesjonane';
$strings['NoRecording'] = 'Ingen opptak';
$strings['ClickToContinue'] = 'Klikk for å fortsette';
$strings['NoGroup'] = 'Ingen gruppe';
$strings['UrlMeetingToShare'] = 'URL å dele';
$strings['AdminView'] = 'Visning for administratorar';
$strings['max_users_limit'] = 'Maksgrense for brukarar';
$strings['max_users_limit_help'] = 'Sett dette til maksimalt tal brukarar du vil tillate per kurs eller sesjon-kurs. Lat stå tom eller sett til 0 for å deaktivere grensa.';
$strings['MaxXUsersWarning'] = 'Dette konferanserommet har ein maksimal grense på %s samtidige brukarar.';
$strings['MaxXUsersReached'] = 'Grensa på %s samtidige brukarar er nådd for dette konferanserommet. Vent på at ein plass blir ledig eller at ein annan konferanse startar for å delta.';
$strings['MaxXUsersReachedManager'] = 'Grensa på %s samtidige brukarar er nådd for dette konferanserommet. Kontakt plattformadministrator for å auke grensa.';
$strings['MaxUsersInConferenceRoom'] = 'Maks antal samtidige brukarar i eit konferanserom';
$strings['global_conference_allow_roles'] = 'Global konferanselanke berre synleg for desse brukarrollene';
$strings['CreatedAt'] = 'Oppretta kl.';
$strings['allow_regenerate_recording'] = 'Tillat regenerering av opptak';
$strings['bbb_force_record_generation'] = 'Tving generering av opptak ved slutten av møtet';
$strings['disable_course_settings'] = 'Deaktiver kursinnstillingar';
$strings['UpdateAllCourses'] = 'Oppdater alle kurs';
$strings['UpdateAllCourseSettings'] = 'Oppdater alle kursinnstillingar';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Dette vil oppdatere alle kursinnstillingane dine på ein gong.';
$strings['ThereIsNoVideoConferenceActive'] = 'Det finst ikkje noko aktivt videokonferanse for tida';
$strings['RoomClosed'] = 'Rommet er lukka';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Møtedurasjon (i minutt)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Tillat studentar å starte konferanse i gruppene sine.';
$strings['hide_conference_link'] = 'Skjul konferanselenke i kursverktøy';
$strings['hide_conference_link_comment'] = 'Vis eller skjul ein blokk med lenke til videokonferansen ved sidan av delta-knappen, slik at brukarar kan kopiere den og lime den i eit anna nettlesarvindauge eller invitere andre. Autentisering er framleis nødvendig for å få tilgang til ikkje-offentlege konferansar.';
$strings['delete_recordings_on_course_delete'] = 'Slett opptak når kurset vert fjerna';
$strings['defaultVisibilityInCourseHomepage'] = 'Standard synleggje på kurs startsida';
$strings['ViewActivityDashboard'] = 'Vis aktivitetsdashbord';
$strings['Participants'] = 'Deltakarar';
$strings['CountUsers'] = 'Tel brukerar';
