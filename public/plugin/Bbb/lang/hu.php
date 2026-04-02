<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Videokonferencia';
$strings['plugin_comment'] = 'Videokonferencia terem hozzáadása egy Chamilo kurzushoz BigBlueButton (BBB) használatával';

$strings['Videoconference'] = 'Videokonferencia';
$strings['MeetingOpened'] = 'Találkozó megnyitva';
$strings['MeetingClosed'] = 'Találkozó bezárva';
$strings['MeetingClosedComment'] = 'Ha kértél felvételt a munkameneteidről, a felvétel akkor lesz elérhető az alábbi listában, ha teljesen elkészült.';
$strings['CloseMeeting'] = 'Találkozó bezárása';

$strings['VideoConferenceXCourseX'] = 'Videokonferencia #%s kurzus %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Videokonferencia hozzáadva a naptárhoz';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Videokonferencia hozzáadva a hivatkozás eszközhöz';

$strings['GoToTheVideoConference'] = 'Ugrás a videokonferenciához';

$strings['Records'] = 'Felvétel';
$strings['Meeting'] = 'Találkozó';

$strings['ViewRecord'] = 'Felvétel megtekintése';
$strings['CopyToLinkTool'] = 'Másolás a hivatkozás eszközbe';

$strings['EnterConference'] = 'Belépés a videokonferenciába';
$strings['RecordList'] = 'Felvétellista';
$strings['ServerIsNotRunning'] = 'A videokonferencia szerver nem fut';
$strings['ServerIsNotConfigured'] = 'A videokonferencia szerver nincs konfigurálva';

$strings['XUsersOnLine'] = '%s felhasználó(k) online';

$strings['host'] = 'BigBlueButton hoszt';
$strings['host_help'] = 'Ez a szerver neve, ahol a BigBlueButton szervered fut.
Lehet localhost, IP-cím (pl. http://192.168.13.54) vagy domain név (pl. http://my.video.com).';

$strings['salt'] = 'BigBlueButton só';
$strings['salt_help'] = 'Ez a BigBlueButton szervered biztonsági kulcsa, amely lehetővé teszi a szerver számára a Chamilo telepítés hitelesítését. Lásd a BigBlueButton dokumentációt annak megkereséséhez. Próbáld: bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'Üdvözlő üzenet';
$strings['enable_global_conference'] = 'Globális konferencia engedélyezése';
$strings['enable_global_conference_per_user'] = 'Globális konferencia engedélyezése felhasználónként';
$strings['enable_conference_in_course_groups'] = 'Konferencia engedélyezése kurzuscoportokban';
$strings['enable_global_conference_link'] = 'Globális konferencia hivatkozás megjelenítése a kezdőlapon';
$strings['disable_download_conference_link'] = 'Konferencia letöltés letiltása';
$strings['big_blue_button_record_and_store'] = 'Munkamenetek rögzítése és tárolása';
$strings['bbb_enable_conference_in_groups'] = 'Konferencia engedélyezése csoportokban';
$strings['plugin_tool_bbb'] = 'Videó';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Nincsenek felvételek a találkozó munkameneteihez';
$strings['NoRecording'] = 'Nincs felvétel';
$strings['ClickToContinue'] = 'Kattints a folytatáshoz';
$strings['NoGroup'] = 'Nincs csoport';
$strings['UrlMeetingToShare'] = 'Megosztási URL';
$strings['AdminView'] = 'Nézet rendszergazdáknak';
$strings['max_users_limit'] = 'Max. felhasználók limit';
$strings['max_users_limit_help'] = 'Állítsd be a maximális felhasználók számára kurzushoz vagy munkamenet-kurzushoz. Hagyjad üresen vagy állítsd 0-ra a limit kikapcsolásához.';
$strings['MaxXUsersWarning'] = 'Ez a konferenciaterem maximálisan %s egyidejű felhasználót támogat.';
$strings['MaxXUsersReached'] = 'Elérte a %s egyidejű felhasználó limitet ebben a konferenciateremben. Kérlek, várj, amíg felszabadul egy hely, vagy indul egy másik konferencia a csatlakozáshoz.';
$strings['MaxXUsersReachedManager'] = 'Elérte a %s egyidejű felhasználó limitet ebben a konferenciateremben. A limit növeléséhez lépj kapcsolatba a platform rendszergazdájával.';
$strings['MaxUsersInConferenceRoom'] = 'Max. egyidejű felhasználók egy konferenciateremben';
$strings['global_conference_allow_roles'] = 'Globális konferencia hivatkozás csak ezeknek a felhasználói szerepköröknek látható';
$strings['CreatedAt'] = 'Létrehozva';
$strings['allow_regenerate_recording'] = 'Felvétel újragenerálásának engedélyezése';
$strings['bbb_force_record_generation'] = 'Felvétel generálásának kényszerítése a találkozó végén';
$strings['disable_course_settings'] = 'Kurzusbeállítások letiltása';
$strings['UpdateAllCourses'] = 'Összes kurzus frissítése';
$strings['UpdateAllCourseSettings'] = 'Összes kurzuskörnyezet frissítése';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Ez egyszerre frissíti az összes kurzuskörnyezetét.';
$strings['ThereIsNoVideoConferenceActive'] = 'Jelenleg nincs aktív videokonferencia';
$strings['RoomClosed'] = 'Szoba lezárva';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Találkozó időtartama (percben)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Lehetővé tenni a diákok számára, hogy csoportjaikban indítsák a konferenciát.';
$strings['hide_conference_link'] = 'Konferencia hivatkozás elrejtése a kurzuseszközben';
$strings['hide_conference_link_comment'] = 'Blokk megjelenítése vagy elrejtése a csatlakozás gomb melletti videokonferencia hivatkozással, hogy a felhasználók másolhassák és beilleszthessék másik böngészőablakba vagy meghívhassanak másokat. A hitelesítés továbbra is szükséges a nem nyilvános konferenciákhoz.';
$strings['delete_recordings_on_course_delete'] = 'Felvételek törlése, ha a kurzust eltávolítják';
$strings['defaultVisibilityInCourseHomepage'] = 'Alapértelmezett láthatóság a kurzus főoldalon';
$strings['ViewActivityDashboard'] = 'Tevékenység dashboard megtekintése';
