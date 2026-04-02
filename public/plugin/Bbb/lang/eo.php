<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Videkonferenco';
$strings['plugin_comment'] = 'Aldoni videkonferencan ĉambron en Chamilo-kurso uzante BigBlueButton (BBB)';

$strings['Videoconference'] = 'Videkonferenco';
$strings['MeetingOpened'] = 'Renkontro malfermita';
$strings['MeetingClosed'] = 'Renkontro fermita';
$strings['MeetingClosedComment'] = 'Se vi petis registri viajn sesiojn, la registraĵo estos havebla en la listo malsupre kiam ĝi estos tute generita.';
$strings['CloseMeeting'] = 'Fermi renkonton';

$strings['VideoConferenceXCourseX'] = 'Videkonferenco #%s kurso %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Videkonferenco aldonita al la kalendaro';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Videkonferenco aldonita al la ligilo-instrumento';

$strings['GoToTheVideoConference'] = 'Iri al la videkonferenco';

$strings['Records'] = 'Registraĵo';
$strings['Meeting'] = 'Renkonto';

$strings['ViewRecord'] = 'Vidi registraĵon';
$strings['CopyToLinkTool'] = 'Kopii al ligilo-instrumento';

$strings['EnterConference'] = 'Eniri la videkonferencon';
$strings['RecordList'] = 'Listo de registraĵoj';
$strings['ServerIsNotRunning'] = 'Videkonferenca servilo ne funkcias';
$strings['ServerIsNotConfigured'] = 'Videkonferenca servilo ne estas agordita';

$strings['XUsersOnLine'] = '%s uzanto(j) rete';

$strings['host'] = 'BigBlueButton gastigo';
$strings['host_help'] = 'Jen la nomo de la servilo kie funkcias via BigBlueButton-servilo.
Povas esti localhost, IP-adreso (ekz. http://192.168.13.54) aŭ domennomo (ekz. http://my.video.com).';

$strings['salt'] = 'BigBlueButton salo';
$strings['salt_help'] = 'Jen la sekureca ŝlosilo de via BigBlueButton-servilo, kiu permesos al via servilo aŭtentigi la Chamilo-instalon. Rilatu al la BigBlueButton-dokumentaro por trovi ĝin. Provi bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'Bonvenmesaĝo';
$strings['enable_global_conference'] = 'Ŝalti ĉefkonferencon';
$strings['enable_global_conference_per_user'] = 'Ŝalti ĉefkonferencon po uzanto';
$strings['enable_conference_in_course_groups'] = 'Ŝalti konferencon en kursaj grupoj';
$strings['enable_global_conference_link'] = 'Ŝalti ligilon al la ĉefkonferenco en la hejmpaĝo';
$strings['disable_download_conference_link'] = 'Malŝalti elŝuton de konferenco';
$strings['big_blue_button_record_and_store'] = 'Registri kaj konservi sesiojn';
$strings['bbb_enable_conference_in_groups'] = 'Permesi konferencon en grupoj';
$strings['plugin_tool_bbb'] = 'Video';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Ne estas registraĵoj por la renkontaj sesioj';
$strings['NoRecording'] = 'Neniu registraĵo';
$strings['ClickToContinue'] = 'Klaki por daŭrigi';
$strings['NoGroup'] = 'Neniu grupo';
$strings['UrlMeetingToShare'] = 'URL por kunhavigi';
$strings['AdminView'] = 'Vido por administrantoj';
$strings['max_users_limit'] = 'Maksimuma limo de uzantoj';
$strings['max_users_limit_help'] = 'Agordi tion al la maksimuma nombro da uzantoj kiujn vi volas permesi po kurso aŭ sesio-kurso. Lasu malplenan aŭ agordu al 0 por malŝalti tiun limon.';
$strings['MaxXUsersWarning'] = 'Ĉi tiu konferenca ĉambro havas maksimuman nombron de %s samtempaj uzantoj.';
$strings['MaxXUsersReached'] = 'La limo de %s samtempaj uzantoj estas atingita por ĉi tiu konferenca ĉambro. Bonvolu atendi liberecon de unu sidloko aŭ komenciĝon de alia konferenco por aliĝi.';
$strings['MaxXUsersReachedManager'] = 'La limo de %s samtempaj uzantoj estas atingita por ĉi tiu konferenca ĉambro. Por pligrandigi tiun limon, bonvolu kontakti la platforman administranton.';
$strings['MaxUsersInConferenceRoom'] = 'Maksimuma nombro da samtempaj uzantoj en konferenca ĉambro';
$strings['global_conference_allow_roles'] = 'Ligilo al ĉefkonferenco videbla nur por ĉi tiuj uzantrolaj';
$strings['CreatedAt'] = 'Kreita je';
$strings['allow_regenerate_recording'] = 'Permesi regeneri registraĵon';
$strings['bbb_force_record_generation'] = 'Devigi generi registraĵon fine de la renkonto';
$strings['disable_course_settings'] = 'Malŝalti kursajn agordojn';
$strings['UpdateAllCourses'] = 'Ĝisdatigi ĉiujn kursojn';
$strings['UpdateAllCourseSettings'] = 'Ĝisdatigi ĉiujn kursajn agordojn';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Tio ĝisdatigos samtempe ĉiujn viajn kursajn agordojn.';
$strings['ThereIsNoVideoConferenceActive'] = 'Neniu videkonferenco estas aktiva nun';
$strings['RoomClosed'] = 'Ĉambro fermita';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Daŭro de kunveno (en minutoj)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Permesi al studentoj lanĉi konferencon en siaj grupoj.';
$strings['hide_conference_link'] = 'Kaŝi ligilon de konferenco en kursa ilo';
$strings['hide_conference_link_comment'] = 'Montri aŭ kaŝi blokon kun ligilo al la videkonferenco apud la aliĝbutono, por ke uzantoj povu kopii ĝin kaj alglui en alia fenestro de retumilo aŭ inviti aliajn. Aŭtentigo tamen estos necesa por aliri nepublikajn konferencon.';
$strings['delete_recordings_on_course_delete'] = 'Forigi registritojn kiam kurso forigas';
$strings['defaultVisibilityInCourseHomepage'] = 'Defaŭlta videbleco en hejmpaĝo de kurso';
$strings['ViewActivityDashboard'] = 'Vidi paĝon de agadoj';
