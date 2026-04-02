<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Videokonferens';
$strings['plugin_comment'] = 'Lägg till ett videokonferensrum i en Chamilo-kurs med BigBlueButton (BBB)';

$strings['Videoconference'] = 'Videokonferens';
$strings['MeetingOpened'] = 'Möte öppnat';
$strings['MeetingClosed'] = 'Möte stängt';
$strings['MeetingClosedComment'] = 'Om du har begärt att dina sessioner ska spelas in kommer inspelningen att finnas tillgänglig i listan nedan när den har genererats helt.';
$strings['CloseMeeting'] = 'Stäng möte';

$strings['VideoConferenceXCourseX'] = 'Videokonferens #%s kurs %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Videokonferens tillagd i kalendern';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Videokonferens tillagd i länkverktyget';

$strings['GoToTheVideoConference'] = 'Gå till videokonferensen';

$strings['Records'] = 'Inspelning';
$strings['Meeting'] = 'Möte';

$strings['ViewRecord'] = 'Visa inspelning';
$strings['CopyToLinkTool'] = 'Kopiera till länkverktyget';

$strings['EnterConference'] = 'Gå in i videokonferensen';
$strings['RecordList'] = 'Inspelningslista';
$strings['ServerIsNotRunning'] = 'Videokonferensservern körs inte';
$strings['ServerIsNotConfigured'] = 'Videokonferensservern är inte konfigurerad';

$strings['XUsersOnLine'] = '%s användare(a) online';

$strings['host'] = 'BigBlueButton-värd';
$strings['host_help'] = 'Detta är namnet på servern där din BigBlueButton-server körs.
Kan vara localhost, en IP-adress (t.ex. http://192.168.13.54) eller ett domännamn (t.ex. http://my.video.com).';

$strings['salt'] = 'BigBlueButton-salt';
$strings['salt_help'] = 'Detta är säkerhetsnyckeln för din BigBlueButton-server, som låter servern autentisera Chamilo-installationen. Se BigBlueButton-dokumentationen för att hitta den. Prova bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'Välkomstmeddelande';
$strings['enable_global_conference'] = 'Aktivera global konferens';
$strings['enable_global_conference_per_user'] = 'Aktivera global konferens per användare';
$strings['enable_conference_in_course_groups'] = 'Aktivera konferens i kursgrupper';
$strings['enable_global_conference_link'] = 'Aktivera länken till den globala konferensen på startsidan';
$strings['disable_download_conference_link'] = 'Inaktivera nedladdning av konferens';
$strings['big_blue_button_record_and_store'] = 'Spela in och spara sessioner';
$strings['bbb_enable_conference_in_groups'] = 'Tillåt konferens i grupper';
$strings['plugin_tool_bbb'] = 'Video';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Det finns inga inspelningar för mötesessionerna';
$strings['NoRecording'] = 'Ingen inspelning';
$strings['ClickToContinue'] = 'Klicka för att fortsätta';
$strings['NoGroup'] = 'Ingen grupp';
$strings['UrlMeetingToShare'] = 'URL att dela';
$strings['AdminView'] = 'Visa för administratörer';
$strings['max_users_limit'] = 'Maxgräns för användare';
$strings['max_users_limit_help'] = 'Ange det maximala antalet användare du vill tillåta per kurs eller session-kurs. Lämna tomt eller ange 0 för att inaktivera gränsen.';
$strings['MaxXUsersWarning'] = 'Detta konferensrum har ett maximum på %s simultana användare.';
$strings['MaxXUsersReached'] = 'Gränsen på %s simultana användare har uppnåtts för detta konferensrum. Vänta på att en plats blir ledig eller att en annan konferens startar för att kunna gå med.';
$strings['MaxXUsersReachedManager'] = 'Gränsen på %s simultana användare har uppnåtts för detta konferensrum. Kontakta plattformsadministratören för att öka gränsen.';
$strings['MaxUsersInConferenceRoom'] = 'Max simultana användare i ett konferensrum';
$strings['global_conference_allow_roles'] = 'Global konferenslänk synlig endast för dessa användarroller';
$strings['CreatedAt'] = 'Skapad';
$strings['allow_regenerate_recording'] = 'Tillåt återskapande av inspelning';
$strings['bbb_force_record_generation'] = 'Tvinga inspelningsgenerering vid mötesavslut';
$strings['disable_course_settings'] = 'Inaktivera kursinställningar';
$strings['UpdateAllCourses'] = 'Uppdatera alla kurser';
$strings['UpdateAllCourseSettings'] = 'Uppdatera alla kursinställningar';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Detta uppdaterar alla dina kursinställningar på en gång.';
$strings['ThereIsNoVideoConferenceActive'] = 'Ingen videokonferens är för närvarande aktiv';
$strings['RoomClosed'] = 'Rum stängt';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Möteslängd (i minuter)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Tillåt studenter att starta konferens i sina grupper.';
$strings['hide_conference_link'] = 'Dölj konferenslänk i kursverktyg';
$strings['hide_conference_link_comment'] = 'Visa eller dölj ett block med en länk till videokonferensen bredvid anslutningsknappen, så att användare kan kopiera den och klistra in den i ett annat webbläsarfönster eller bjuda in andra. Autentisering krävs fortfarande för att komma åt icke-offentliga konferenser.';
$strings['delete_recordings_on_course_delete'] = 'Ta bort inspelningar när kursen tas bort';
$strings['defaultVisibilityInCourseHomepage'] = 'Standardvisning på kursens startsida';
$strings['ViewActivityDashboard'] = 'Visa aktivitetsinstrumentpanel';
