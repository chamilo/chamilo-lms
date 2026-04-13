<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Vaizdo konferencija';
$strings['plugin_comment'] = 'Pridėti vaizdo konferencijų kambarį Chamilo kurse naudojant BigBlueButton (BBB)';

$strings['Videoconference'] = 'Vaizdo konferencija';
$strings['MeetingOpened'] = 'Susitikimas atidarytas';
$strings['MeetingClosed'] = 'Susitikimas uždarytas';
$strings['MeetingClosedComment'] = 'Jei prašėte įrašyti sesijas, įrašas bus prieinamas žemiau esančiame sąraše, kai bus visiškai sugeneruotas.';
$strings['CloseMeeting'] = 'Uždaryti susitikimą';

$strings['VideoConferenceXCourseX'] = 'Vaizdo konferencija #%s kursas %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Vaizdo konferencija pridėta į kalendorių';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Vaizdo konferencija pridėta į nuorodų įrankį';

$strings['GoToTheVideoConference'] = 'Eiti į vaizdo konferenciją';

$strings['Records'] = 'Įrašas';
$strings['Meeting'] = 'Susitikimas';

$strings['ViewRecord'] = 'Peržiūrėti įrašą';
$strings['CopyToLinkTool'] = 'Kopijuoti į nuorodų įrankį';

$strings['EnterConference'] = 'Įeiti į vaizdo konferenciją';
$strings['RecordList'] = 'Įrašų sąrašas';
$strings['ServerIsNotRunning'] = 'Vaizdo konferencijų serveris neveikia';
$strings['ServerIsNotConfigured'] = 'Vaizdo konferencijų serveris nesukonfigūruotas';

$strings['XUsersOnLine'] = '%s vartotojas(as) prisijungęs(ai)';

$strings['host'] = 'BigBlueButton serveris';
$strings['host_help'] = 'Tai jūsų BigBlueButton serverio pavadinimas.
Gali būti localhost, IP adresas (pvz. http://192.168.13.54) arba domeno vardas (pvz. http://my.video.com).';

$strings['salt'] = 'BigBlueButton druska';
$strings['salt_help'] = 'Tai jūsų BigBlueButton serverio saugumo raktas, leidžiantis serveriui autentifikuoti Chamilo instaliaciją. Žiūrėkite BigBlueButton dokumentaciją, kad jį rastumėte. Pabandykite bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'Sveikinimo pranešimas';
$strings['enable_global_conference'] = 'Įjungti globalią konferenciją';
$strings['enable_global_conference_per_user'] = 'Įjungti globalią konferenciją kiekvienam vartotojui';
$strings['enable_conference_in_course_groups'] = 'Įjungti konferenciją kurso grupėse';
$strings['enable_global_conference_link'] = 'Įjungti nuorodą į globalią konferenciją pradžios puslapyje';
$strings['disable_download_conference_link'] = 'Išjungti konferencijos atsisiuntimą';
$strings['big_blue_button_record_and_store'] = 'Įrašinėti ir saugoti sesijas';
$strings['bbb_enable_conference_in_groups'] = 'Leisti konferenciją grupėse';
$strings['plugin_tool_bbb'] = 'Vaizdas';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Susitikimo sesijoms nėra įrašų';
$strings['NoRecording'] = 'Nėra įrašo';
$strings['ClickToContinue'] = 'Spustelėkite tęsti';
$strings['NoGroup'] = 'Nėra grupės';
$strings['UrlMeetingToShare'] = 'URL dalintis';
$strings['AdminView'] = 'Administratorių peržiūra';
$strings['max_users_limit'] = 'Maksimalus vartotojų skaičius';
$strings['max_users_limit_help'] = 'Nustatykite maksimalų leidžiamų vartotojų skaičių kursui ar sesijos-kursui. Palikite tuščią arba nustatykite 0, kad išjungtumėte ribą.';
$strings['MaxXUsersWarning'] = 'Ši konferencijų salė turi maksimalų %s vienu metu prisijungusių vartotojų skaičių.';
$strings['MaxXUsersReached'] = 'Ši konferencijų salė pasiekė %s vienu metu prisijungusių vartotojų ribą. Palaukite, kol atsibus vieta, arba pradėkite kitą konferenciją prisijungimui.';
$strings['MaxXUsersReachedManager'] = 'Ši konferencijų salė pasiekė %s vienu metu prisijungusių vartotojų ribą. Norėdami padidinti ribą, kreipkitės į platformos administratorių.';
$strings['MaxUsersInConferenceRoom'] = 'Maksimalus vienu metu prisijungusių vartotojų skaičius konferencijų salėje';
$strings['global_conference_allow_roles'] = 'Globalios konferencijos nuoroda matoma tik šiems vartotojų vaidmenims';
$strings['CreatedAt'] = 'Sukurta';
$strings['allow_regenerate_recording'] = 'Leisti regeneruoti įrašą';
$strings['bbb_force_record_generation'] = 'Priverstinai generuoti įrašą susitikimo pabaigoje';
$strings['disable_course_settings'] = 'Išjungti kurso nustatymus';
$strings['UpdateAllCourses'] = 'Atnaujinti visus kursus';
$strings['UpdateAllCourseSettings'] = 'Atnaujinti visus kurso nustatymus';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Tai atnaujins visus jūsų kurso nustatymus vienu metu.';
$strings['ThereIsNoVideoConferenceActive'] = 'Šiuo metu nėra aktyvios vaizdo konferencijos';
$strings['RoomClosed'] = 'Kambarys uždarytas';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Susitikimo trukmė (minutėmis)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Leisti studentams pradėti konferenciją savo grupėse.';
$strings['hide_conference_link'] = 'Slėpti konferencijos nuorodą kurso įrankyje';
$strings['hide_conference_link_comment'] = 'Rodyti arba slėpti bloką su vaizdo konferencijos nuoroda šalia prisijungimo mygtuko, kad vartotojai galėtų ją nukopijuoti ir įklijuoti kitame naršyklės lange arba pakviesti kitus. Prieiga prie ne viešų konferencijų vis tiek reikalaus autentifikacijos.';
$strings['delete_recordings_on_course_delete'] = 'Ištrinti įrašus pašalinus kursą';
$strings['defaultVisibilityInCourseHomepage'] = 'Numatytasis matomumas kurso pradžios puslapyje';
$strings['ViewActivityDashboard'] = 'Peržiūrėti veiklos skydelį';
$strings['Participants'] = 'Dalyviai';
$strings['CountUsers'] = 'Skaičiuoti vartotojus';
