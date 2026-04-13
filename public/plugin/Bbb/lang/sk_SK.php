<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Videokonferencia';
$strings['plugin_comment'] = 'Pridať videokonferenčnú miestnosť do kurzu Chamilo pomocou BigBlueButton (BBB)';

$strings['Videoconference'] = 'Videokonferencia';
$strings['MeetingOpened'] = 'Stretnutie otvorené';
$strings['MeetingClosed'] = 'Stretnutie zatvorené';
$strings['MeetingClosedComment'] = 'Ak ste požiadali o nahrávanie vašich stretnutí, nahrávka bude dostupná v zozname nižšie, keď bude úplne vygenerovaná.';
$strings['CloseMeeting'] = 'Zatvoriť stretnutie';

$strings['VideoConferenceXCourseX'] = 'Videokonferencia #%s kurz %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Videokonferencia pridaná do kalendára';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Videokonferencia pridaná do nástroja odkazy';

$strings['GoToTheVideoConference'] = 'Prejsť na videokonferenciu';

$strings['Records'] = 'Nahrávka';
$strings['Meeting'] = 'Stretnutie';

$strings['ViewRecord'] = 'Prehliadať nahrávku';
$strings['CopyToLinkTool'] = 'Kopírovať do nástroja odkazy';

$strings['EnterConference'] = 'Vstúpiť do videokonferencie';
$strings['RecordList'] = 'Zoznam nahráвок';
$strings['ServerIsNotRunning'] = 'Videokonferenčný server nefunguje';
$strings['ServerIsNotConfigured'] = 'Videokonferenčný server nie je nakonfigurovaný';

$strings['XUsersOnLine'] = '%s používateľ(ov) online';

$strings['host'] = 'BigBlueButton hostiteľ';
$strings['host_help'] = 'Toto je názov servera, na ktorom beží váš BigBlueButton server.
Môže to byť localhost, IP adresa (napr. http://192.168.13.54) alebo doménové meno (napr. http://my.video.com).';

$strings['salt'] = 'BigBlueButton salt';
$strings['salt_help'] = 'Toto je bezpečnostný kľúč vášho BigBlueButton servera, ktorý umožní vášmu serveru overiť inštaláciu Chamilo. Pozrite dokumentáciu BigBlueButton na jeho lokalizáciu. Skúste bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'Prílovná správa';
$strings['enable_global_conference'] = 'Povoliť globálnu konferenciu';
$strings['enable_global_conference_per_user'] = 'Povoliť globálnu konferenciu pre používateľa';
$strings['enable_conference_in_course_groups'] = 'Povoliť konferenciu v kurzoch skupín';
$strings['enable_global_conference_link'] = 'Povoliť odkaz na globálnu konferenciu na domovskej stránke';
$strings['disable_download_conference_link'] = 'Zakázať sťahovanie konferencie';
$strings['big_blue_button_record_and_store'] = 'Nahrávať a ukladať stretnutia';
$strings['bbb_enable_conference_in_groups'] = 'Povoliť konferenciu v skupinách';
$strings['plugin_tool_bbb'] = 'Video';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Pre stretnutia nie sú žiadne nahrávky';
$strings['NoRecording'] = 'Žiadna nahrávka';
$strings['ClickToContinue'] = 'Kliknite pre pokračovanie';
$strings['NoGroup'] = 'Žiadna skupina';
$strings['UrlMeetingToShare'] = 'URL na zdieľanie';
$strings['AdminView'] = 'Pohľad pre správcov';
$strings['max_users_limit'] = 'Limit maximálneho počtu používateľov';
$strings['max_users_limit_help'] = 'Nastavte na maximálny počet používateľov, ktorým chcete povoliť prístup v kurze alebo relácii-kurze. Nechajte prázdne alebo nastavte na 0 na vypnutie tohto limitu.';
$strings['MaxXUsersWarning'] = 'Táto konferenčná miestnosť má maximálny počet %s simultánnych používateľov.';
$strings['MaxXUsersReached'] = 'Pre túto konferenčnú miestnosť bol dosiahnutý limit %s simultánnych používateľov. Počkajte, kým sa uvoľní jedno miesto alebo sa spustí iná konferencia, aby ste sa mohli pripojiť.';
$strings['MaxXUsersReachedManager'] = 'Pre túto konferenčnú miestnosť bol dosiahnutý limit %s simultánnych používateľov. Na zvýšenie tohto limitu kontaktujte správcu platformy.';
$strings['MaxUsersInConferenceRoom'] = 'Maximálny počet simultánnych používateľov v konferenčnej miestnosti';
$strings['global_conference_allow_roles'] = 'Odkaz na globálnu konferenciu viditeľný iba pre tieto role používateľov';
$strings['CreatedAt'] = 'Vytvorené';
$strings['allow_regenerate_recording'] = 'Povoliť regeneráciu nahrávky';
$strings['bbb_force_record_generation'] = 'Vynútiť generovanie nahrávky na konci stretnutia';
$strings['disable_course_settings'] = 'Zakázať nastavenia kurzu';
$strings['UpdateAllCourses'] = 'Aktualizovať všetky kurzy';
$strings['UpdateAllCourseSettings'] = 'Aktualizovať všetky nastavenia kurzu';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Toto aktualizuje naraz všetky nastavenia vášho kurzu.';
$strings['ThereIsNoVideoConferenceActive'] = 'Momentálne nie je aktívna žiadna videokonferencia';
$strings['RoomClosed'] = 'Miestnosť zatvorená';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Trvanie stretnutia (v minútach)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Povoliť študentom spustiť konferenciu v ich skupinách.';
$strings['hide_conference_link'] = 'Skryť odkaz na konferenciu v nástroji kurzu';
$strings['hide_conference_link_comment'] = 'Zobraziť alebo skryť blok s odkazom na videokonferenciu vedľa tlačidla pripojiť sa, aby používatelia mohli odkaz skopírovať a vložiť do iného okna prehliadača alebo pozvať ostatných. Overenie identity bude stále potrebné na prístup k neverejným konferenciám.';
$strings['delete_recordings_on_course_delete'] = 'Odstrániť nahrávky pri odstránení kurzu';
$strings['defaultVisibilityInCourseHomepage'] = 'Predvolená viditeľnosť na úvodnej stránke kurzu';
$strings['ViewActivityDashboard'] = 'Zobraziť dashboard aktivity';
$strings['Participants'] = 'Účastníci';
$strings['CountUsers'] = 'Počítať používateľov';
