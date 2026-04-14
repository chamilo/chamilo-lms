<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Videokonference';
$strings['plugin_comment'] = 'Pievienot videokonferenču telpu Chamilo kursā, izmantojot BigBlueButton (BBB)';

$strings['Videoconference'] = 'Videokonference';
$strings['MeetingOpened'] = 'Tikšanās atvērta';
$strings['MeetingClosed'] = 'Tikšanās aizvērta';
$strings['MeetingClosedComment'] = 'Ja esat pieprasījis sesiju ierakstīšanu, ieraksts būs pieejams zemāk esošajā sarakstā, kad tas būs pilnībā ģenerēts.';
$strings['CloseMeeting'] = 'Aizvērt tikšanos';

$strings['VideoConferenceXCourseX'] = 'Videokonference #%s kurss %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Videokonference pievienota kalendāram';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Videokonference pievienota saites rīkam';

$strings['GoToTheVideoConference'] = 'Doties uz videokonferenci';

$strings['Records'] = 'Ieraksts';
$strings['Meeting'] = 'Tikšanās';

$strings['ViewRecord'] = 'Skatīt ierakstu';
$strings['CopyToLinkTool'] = 'Kopēt uz saites rīku';

$strings['EnterConference'] = 'Ienākt videokonferencē';
$strings['RecordList'] = 'Ierakstu saraksts';
$strings['ServerIsNotRunning'] = 'Videokonferenču serveris nedarbojas';
$strings['ServerIsNotConfigured'] = 'Videokonferenču serveris nav konfigurēts';

$strings['XUsersOnLine'] = '%s lietotājs(i) tiešsaistē';

$strings['host'] = 'BigBlueButton saimnieks';
$strings['host_help'] = 'Šī ir servera nosaukums, kur darbojas jūsu BigBlueButton serveris.
Var būt localhost, IP adrese (piem., http://192.168.13.54) vai domēna nosaukums (piem., http://my.video.com).';

$strings['salt'] = 'BigBlueButton sāls';
$strings['salt_help'] = 'Šī ir jūsu BigBlueButton servera drošības atslēga, kas ļaus jūsu serverim autentificēt Chamilo instalāciju. Skatiet BigBlueButton dokumentāciju, lai to atrastu. Mēģiniet bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'Sveiciena ziņojums';
$strings['enable_global_conference'] = 'Iespējot globālo konferenci';
$strings['enable_global_conference_per_user'] = 'Iespējot globālo konferenci katram lietotājam';
$strings['enable_conference_in_course_groups'] = 'Iespējot konferenci kursa grupās';
$strings['enable_global_conference_link'] = 'Iespējot saiti uz globālo konferenci sākumlapā';
$strings['disable_download_conference_link'] = 'Aizliegt konferenču lejupielādi';
$strings['big_blue_button_record_and_store'] = 'Ierakstīt un uzglabāt sesijas';
$strings['bbb_enable_conference_in_groups'] = 'Atļaut konferenci grupās';
$strings['plugin_tool_bbb'] = 'Video';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Tikšanās sesijām nav ierakstu';
$strings['No recording'] = 'Nav ieraksta';
$strings['ClickToContinue'] = 'Noklikšķiniet, lai turpinātu';
$strings['NoGroup'] = 'Nav grupas';
$strings['UrlMeetingToShare'] = 'Dalīšanās URL';
$strings['AdminView'] = 'Skats administratoriem';
$strings['max_users_limit'] = 'Maksimālais lietotāju skaits';
$strings['max_users_limit_help'] = 'Iestatiet uz maksimālo lietotāju skaitu, ko vēlaties atļaut kursā vai sesijas-kursā. Atstājiet tukšu vai iestatiet uz 0, lai atspējotu šo ierobežojumu.';
$strings['MaxXUsersWarning'] = 'Šai konferenču telpai ir maksimālais %s vienlaicīgo lietotāju skaits.';
$strings['MaxXUsersReached'] = 'Šai konferenču telpai ir sasniegts %s vienlaicīgo lietotāju ierobežojums. Lūdzu, pagaidiet, kamēr atbrīvojas viena vieta vai sākas cita konference, lai pievienotos.';
$strings['MaxXUsersReachedManager'] = 'Šai konferenču telpai ir sasniegts %s vienlaicīgo lietotāju ierobežojums. Lai palielinātu šo ierobežojumu, lūdzu, sazinieties ar platformas administratoru.';
$strings['MaxUsersInConferenceRoom'] = 'Maksimālais vienlaicīgo lietotāju skaits konferenču telpā';
$strings['global_conference_allow_roles'] = 'Globālās konferences saite redzama tikai šīm lietotāju lomām';
$strings['CreatedAt'] = 'Izveidots';
$strings['allow_regenerate_recording'] = 'Atļaut ieraksta atkārtotu ģenerēšanu';
$strings['bbb_force_record_generation'] = 'Piespiedu kārtā ģenerēt ierakstu tikšanās beigās';
$strings['disable_course_settings'] = 'Atspējot kursa iestatījumus';
$strings['UpdateAllCourses'] = 'Atjaunināt visus kursus';
$strings['UpdateAllCourseSettings'] = 'Atjaunināt visus kursa iestatījumus';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Tas atjauninās visus jūsu kursa iestatījumus uzreiz.';
$strings['ThereIsNoVideoConferenceActive'] = 'Pašlaik nav aktīva videokonference';
$strings['RoomClosed'] = 'Telpa aizvērta';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Tikšanās ilgums (minūtēs)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Atļaut studentiem sākt konferenci savās grupās.';
$strings['hide_conference_link'] = 'Paslēpt konferences saiti kursa rīkā';
$strings['hide_conference_link_comment'] = 'Rādīt vai paslēpt bloku ar saiti uz videokonferenci pie pievienošanās pogas, lai lietotāji varētu to nokopēt un ielīmēt citā pārlūkprogrammas logā vai uzaicināt citus. Lai piekļūtu nepubliskām konferencēm, joprojām būs nepieciešama autentifikācija.';
$strings['delete_recordings_on_course_delete'] = 'Dzēst ierakstus, kad kurss tiek dzēsts';
$strings['defaultVisibilityInCourseHomepage'] = 'Noklusētā redzamība kursa sākumlappā';
$strings['ViewActivityDashboard'] = 'Skatīt aktivitātes paneli';
$strings['Participants'] = 'Dalībnieki';
$strings['CountUsers'] = 'Skaitīt lietotājus';
