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
$strings['courses_count_limit_help'] = "El límite de cantidad de cursos (sesiones) que se pueden llevar en un año calendario del año actual y que <b>no</b> sean el curso de inducción";
$strings['course_session_credit_year_start_date'] = 'Fecha de inicio';
$strings['course_session_credit_year_start_date_help'] = "Fecha de inicio del año (día/mes)";


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