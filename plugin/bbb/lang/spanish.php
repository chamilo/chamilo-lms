<?php
/* License: see /license.txt */
//Needed in order to show the plugin title
$strings['plugin_title'] = "Videoconferencia";
$strings['plugin_comment'] = "Añade una sala de videoconferencia en los cursos de Chamilo con BigBlueButton (BBB)";

$strings['Videoconference'] = "Videoconferencia";
$strings['MeetingOpened'] = "Sala abierta";
$strings['MeetingClosed'] = "Sala cerrada";
$strings['MeetingClosedComment'] = "Si ha pedido grabar la sesión de videoconferencia en los parámetros del curso, esta grabación aparecerá en la lista siguiente una vez generada.";
$strings['CloseMeeting'] = "Cerrar sala";

$strings['VideoConferenceXCourseX'] = "Videoconferencia #%s, curso %s";
$strings['VideoConferenceAddedToTheCalendar'] = "Videoconferencia añadida al calendario";
$strings['VideoConferenceAddedToTheLinkTool'] = "Videoconferencia añadida como enlace. Puede editar y publicar el enlace en la página principal del curso desde la herramienta de enlace.";

$strings['GoToTheVideoConference'] = "Ir a la videoconferencia";

$strings['Records'] = "Grabación";
$strings['Meeting'] = "Sala de conferencia";

$strings['ViewRecord'] = "Ver grabación";
$strings['CopyToLinkTool'] = "Añadir como enlace del curso";

$strings['EnterConference'] = "Entrar a la videoconferencia";
$strings['RecordList'] = "Lista de grabaciones";
$strings['ServerIsNotRunning'] = "El servidor de videoconferencia no está funcionando";
$strings['ServerIsNotConfigured'] = "El servidor de videoconferencia no está configurado correctamente";

$strings['XUsersOnLine'] = "%s usuario(s) en la sala";

$strings['host'] = 'Host de BigBlueButton';
$strings['host_help'] = 'Este es el nombre del servidor donde su servidor BigBlueButton está corriendo. Puede ser localhost, una dirección IP (ej: 192.168.13.54) o un nombre de dominio (ej: mi.video.com).';

$strings['salt'] = 'Clave BigBlueButton';
$strings['salt_help'] = 'Esta es la llave de seguridad de su servidor BigBlueButton (llamada "salt" en inglés), que permitirá a su servidor de autentifica la instalación de Chamilo (como autorizada). Refiérese a la documentación de BigBlueButton para ubicarla, o use el comando "bbb-conf --salt" si tiene acceso al servidor en línea de comando.';

$strings['tool_enable'] = 'Herramienta de videoconferencia BigBlueButton activada';
$strings['tool_enable_help'] = "Escoja si desea activar la herramienta de videoconferencia BigBlueButton.
    Una vez activada, se mostrará como una herramienta adicional en todas las páginas principales de cursos, y los profesores podrán iniciar una conferencia en cualquier momento. Los estudiantes no podrían lanzar una conferencia, solo juntarse a una existente. Si no tiene un servidor de videoconferencia BigBlueButton, por favor <a target=\"_blank\" href=\"http://bigbluebutton.org/\">configure uno</a> antes de seguir, o pida una cotización a uno de los proveedores oficiales de Chamilo. BigBlueButton es una herramienta de software libre (y gratuita), pero su instalación requiere de competencias que quizás no sean inmediatamente disponibles para todos. Puede instalarla usted mismo o buscar ayuda profesional. Esta ayuda podría no obstante generar algunos costos (por lo menos el tiempo de la persona quien lo ayude). En el puro espíritu del software libre, le ofrecemos las herramientas para hacer su trabajo más simple, en la medida de nuestras posibilidades, y le recomendamos profesionales (los proveedores oficiales de Chamilo) para ayudarlo en caso esto fuera demasiado complicado.<br />";

$strings['big_blue_button_welcome_message'] = 'Mensaje de bienvenida de BigBlueButton';
$strings['big_blue_button_record_and_store'] = 'Grabar las sesiones de videoconferencia.';
$strings['bbb_enable_conference_in_groups'] = 'Activar la creación de videoconferencia en los grupos.';

$strings['plugin_tool_bbb'] = 'Video';

$strings['ThereAreNotRecordingsForTheMeetings'] = 'No hay grabaciones de sesiones de videoconferencia';
$strings['NoRecording'] = 'No hay grabación';
$strings['ClickToContinue'] = 'Hacer click para continuar';
$strings['UrlMeetingToShare'] = 'URL a compartir';

$strings['AdminView'] = 'Vista para administradores';
$strings['max_users_limit'] = 'Cantidad máxima de usuarios';
$strings['max_users_limit_help'] = 'Este valor indica la cantidad máxima de usuarios simultáneos en una conferencia en un curso o curso-sesión. Dejar vacío o en 0 para no poner límite.';
$strings['MaxXUsersWarning'] = 'Esta sala de conferencia es limitada a un máximo de %s usuarios simultáneos.';
$strings['MaxXUsersReached'] = 'El límite de %s usuarios simultáneos ha sido alcanzado en esta sala de conferencia. Por favor refresque la página en unos minutos para ver si un asiento se ha liberado, o espere la apertura de una nueva sala para poder participar.';
$strings['MaxXUsersReachedManager'] = 'El límite de %s usuarios simultáneos ha sido alcanzado en esta sala de conferencia. Para aumentar el límite, contáctese con el administrador del portal.';
$strings['MaxUsersInConferenceRoom'] = 'Número máximo de usuarios simultáneos en una sala de conferencia';
