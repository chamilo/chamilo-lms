<?php
/* License: see /license.txt */

// Needed in order to show the plugin title
$strings['plugin_title'] = "Videoconferencia Zoom";
$strings['plugin_comment'] = "Integración de videoconferencias Zoom en los cursos y las sesiones";

$strings['tool_enable'] = 'Herramienta activada';
$strings['apiKey'] = "Clave API (<em>API Key</em>)";
$strings['apiSecret'] = "Código secreto de API (<em>API Secret</em>)";
$strings['enableParticipantRegistration'] = "Activar la inscripción de participantes";
$strings['enableCloudRecording'] = "Tipo de grabación automática";
$strings['enableGlobalConference'] = "Activar las conferencias globales";
$strings['enableGlobalConferencePerUser'] = "Activar las conferencias globales por usuario";
$strings['globalConferenceAllowRoles'] = "Visibilidad del enlace global de videoconferencia para los perfiles siguientes";
$strings['globalConferencePerUserAllowRoles'] = "Visibilidad del enlace global de videoconferencia por usuario para los perfiles siguientes";

$strings['tool_enable_help'] = "Escoja si desea activar la herramienta Zoom.
Una vez activada, aparecerá en las páginas principales de todos los cursos. Los profesores podrán
<strong>iniciar</strong> una conferencia y los alumnos <strong>juntarse</strong> a ella.
<br/>
Este plugin requiere una cuenta Zoom para gestionar las conferencias.
El API de Zoom utiliza los <em>JSON Web Tokens (JWT)</em> para autorizar el acceso a una cuenta.
<strong>Una <em>clave</em> y un <em>código secreto</em> de API son necesarios</strong> para identificarse con JWT.
Para obtenerlos, crea una <em>app JWT</em> :
<br/>1. logéase en <a href=\"https://zoom.us/profile\">Su perfil Zoom</a>
<br/>2. de clic en <em>Avanzado / Marketplace de aplicaciones</em>
<br/>3. de clic en <em><a href=\"https://marketplace.zoom.us/develop/create\">Develop / build App</a></em>
<br/>4. escoja <em>JWT / Create</em>
<br/>5. Information: ingrese algunas informaciones sobre vuestra \"App\"
(nombres de la aplicación, de la empresa, nombre y dirección de correo de contacto)
<br/>6. de clic en <em>Continue</em>
<br/>7. App Credentials:
muestra la clave (API Key) y el código secreto (API Secret) por ingresar aquí.
<strong>copiez la clé (API Key) et le code secret (API Secret) dans les champs ci-dessous.</strong>
<br/>8. de clic en <em>Continue</em>
<br/>9. Feature :
activez <em>Event Subscriptions</em> para agregar uno con su endpoint URL
<code>https://your.chamilo.url/plugin/zoom/endpoint.php</code>
y agrega este tipo de eventos:
<br/>- Start Meeting
<br/>- End Meeting
<br/>- Participant/Host joined meeting
<br/>- Participant/Host left meeting
<br/>- All Recordings have completed
<br/>- Recording transcript files have completed
<br/>de clic en <em>Done</em> y luego en <em>Save</em>
y <strong>copie su Verification Token en el campo a continuación</strong>.
<br/>10. de clic en <em>Continue</em>
<br/>
<strong>Atención</strong> :
<br/>Zoom <em>NO ES</em> un software libre, y reglas específicas de protección de datos se aplican a este.
Por favor verifique con Zoom que éstas le den satisfacción a Usted y los alumnos que la usarán.";

$strings['enableParticipantRegistration_help'] = "Requiere un perfil Zoom de pago.
No funcionará para un perfil <em>base/gratuito</em>.";

$strings['enableCloudRecording_help'] = "Requiere un perfil Zoom de pago.
No funcionará para un perfil <em>base/gratuito</em>.";

// please keep these lines alphabetically sorted
$strings['AllCourseUsersWereRegistered'] = "Todos los alumnos del curso están inscritos";
$strings['Agenda'] = "Orden del día";
$strings['CannotRegisterWithoutEmailAddress'] = "No se puede registrar usuario sin dirección de correo electrónico";
$strings['CopyingJoinURL'] = "Copia de la URL para ingresar";
$strings['CopyJoinAsURL'] = "Copiar la URL para 'ingresar como'";
$strings['CopyToCourse'] = "Copiar en el curso";
$strings['CouldNotCopyJoinURL'] = "Falló la copia de la URL de ingreso";
$strings['Course'] = "Curso";
$strings['CreatedAt'] = "Creado el";
$strings['CreateLinkInCourse'] = "Crear en el curso uno o más vínculos hacia el/los archivo(s)";
$strings['CreateUserVideoConference'] = "Crear conferencias de usario";
$strings['DateMeetingTitle'] = "%s: %s";
$strings['DeleteMeeting'] = "Borrar la conferencia";
$strings['DeleteFile'] = "Borrar este/estos archivo(s)";
$strings['Details'] = "Detalle";
$strings['DoIt'] = "Hágalo";
$strings['Duration'] = "Duración";
$strings['DurationFormat'] = "%hh%I";
$strings['DurationInMinutes'] = "Duración (en minutos)";
$strings['EndDate'] = "Fecha de fin";
$strings['EnterMeeting'] = "Ingresar la conferencia";
$strings['ViewMeeting'] = "Ver la conferencia";
$strings['Files'] = "Archivos";
$strings['Finished'] = "terminada";
$strings['FileWasCopiedToCourse'] = "El archivo ha sido copiado en el curso";
$strings['FileWasDeleted'] = "El archivo ha sido borrado";
$strings['GlobalMeeting'] = "Conferencia global";
$strings['GroupUsersWereRegistered'] = "Miembros de los grupos han sido registrados";
$strings['InstantMeeting'] = "Conferencia instantánea";
$strings['Join'] = "Ingresar";
$strings['JoinGlobalVideoConference'] = "Ingresar la conrencia global";
$strings['JoinURLCopied'] = "URL para juntarse copiada";
$strings['JoinURLToSendToParticipants'] = "URL para asistir a la conferencia (para enviar a los participantes)";
$strings['LiveMeetings'] = "Conferencias activas";
$strings['LinkToFileWasCreatedInCourse'] = "Un enlace al archivo ha sido añadido al curso";
$strings['MeetingDeleted'] = "Conferencia borrada";
$strings['MeetingsFound'] = "Conferencias encontradas";
$strings['MeetingUpdated'] = "Conferencias actualizadas";
$strings['NewMeetingCreated'] = "Nueva conferencia creada";
$strings['Password'] = "Contraseña";
$strings['RecurringWithFixedTime'] = "Recurrente, a una hora fija";
$strings['RecurringWithNoFixedTime'] = "Recurrente, sin hora fija";
$strings['RegisterAllCourseUsers'] = "Inscribir todos los usuarios del curso";
$strings['RegisteredUserListWasUpdated'] = "Lista de usuarios inscritos actualizada";
$strings['RegisteredUsers'] = "Usuarios inscritos";
$strings['RegisterNoUser'] = "No inscribir ningún usuario";
$strings['RegisterTheseGroupMembers'] = "Inscribir los miembros de estos grupos";
$strings['ScheduleAMeeting'] = "Programar una conferencia";
$strings['ScheduledMeeting'] = "Conferencia programada";
$strings['ScheduledMeetings'] = "Conferencias programadas";
$strings['ScheduleAMeeting'] = "Programar una conferencia";
$strings['SearchMeeting'] = "Buscar una conferencia";
$strings['Session'] = "Sesión";
$strings['StartDate'] = "Fecha de inicio";
$strings['Started'] = "iniciada";
$strings['StartInstantMeeting'] = "Iniciar una conferencia instantánea";
$strings['StartMeeting'] = "Iniciar la conferencia";
$strings['StartTime'] = "Hora de inicio";
$strings['Topic'] = "Objeto";
$strings['TopicAndAgenda'] = "Objeto y orden del día";
$strings['Type'] = "Tipo";
$strings['UpcomingMeeting'] = "Próximas conferencias";
$strings['UpdateMeeting'] = "Actualizar la conferencia";
$strings['UpdateRegisteredUserList'] = "Actualizar la lista de usuarios inscritos";
$strings['UserRegistration'] = "Inscripción de los usuarios";
$strings['Y-m-d H:i'] = "d/m/Y a las H\hi";
$strings['verificationToken'] = 'Verification Token';
$strings['Waiting'] = "en espera";
$strings['XRecordingOfMeetingXFromXDurationXDotX'] = "Grabación (%s) de la conferencia %s de %s (%s).%s";
$strings['YouAreNotRegisteredToThisMeeting'] = "No estás registrado en esta reunión";
$strings['ZoomVideoConferences'] = "Videoconferencias Zoom";
$strings['Recordings'] = "Grabaciones";
$strings['CreateGlobalVideoConference'] = "Crear una videoconferencia global";
$strings['JoinURLNotAvailable'] = "URL no disponible";
$strings['Meetings'] = "Conferencias";
$strings['Activity'] = "Actividad";
$strings['ConferenceNotAvailable'] = "Conferencia no disponible";
