<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Videokonference';
$strings['plugin_comment'] = 'Tilføj et videokonferencerum i en Chamilo-kursus ved hjælp af BigBlueButton (BBB)';

$strings['Videoconference'] = 'Videokonference';
$strings['MeetingOpened'] = 'Møde åbnet';
$strings['MeetingClosed'] = 'Møde lukket';
$strings['MeetingClosedComment'] = 'Hvis du har bedt om, at dine sessioner optages, vil optagelsen være tilgængelig i listen nedenfor, når den er fuldt genereret.';
$strings['CloseMeeting'] = 'Luk møde';

$strings['VideoConferenceXCourseX'] = 'Videokonference #%s kursus %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Videokonference tilføjet til kalenderen';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Videokonference tilføjet til link-værktøjet';

$strings['GoToTheVideoConference'] = 'Gå til videokonferencen';

$strings['Records'] = 'Optagelse';
$strings['Meeting'] = 'Møde';

$strings['ViewRecord'] = 'Vis optagelse';
$strings['CopyToLinkTool'] = 'Kopiér til link-værktøj';

$strings['EnterConference'] = 'Indgå i videokonferencen';
$strings['RecordList'] = 'Optagelsesliste';
$strings['ServerIsNotRunning'] = 'Videokonference-server kører ikke';
$strings['ServerIsNotConfigured'] = 'Videokonference-server er ikke konfigureret';

$strings['XUsersOnLine'] = '%s bruger(e) online';

$strings['host'] = 'BigBlueButton-vært';
$strings['host_help'] = 'Dette er navnet på serveren, hvor din BigBlueButton-server kører.
Kan være localhost, en IP-adresse (f.eks. http://192.168.13.54) eller et domænenavn (f.eks. http://my.video.com).';

$strings['salt'] = 'BigBlueButton-salt';
$strings['salt_help'] = 'Dette er sikkerhedsnøglen til din BigBlueButton-server, som tillader din server at autentificere Chamilo-installationen. Se BigBlueButton-dokumentationen for at finde den. Prøv bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'Velkomstbesked';
$strings['enable_global_conference'] = 'Aktivér global konferencelokale';
$strings['enable_global_conference_per_user'] = 'Aktivér global konferencelokale pr. bruger';
$strings['enable_conference_in_course_groups'] = 'Aktivér konferencelokale i kursusgrupper';
$strings['enable_global_conference_link'] = 'Aktivér link til globalt konferencelokale på forsiden';
$strings['disable_download_conference_link'] = 'Deaktivér download af konferencelokale';
$strings['big_blue_button_record_and_store'] = 'Optag og gem sessioner';
$strings['bbb_enable_conference_in_groups'] = 'Tillad konferencelokale i grupper';
$strings['plugin_tool_bbb'] = 'Video';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Der er ingen optagelser for mødesessionerne';
$strings['NoRecording'] = 'Ingen optagelse';
$strings['ClickToContinue'] = 'Klik for at fortsætte';
$strings['NoGroup'] = 'Ingen gruppe';
$strings['UrlMeetingToShare'] = 'URL til deling';
$strings['AdminView'] = 'Visning for administratorer';
$strings['max_users_limit'] = 'Maks. brugergrænse';
$strings['max_users_limit_help'] = 'Indstil dette til det maksimale antal brugere, du vil tillade pr. kursus eller session-kursus. Efterlad tomt eller sæt til 0 for at deaktivere grænsen.';
$strings['MaxXUsersWarning'] = 'Dette konferencelokale har et maksimum på %s samtidige brugere.';
$strings['MaxXUsersReached'] = 'Grænsen på %s samtidige brugere er nået for dette konferencelokale. Vent venligst på, at en plads bliver ledig, eller på at et andet konferencelokale starter, for at kunne deltage.';
$strings['MaxXUsersReachedManager'] = 'Grænsen på %s samtidige brugere er nået for dette konferencelokale. Kontakt platformadministratoren for at øge grænsen.';
$strings['MaxUsersInConferenceRoom'] = 'Maks. samtidige brugere i et konferencelokale';
$strings['global_conference_allow_roles'] = 'Global konferencelokale-link kun synligt for disse brugerroller';
$strings['CreatedAt'] = 'Oprettet den';
$strings['allow_regenerate_recording'] = 'Tillad regenerering af optagelse';
$strings['bbb_force_record_generation'] = 'Tving optagelsesgenerering ved mødets afslutning';
$strings['disable_course_settings'] = 'Deaktivér kursusindstillinger';
$strings['UpdateAllCourses'] = 'Opdater alle kurser';
$strings['UpdateAllCourseSettings'] = 'Opdater alle kursusindstillinger';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Dette vil opdatere alle dine kursusindstillinger på én gang.';
$strings['ThereIsNoVideoConferenceActive'] = 'Der er ingen videokonference aktiv lige nu';
$strings['RoomClosed'] = 'Rum lukket';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Mødevarighed (i minutter)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Tillad elever at starte konferencen i deres grupper.';
$strings['hide_conference_link'] = 'Skjul konferencelinken i kursusværktøjet';
$strings['hide_conference_link_comment'] = 'Vis eller skjul en blok med en link til videokonferencen ved siden af tilmeldingsknappen, så brugere kan kopiere den og indsætte den i et andet browservindue eller invitere andre. Godkendelse er stadig nødvendig for at få adgang til ikke-offentlige konferencer.';
$strings['delete_recordings_on_course_delete'] = 'Slet optagelser når kurset fjernes';
$strings['defaultVisibilityInCourseHomepage'] = 'Standard synlighed på kursushjemmesiden';
$strings['ViewActivityDashboard'] = 'Vis aktivitetsdashboard';
$strings['Participants'] = 'Deltagere';
$strings['CountUsers'] = 'Tæl brugere';
