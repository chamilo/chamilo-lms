<?php

/* Strings for settings */
$strings['plugin_title'] = 'Advanced Subscription';
$strings['plugin_comment'] = 'Plugin que permite gestionar la inscripción en cola a sesiones con comunicación a a un portal externo';
$strings['uit_value'] = 'Valor de la Unidad Impositiva Tributaria (UIT)';
$strings['ws_url'] = 'URL del Webservice';
$strings['min_profile_percentage'] = 'Porcentaje mínimo de perfil completado';
$strings['max_expended_uit'] = 'Limite anual de cursos en UIT por año(Medido en UIT)';
$strings['max_expended_num'] = 'Limite anual de cursos en horas por año(Medido en UIT)';
$strings['max_course_times'] = 'Limite anual de cantidad de cursos por año';
$strings['check_induction'] = 'Activar requerimiento de curso inducción';

/* String for error message about requirements */
$strings['AdvancedSubscriptionNotConnected'] = "Usted no está conectado en la plataforma. Por favor ingrese su usuario / constraseña para poder inscribirse";
$strings['AdvancedSubscriptionProfileIncomplete'] = "Su perfil no es lo suficientemente completo para poder inscribirse al curso. Por favor complete su perfil";
$strings['AdvancedSubscriptionIncompleteInduction'] = "Usted aún no ha completado el curso de inducción. Por favor complete el curso inducción";
$strings['AdvancedSubscriptionCostXLimitReached'] = "Lo sentimos, usted ya ha alcanzado el límite anual de %s UIT para los cursos que ha seguido este año";
$strings['AdvancedSubscriptionTimeXLimitReached'] = "Lo sentimos, usted ya ha alcanzado el límite anual de %s horas para los cursos que ha seguido este año";
$strings['AdvancedSubscriptionCourseXLimitReached'] = "Lo sentimos, usted ya ha alcanzado el límite anual de %s cursos que ha seguido este año";
$strings['AdvancedSubscriptionNotMoreAble'] = "Lo sentimos, usted ya no cumple con las condiciones iniciales para poder inscribirse al curso";

$strings['AdvancedSubscriptionNoQueue'] = "Usted no está inscrito en este curso.";
$strings['AdvancedSubscriptionNoQueueIsAble'] = "Usted no está inscrito, pero está calificado para este curso.";
$strings['AdvancedSubscriptionQueueStart'] = "Su inscripción está en espera de aprobación por parte de su jefe, por favor este atento.";
$strings['AdvancedSubscriptionQueueBossDisapproved'] = "Lo sentimos, su inscripción ha sido desaprobada por su jefe.";
$strings['AdvancedSubscriptionQueueBossApproved'] = "Su inscripción ha sido aprobada por su jefe, y está en espera de aprobación del Administrador.";
$strings['AdvancedSubscriptionQueueAdminDisapproved'] = "Lo sentimos, su inscripción ha sido desaprobada por el administrador.";
$strings['AdvancedSubscriptionQueueAdminApproved'] = "Felicitaciones, Su inscripción ha sido aprobada por el administrador.";
$strings['AdvancedSubscriptionQueueDefaultX'] = "Ha ocurrido un problema, el estado en cola, %d no está definido en el sistema.";

// Mail translations
$strings['MailStudentRequest'] = 'Student registration request';
$strings['MailBossAccept'] = 'Registration request accepted by boss';
$strings['MailBossReject'] = 'Registration request rejected by boss';
$strings['MailStudentRequestSelect'] = 'Student registration requests selection';
$strings['MailAdminAccept'] = 'Registration request accepted by administrator';
$strings['MailAdminReject'] = 'Registration request rejected by administrator';
$strings['MailStudentRequestNoBoss'] = 'Student registration request without boss';

// TPL langs
// Admin view
$strings['SelectASession'] = 'Elija una sesión';
$strings['SessionName'] = 'Nombre de la sesión';
$strings['Target'] = 'Target audience';
$strings['Vacancies'] = 'Vacancies';
$strings['RecommendedNumberOfParticipants'] = 'Recommended number of participants';
$strings['PublicationEndDate'] = 'Publication end date';
$strings['Mode'] = 'Mode';
$strings['Postulant'] = 'Postulant';
$strings['InscriptionDate'] = 'Inscription date';
$strings['BossValidation'] = 'Boss validation';
$strings['Decision'] = 'Decision';
$strings['AdvancedSubscriptionAdminViewTitle'] = 'Subscription request confirmation result';