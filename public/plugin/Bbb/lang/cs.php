<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Videokonference';
$strings['plugin_comment'] = 'Přidat videoconferenční místnost do kurzu Chamilo pomocí BigBlueButton (BBB)';

$strings['Videoconference'] = 'Videokonference';
$strings['MeetingOpened'] = 'Schůzka otevřena';
$strings['MeetingClosed'] = 'Schůzka uzavřena';
$strings['MeetingClosedComment'] = 'Pokud jste požádali o nahrávání svých sezení, nahrávka bude dostupná v seznamu níže, jakmile bude zcela vygenerována.';
$strings['CloseMeeting'] = 'Uzavřít schůzku';

$strings['VideoConferenceXCourseX'] = 'Videokonference #%s kurz %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Videokonference přidána do kalendáře';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Videokonference přidána do nástroje odkazy';

$strings['GoToTheVideoConference'] = 'Jít na videokonferenci';

$strings['Records'] = 'Nahrávka';
$strings['Meeting'] = 'Schůzka';

$strings['ViewRecord'] = 'Zobrazit nahrávku';
$strings['CopyToLinkTool'] = 'Kopírovat do nástroje odkazy';

$strings['EnterConference'] = 'Vstoupit do videokonference';
$strings['RecordList'] = 'Seznam nahrávek';
$strings['ServerIsNotRunning'] = 'Videokonferenční server neběží';
$strings['ServerIsNotConfigured'] = 'Videokonferenční server není nakonfigurován';

$strings['XUsersOnLine'] = '%s uživatel(ů) online';

$strings['host'] = 'BigBlueButton hostitel';
$strings['host_help'] = 'Toto je název serveru, na kterém běží váš BigBlueButton server.
Může to být localhost, IP adresa (např. http://192.168.13.54) nebo doménové jméno (např. http://my.video.com).';

$strings['salt'] = 'BigBlueButton salt';
$strings['salt_help'] = 'Toto je bezpečnostní klíč vašeho BigBlueButton serveru, který umožní vašemu serveru ověřit instalaci Chamilo. Viz dokumentace BigBlueButton pro jeho nalezení. Zkuste bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'Přivítací zpráva';
$strings['enable_global_conference'] = 'Povolit globální konferenci';
$strings['enable_global_conference_per_user'] = 'Povolit globální konferenci pro uživatele';
$strings['enable_conference_in_course_groups'] = 'Povolit konferenci v kurzech skupin';
$strings['enable_global_conference_link'] = 'Povolit odkaz na globální konferenci na domovské stránce';
$strings['disable_download_conference_link'] = 'Zakázat stahování konference';
$strings['big_blue_button_record_and_store'] = 'Nahrávat a ukládat sezení';
$strings['bbb_enable_conference_in_groups'] = 'Povolit konferenci ve skupinách';
$strings['plugin_tool_bbb'] = 'Video';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Neexistují nahrávky pro sezení schůzky';
$strings['NoRecording'] = 'Žádná nahrávka';
$strings['ClickToContinue'] = 'Klikněte pro pokračování';
$strings['NoGroup'] = 'Žádná skupina';
$strings['UrlMeetingToShare'] = 'URL k sdílení';
$strings['AdminView'] = 'Zobrazení pro administrátory';
$strings['max_users_limit'] = 'Limit maximálního počtu uživatelů';
$strings['max_users_limit_help'] = 'Nastavte na maximální počet uživatelů, které chcete povolit pro kurz nebo sezení-kurz. Nechte prázdné nebo nastavte na 0 pro vypnutí tohoto limitu.';
$strings['MaxXUsersWarning'] = 'Tato konferenční místnost má maximální počet %s souběžných uživatelů.';
$strings['MaxXUsersReached'] = 'Pro tuto konferenční místnost byl dosažen limit %s souběžných uživatelů. Počkejte, až se uvolní jedno místo, nebo až začne jiná konference, abyste se mohli připojit.';
$strings['MaxXUsersReachedManager'] = 'Pro tuto konferenční místnost byl dosažen limit %s souběžných uživatelů. Pro zvýšení limitu kontaktujte prosím správce platformy.';
$strings['MaxUsersInConferenceRoom'] = 'Max souběžných uživatelů v konferenční místnosti';
$strings['global_conference_allow_roles'] = 'Odkaz na globální konferenci viditelný pouze pro tyto role uživatelů';
$strings['CreatedAt'] = 'Vytvořeno';
$strings['allow_regenerate_recording'] = 'Povolit regeneraci nahrávky';
$strings['bbb_force_record_generation'] = 'Vynutit generování nahrávky na konci schůzky';
$strings['disable_course_settings'] = 'Zakázat nastavení kurzu';
$strings['UpdateAllCourses'] = 'Aktualizovat všechny kurzy';
$strings['UpdateAllCourseSettings'] = 'Aktualizovat nastavení všech kurzů';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Tím se najednou aktualizují všechna nastavení vašeho kurzu.';
$strings['ThereIsNoVideoConferenceActive'] = 'V tuto chvíli není aktivní žádná videokonference';
$strings['RoomClosed'] = 'Místnost uzavřena';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Délka schůzky (v minutách)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Povolit studentům zahájit konferenci ve svých skupinách.';
$strings['hide_conference_link'] = 'Skrýt odkaz na konferenci v nástroji kurzu';
$strings['hide_conference_link_comment'] = 'Zobrazit nebo skrýt blok s odkazem na videokonferenci vedle tlačítka pro připojení, aby uživatelé mohli odkaz zkopírovat a vložit do jiného okna prohlížeče nebo pozvat ostatní. Pro přístup k neveřejným konferencím bude stále nutné ověření.';
$strings['delete_recordings_on_course_delete'] = 'Smazat nahrávky při odstranění kurzu';
$strings['defaultVisibilityInCourseHomepage'] = 'Výchozí viditelnost na úvodní stránce kurzu';
$strings['ViewActivityDashboard'] = 'Zobrazit nástrojnicu aktivity';
