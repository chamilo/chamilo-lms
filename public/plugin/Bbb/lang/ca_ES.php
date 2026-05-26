<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Videoconferència';
$strings['plugin_comment'] = 'Afegeix una sala de videoconferència en un curs Chamilo utilitzant BigBlueButton (BBB)';

$strings['Videoconference'] = 'Videoconferència';
$strings['MeetingOpened'] = 'Reunió oberta';
$strings['MeetingClosed'] = 'Reunió tancada';
$strings['MeetingClosedComment'] = "Si has demanat que les teves sessions es gravin, la gravació estarà disponible a la llista inferior quan s'hagi generat completament.";
$strings['CloseMeeting'] = 'Tancar reunió';

$strings['VideoConferenceXCourseX'] = 'Videoconferència #%s curs %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Videoconferència afegida al calendari';
$strings['VideoConferenceAddedToTheLinkTool'] = "Videoconferència afegida a l'eina d'enllaços";

$strings['GoToTheVideoConference'] = 'Anar a la videoconferència';

$strings['Records'] = 'Gravació';
$strings['Meeting'] = 'Reunió';

$strings['ViewRecord'] = 'Veure gravació';
$strings['CopyToLinkTool'] = "Copiar a l'eina d'enllaços";

$strings['EnterConference'] = 'Entrar a la videoconferència';
$strings['RecordList'] = 'Llista de gravacions';
$strings['ServerIsNotRunning'] = 'El servidor de videoconferència no està en execució';
$strings['ServerIsNotConfigured'] = 'El servidor de videoconferència no està configurat';

$strings['XUsersOnLine'] = '%s usuari(s) en línia';

$strings['host'] = 'Amfitrió BigBlueButton';
$strings['host_help'] = "Aquest és el nom del servidor on s'executa el teu servidor BigBlueButton.\nPot ser localhost, una adreça IP (p. ex. http://192.168.13.54) o un nom de domini (p. ex. http://my.video.com).";

$strings['salt'] = 'Salg BigBlueButton';
$strings['salt_help'] = 'Aquesta és la clau de seguretat del teu servidor BigBlueButton, que permetrà al teu servidor autenticar la instal·lació Chamilo. Consulta la documentació de BigBlueButton per localitzar-la. Prova bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'Missatge de benvinguda';
$strings['enable_global_conference'] = 'Activar conferència global';
$strings['enable_global_conference_per_user'] = 'Activar conferència global per usuari';
$strings['enable_conference_in_course_groups'] = 'Activar conferència en grups de curs';
$strings['enable_global_conference_link'] = "Activar l'enllaç a la conferència global a la pàgina principal";
$strings['disable_download_conference_link'] = 'Desactivar descàrrega de conferència';
$strings['big_blue_button_record_and_store'] = 'Gravar i emmagatzemar sessions';
$strings['bbb_enable_conference_in_groups'] = 'Permetre conferència en grups';
$strings['plugin_tool_bbb'] = 'Vídeo';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'No hi ha gravacions per a les sessions de la reunió';
$strings['No recording'] = 'Sense gravació';
$strings['ClickToContinue'] = 'Feu clic per continuar';
$strings['NoGroup'] = 'Sense grup';
$strings['UrlMeetingToShare'] = 'URL per compartir';
$strings['AdminView'] = 'Vista per a administradors';
$strings['max_users_limit'] = "Límit d'usuaris màxim";
$strings['max_users_limit_help'] = "Estableix això al nombre màxim d'usuaris que vulguis permetre per curs o sessió-curs. Deixa-ho en blanc o estableix-ho a 0 per desactivar aquest límit.";
$strings['MaxXUsersWarning'] = 'Aquesta sala de conferències té un nombre màxim de %s usuaris simultanis.';
$strings['MaxXUsersReached'] = "S'ha arribat al límit de %s usuaris simultanis per aquesta sala de conferències. Si us plau, espera que s'alliberi un lloc o que comenci una altra conferència per unir-t'hi.";
$strings['MaxXUsersReachedManager'] = "S'ha arribat al límit de %s usuaris simultanis per aquesta sala de conferències. Per augmentar aquest límit, contacta amb l'administrador de la plataforma.";
$strings['MaxUsersInConferenceRoom'] = 'Usuaris simultanis màxims en una sala de conferències';
$strings['global_conference_allow_roles'] = "Enllaç de conferència global visible només per aquests rols d'usuari";
$strings['CreatedAt'] = 'Creat a';
$strings['allow_regenerate_recording'] = 'Permetre regenerar gravació';
$strings['bbb_force_record_generation'] = 'Forçar la generació de gravació al final de la reunió';
$strings['disable_course_settings'] = 'Desactivar configuracions del curs';
$strings['UpdateAllCourses'] = 'Actualitza tots els cursos';
$strings['UpdateAllCourseSettings'] = 'Actualitza tots els ajustos del curs';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Això actualitzarà de cop tots els ajustos del vostre curs.';
$strings['ThereIsNoVideoConferenceActive'] = 'No hi ha cap videoconferència activa actualment';
$strings['RoomClosed'] = 'Sala tancada';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Durada de la reunió (en minuts)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Permet als estudiants iniciar la conferència en els seus grups.';
$strings['hide_conference_link'] = "Amaga l'enllaç de la conferència a l'eina del curs";
$strings['hide_conference_link_comment'] = "Mostra o amaga un bloc amb un enllaç a la videoconferència al costat del botó d'unir-se, per permetre als usuaris copiar-lo i enganxar-lo en una altra finestra del navegador o convidar altres. L'autenticació encara serà necessària per accedir a conferències no públiques.";
$strings['delete_recordings_on_course_delete'] = "Esborra les gravacions quan s'elimini el curs";
$strings['defaultVisibilityInCourseHomepage'] = 'Visibilitat per defecte a la pàgina principal del curs';
$strings['ViewActivityDashboard'] = "Veure el tauler d'activitat";
$strings['Participants'] = 'Participants';
$strings['CountUsers'] = 'Comptar usuaris';
