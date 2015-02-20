<?php

/* Strings for settings */
$strings['plugin_title'] = 'Inscripción Avanzada';
$strings['plugin_comment'] = 'Plugin que permite gestionar la inscripción en cola a sesiones con comunicación a a un portal externo';
$strings['ws_url'] = 'URL del Webservice';
$strings['ws_url_help'] = 'La URL de la cual se solicitará información para el proceso de la inscripción avanzada';
$strings['check_induction'] = 'Activar requerimiento de curso inducción';
$strings['check_induction_help'] = 'Escoja si se requiere que se complete los cursos de inducción';
$strings['tool_enable'] = 'Inscripción avanzada activada';
$strings['tool_enable_help'] = "Escoja si desea activar la inscripción avanzada.";
$strings['yearly_cost_limit'] = 'Límite de UITs';
$strings['yearly_cost_limit_help'] = "El límite de UITs de cursos que se pueden llevar en un año calendario del año actual.";
$strings['yearly_hours_limit'] = 'Límite de horas lectivas';
$strings['yearly_hours_limit_help'] = "El límite de horas lectivas de cursos que se pueden llevar en un año calendario del año actual.";
$strings['yearly_cost_unit_converter'] = 'Valor de un UIT';
$strings['yearly_cost_unit_converter_help'] = "El valor en Soles de un UIT del año actual.";
$strings['courses_count_limit'] = 'Límite de sesiones';
$strings['courses_count_limit_help'] = "El límite de cantidad de cursos (sesiones) que se pueden llevar en un año calendario del año actual y que <strong>no</strong> sean el curso de inducción";
$strings['course_session_credit_year_start_date'] = 'Fecha de inicio';
$strings['course_session_credit_year_start_date_help'] = "Fecha de inicio del año (día/mes)";
$strings['min_profile_percentage'] = 'Porcentage de perfil completado mínimo requerido';
$strings['min_profile_percentage_help'] = 'Número porcentage ( > 0.00 y < 100.00)';


/* String for error message about requirements */
$strings['AdvancedSubscriptionNotConnected'] = "Usted no está conectado en la plataforma. Por favor ingrese su usuario / constraseña para poder inscribirse";
$strings['AdvancedSubscriptionProfileIncomplete'] = "Su perfil no es lo suficientemente completo para poder inscribirse al curso. Por favor complete su perfil";
$strings['AdvancedSubscriptionIncompleteInduction'] = "Usted aún no ha completado el curso de inducción. Por favor complete el curso inducción";
$strings['AdvancedSubscriptionCostXLimitReached'] = "Lo sentimos, usted ya ha alcanzado el límite anual de %s UIT para los cursos que ha seguido este año";
$strings['AdvancedSubscriptionTimeXLimitReached'] = "Lo sentimos, usted ya ha alcanzado el límite anual de %s horas para los cursos que ha seguido este año";
$strings['AdvancedSubscriptionCourseXLimitReached'] = "Lo sentimos, usted ya ha alcanzado el límite anual de %s cursos que ha seguido este año";
$strings['AdvancedSubscriptionNotMoreAble'] = "Lo sentimos, usted ya no cumple con las condiciones iniciales para poder inscribirse al curso";
$strings['AdvancedSubscriptionIncompleteParams'] = "Los parámetros enviados no están completos o no son los correctos.";

$strings['AdvancedSubscriptionIsNotEnabled'] = "La inscripción avanzada no está activada";

//Needed in order to show the plugin title

// Mail translations
$strings['MailStudentRequest'] = 'Solicitud de registro de estudiante';
$strings['MailBossAccept'] = 'Solicitud de registro aceptada por superior';
$strings['MailBossReject'] = 'Solicitud de registro rechazada por superior';
$strings['MailStudentRequestSelect'] = 'Selección de solicitudes de registro de estudiante';
$strings['MailAdminAccept'] = 'Solicitud de registro aceptada por administrador';
$strings['MailAdminReject'] = 'Solicitud de registro rechazada por administrador';
$strings['MailStudentRequestNoBoss'] = 'Solicitud de registro de estudiante sin superior';

// TPL translations
// Admin view
$strings['SelectASession'] = 'Elija una sesión';
$strings['SessionName'] = 'Nombre de la sesión';
$strings['Target'] = 'Publico objetivo';
$strings['Vacancies'] = 'Vacantes';
$strings['RecommendedNumberOfParticipants'] = 'Número recomendado de participantes';
$strings['PublicationEndDate'] = 'Fecha fin de publicación';
$strings['Mode'] = 'Modalidad';
$strings['Postulant'] = 'Postulante';
$strings['InscriptionDate'] = 'Fecha de inscripción';
$strings['BossValidation'] = 'Validación del superior';
$strings['Decision'] = 'Decisión';
$strings['AdvancedSubscriptionAdminViewTitle'] = 'Resultado de confirmación de solicitud de inscripción';

$strings['AcceptInfinitive'] = 'Aceptar';
$strings['RejectInfinitive'] = 'Rechazar';
$strings['AreYouSureYouWantToAcceptSubscriptionOfX'] = '¿Está seguro que quiere aceptar la inscripción de %s?';
$strings['AreYouSureYouWantToRejectSubscriptionOfX'] = '¿Está seguro que quiere rechazar la inscripción de %s?';

$strings['MailTitle'] = 'Solicitud recibida para el curso %s';
$strings['MailDear'] = 'Estimado(a):';
$strings['MailThankYou'] = 'Gracias.';
$strings['MailThankYouCollaboration'] = 'Gracias por su colaboración.';

// Admin Accept
$strings['MailTitleAdminAcceptToAdmin'] = 'Información: Validación de inscripción recibida';
$strings['MailContentAdminAcceptToAdmin'] = 'Hemos recibido y registrado su validación de la inscripción de <strong>%s</strong> al curso <strong>%s</strong>';
$strings['MailTitleAdminAcceptToStudent'] = 'Aprobada: ¡Su inscripción al curso %s fue confirmada!';
$strings['MailContentAdminAcceptToStudent'] = 'Nos complace informarle que su inscripción al curso <strong>%s</strong> iniciando el <strong>%s</strong> fue validada por los administradores. Esperamos mantenga todo su ánimo y participe en otro curso o, en otra oportunidad, a este mismo curso.';
$strings['MailTitleAdminAcceptToSuperior'] = 'Información: Validación de inscripción de %s al curso %s';
$strings['MailContentAdminAcceptToSuperior'] = 'La inscripción de <strong>%s</strong> al curso <strong>%s</strong> iniciando el <strong>%s</strong>, que estaba pendiente de validación por los organizadores del curso, fue validada hacen unos minutos. Esperamos nos ayude en asegurar la completa disponibilidad de su colaborador(a) para la duración completa del curso.';

// Admin Reject
$strings['MailTitleAdminRejectToAdmin'] = 'Información: rechazo de inscripción recibido';
$strings['MailContentAdminRejectToAdmin'] = 'Hemos recibido y registrado su rechazo de la inscripción de <strong>%s</strong> al curso <strong>%s</strong>';
$strings['MailTitleAdminRejectToStudent'] = 'Rechazamos su inscripción al curso %s';
$strings['MailContentAdminRejectToStudent'] = 'Lamentamos informarle que su inscripción al curso <strong>%s</strong> iniciando el <strong>%s</strong> fue rechazada por falta de cupos. Esperamos mantenga todo su ánimo y participe en otro curso o, en otra oportunidad, a este mismo curso.';
$strings['MailTitleAdminRejectToSuperior'] = 'Información: Rechazo de inscripción de %s al curso %s';
$strings['MailContentAdminRejectToSuperior'] = 'La inscripción de <strong>%s</strong> al curso <strong>%s</strong>, que había aprobado anteriormente, fue rechazada por falta de cupos. Les presentamos nuestras disculpas sinceras.';

// Superior Accept
$strings['MailTitleSuperiorAcceptToAdmin'] = 'Aprobación de %s al curso %s ';
$strings['MailContentSuperiorAcceptToAdmin'] = 'La inscripción del alumno <strong>%s</strong> al curso <strong>%s</strong> ha sido aprobada por su superior. Puede gestionar las inscripciones al curso <a href="%s"><strong>aquí</strong></a>';
$strings['MailTitleSuperiorAcceptToSuperior'] = 'Confirmación: Aprobación recibida para %s';
$strings['MailContentSuperiorAcceptToSuperior'] = 'Hemos recibido y registrado su decisión de aprobar el curso <strong>%s</strong> para su colaborador <strong>%s</strong>';
$strings['MailContentSuperiorAcceptToSuperiorSecond'] = 'Ahora la inscripción al curso está pendiente de la disponibilidad de cupos. Le mantendremos informado sobre el resultado de esta etapa';
$strings['MailTitleSuperiorAcceptToStudent'] = 'Aprobado: Su inscripción al curso %s ha sido aprobada por su superior ';
$strings['MailContentSuperiorAcceptToStudent'] = 'Nos complace informarle que su inscripción al curso <strong>%s</strong> ha sido aprobada por su superior. Su inscripción ahora solo se encuentra pendiente de disponibilidad de cupos. Le avisaremos tan pronto como se confirme este último paso.';

// Superior Reject
$strings['MailTitleSuperiorRejectToStudent'] = 'Información: Su inscripción al curso %s ha sido rechazada ';
$strings['MailContentSuperiorRejectToStudent'] = 'Lamentamos informarle que, en esta oportunidad, su inscripción al curso <strong>%s</strong> NO ha sido aprobada. Esperamos mantenga todo su ánimo y participe en otro curso o, en otra oportunidad, a este mismo curso.';
$strings['MailTitleSuperiorRejectToSuperior'] = 'Confirmación: Desaprobación recibida para %s';
$strings['MailContentSuperiorRejectToSuperior'] = 'Hemos recibido y registrado su decisión de desaprobar el curso <strong>%s</strong> para su colaborador <strong>%s</strong>';

// Student Request
$strings['MailTitleStudentRequestToStudent'] = 'Información: Validación de inscripción recibida';
$strings['MailContentStudentRequestToStudent'] = 'Hemos recibido y registrado su solicitud de inscripción al curso <strong>%s</strong> para iniciarse el <strong>%s</strong>.';
$strings['MailContentStudentRequestToStudentSecond'] = 'Su inscripción es pendiente primero de la aprobación de su superior, y luego de la disponibilidad de cupos. Un correo ha sido enviado a su superior para revisión y aprobación de su solicitud.';
$strings['MailTitleStudentRequestToSuperior'] = 'Solicitud de consideración de curso para un colaborador';
$strings['MailContentStudentRequestToSuperior'] = 'Hemos recibido una solicitud de inscripción de <strong>%s</strong> al curso <strong>%s</strong>, por iniciarse el <strong>%s</strong>. Detalles del curso: <strong>%s</strong>.';
$strings['MailContentStudentRequestToSuperiorSecond'] = 'Le invitamos a aprobar o desaprobar esta inscripción, dando clic en el botón correspondiente a continuación.';

// Student Request No Boss
$strings['MailTitleStudentRequestNoSuperiorToStudent'] = 'Solicitud recibida para el curso %s';
$strings['MailContentStudentRequestNoSuperiorToStudent'] = 'Hemos recibido y registrado su solicitud de inscripción al curso <strong>%s</strong> para iniciarse el <strong>%s</strong>.';
$strings['MailContentStudentRequestNoSuperiorToStudentSecond'] = 'Su inscripción es pendiente de la disponibilidad de cupos. Pronto recibirá los resultados de su aprobación de su solicitud.';
$strings['MailTitleStudentRequestNoSuperiorToAdmin'] = 'Solicitud de inscripción de %s para el curso %s';
$strings['MailContentStudentRequestNoSuperiorToAdmin'] = 'La inscripción del alumno <strong>%s</strong> al curso <strong>%s</strong> ha sido aprobada por defecto, a falta de superior. Puede gestionar las inscripciones al curso <a href="%s"><strong>aquí</strong></a>';

// Reminders
$strings['MailTitleReminderAdmin'] = 'Inscripciones a %s pendiente de confirmación';
$strings['MailContentReminderAdmin'] = 'Las inscripciones siguientes al curso <strong>%s</strong> están pendientes de validación para ser efectivas. Por favor, dirigese a la <a href="%s">página de administración</a> para validarlos.';
$strings['MailTitleReminderStudent'] = 'Información: Solicitud pendiente de aprobación para el curso %s';
$strings['MailContentReminderStudent'] = 'Este correo es para confirmar que hemos recibido y registrado su solicitud de inscripción al  curso <strong>%s</strong>, por iniciarse el <strong>%s</strong>.';
$strings['MailContentReminderStudentSecond'] = 'Su inscripción todavía no ha sido aprobada por su superior, por lo que hemos vuelto a enviarle un correo electrónico de recordatorio.';
$strings['MailTitleReminderSuperior'] = 'Solicitud de consideración de curso para un colaborador';
$strings['MailContentReminderSuperior'] = 'Le recordamos que hemos recibido las siguientes solicitudes de suscripción para el curso <strong>%s</strong> de parte de sus colaboradores. El curso se iniciará el <strong>%s</strong>. Detalles del curso: <strong>%s</strong>.';
$strings['MailContentReminderSuperiorSecond'] = 'Le invitamos a aprobar o desaprobar las suscripciones, dando clic en el botón correspondiente a continuación para cada colaborador.';
$strings['MailTitleReminderMaxSuperior'] = 'Recordatorio: Solicitud de consideración de curso para colaborador(es)';
$strings['MailContentReminderMaxSuperior'] = 'Le recordamos que hemos recibido las siguientes solicitudes de suscripción al curso <strong>%s</strong> de parte de sus colaboradores. El curso se iniciará el <strong>%s</strong>. Detalles del curso: <strong>%s</strong>.';
$strings['MailContentReminderMaxSuperiorSecond'] = 'Este curso tiene una cantidad de cupos limitados y ha recibido una alta tasa de solicitudes de inscripción, por lo que recomendamos que cada área apruebe un máximo de <strong>%s</strong> candidatos. Le invitamos a aprobar o desaprobar las suscripciones, dando clic en el botón correspondiente a continuación para cada colaborador.';
