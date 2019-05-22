<?php

/* Strings for settings */
$strings['plugin_title'] = 'Inscriptions avancées';
$strings['plugin_comment'] = 'Plugin qui permet de gérer des listes d\'attente pour l\'inscription aux sessions, avec communications avec un portail extérieur';
$strings['ws_url'] = 'URL du Service Web';
$strings['ws_url_help'] = 'L\'URL depuis laquelle l\'information est requise pour le processus d\'inscription avancée';
$strings['check_induction'] = 'Activer le cours d\'induction comme pré-requis';
$strings['check_induction_help'] = 'Décidez s\'il est nécessaire de compléter les cours d\'induction';
$strings['yearly_cost_limit'] = 'Límite d\'unités de taxe';
$strings['yearly_cost_limit_help'] = "La limite d\'unités de taxe à utiliser pour des cours dans l\'année calendrier actuelle.";
$strings['yearly_hours_limit'] = 'Límite d\'heures académiques';
$strings['yearly_hours_limit_help'] = "La límite d\'heures académiques de cours qui peuvent être suivies en une année calendrier.";
$strings['yearly_cost_unit_converter'] = 'Valeur d\'une unité de taxe';
$strings['yearly_cost_unit_converter_help'] = "La valeur en devise locale d\'une unité de taxe de l\'année actuelle.";
$strings['courses_count_limit'] = 'Límite de sessions';
$strings['courses_count_limit_help'] = "La límite de nombre de cours (sessions) qui peuvent être suivis durant une année calendrier et qui <strong>ne sont pas</strong> le cours d'induction";
$strings['course_session_credit_year_start_date'] = 'Date de début';
$strings['course_session_credit_year_start_date_help'] = "Date de début de l'année (jour/mois)";
$strings['min_profile_percentage'] = 'Pourcentage du profil complété mínimum requis';
$strings['min_profile_percentage_help'] = 'Numéro pourcentage ( > 0.00 et < 100.00)';
$strings['secret_key'] = 'Clef secrète';
$strings['terms_and_conditions'] = 'Conditions d\'utilisation';

/* String for error message about requirements */
$strings['AdvancedSubscriptionNotConnected'] = "Vous n'êtes pas connecté à la plateforme. Merci d'introduire votre nom d'utilisateur / mot de passe afin de vous inscrire";
$strings['AdvancedSubscriptionProfileIncomplete'] = "Vous devez d'abord compléter votre profil <strong>à %d pourcents</strong> ou plus. Pour l'instant vous n'avez complété que <strong>%d pourcents</strong>";
$strings['AdvancedSubscriptionIncompleteInduction'] = "Vous n'avez pas encore passé le cours d'induction. Merci de commencer par cette étape.";
$strings['AdvancedSubscriptionCostXLimitReached'] = "Désolé, vous avez déjà atteint la limite de %s unités de taxe pour les cours que vous avez suivi cette année";
$strings['AdvancedSubscriptionTimeXLimitReached'] = "Désolé, vous avez déjà atteint la limite annuelle du nombre de %s heures pour les cours que vous avez suivi cette année";
$strings['AdvancedSubscriptionCourseXLimitReached'] = "Désolé, vous avez déjà atteint la limite annuelle du nombre de cours (%s) à suivre cette année";
$strings['AdvancedSubscriptionNotMoreAble'] = "Désolé, vous ne répondez plus aux conditions d'utilisation minimum pour l'inscription à un cours";
$strings['AdvancedSubscriptionIncompleteParams'] = "Les paramètres envoyés ne sont pas complets ou sont incorrects.";
$strings['AdvancedSubscriptionIsNotEnabled'] = "L'inscription avancée n'est pas activée";
$strings['AdvancedSubscriptionNoQueue'] = "Vous n'êtes pas inscrit dans ce cours";
$strings['AdvancedSubscriptionNoQueueIsAble'] = "Vous n'êtes pas inscrit mais vous qualifiez pour ce cours";
$strings['AdvancedSubscriptionQueueStart'] = "Votre demande d'inscription est en attente de l'approbation de votre supérieur(e). Merci de patienter.";
$strings['AdvancedSubscriptionQueueBossDisapproved'] = "Désolé, votre inscription a été déclinée par votre supérieur(e).";
$strings['AdvancedSubscriptionQueueBossApproved'] = "Votre demande d'inscription a été acceptée par votre supérieur(e), mais est en attente de places libres.";
$strings['AdvancedSubscriptionQueueAdminDisapproved'] = "Désolé, votre inscription a été déclinée par l'administrateur.";
$strings['AdvancedSubscriptionQueueAdminApproved'] = "Félicitations! Votre inscription a été acceptée par l'administrateur.";
$strings['AdvancedSubscriptionQueueDefaultX'] = "Une erreur est survenue: l'état de la file d'attente %s n'est pas défini dans le système.";

// Mail translations
$strings['MailStudentRequest'] = 'Demange d\'inscription d\'un(e) apprenant(e)';
$strings['MailBossAccept'] = 'Demande d\'inscription acceptée par votre supérieur(e)';
$strings['MailBossReject'] = 'Demande d\'inscription déclinée par votre supérieur(e)';
$strings['MailStudentRequestSelect'] = 'Sélection des demandes d\'inscriptions d\'apprenants';
$strings['MailAdminAccept'] = 'Demande d\'inscription acceptée par l\'administrateur';
$strings['MailAdminReject'] = 'Demande d\'inscription déclinée par l\'administrateur';
$strings['MailStudentRequestNoBoss'] = 'Demande d\'inscription d\'apprenant sans supérieur(e)';
$strings['MailRemindStudent'] = 'Rappel de demande d\'inscription';
$strings['MailRemindSuperior'] = 'Demandes d\'inscription en attente de votre approbation';
$strings['MailRemindAdmin'] = 'Inscriptions en attente de votre approbation';

// TPL translations
$strings['SessionXWithoutVacancies'] = "Le cours \"%s\" ne dispose plus de places libres.";
$strings['SuccessSubscriptionToSessionX'] = "<h4>Félicitations!</h4> Votre inscription au cours \"%s\" est en ordre.";
$strings['SubscriptionToOpenSession'] = "Inscription à cours ouvert";
$strings['GoToSessionX'] = "Aller dans le cours \"%s\"";
$strings['YouAreAlreadySubscribedToSessionX'] = "Vous êtes déjà inscrit(e) au cours \"%s\".";

// Admin view
$strings['SelectASession'] = 'Sélectionnez une session de formation';
$strings['SessionName'] = 'Nom de la session';
$strings['Target'] = 'Public cible';
$strings['Vacancies'] = 'Places libres';
$strings['RecommendedNumberOfParticipants'] = 'Nombre recommandé de participants par département';
$strings['PublicationEndDate'] = 'Date de fin de publication';
$strings['Mode'] = 'Modalité';
$strings['Postulant'] = 'Candidats';
$strings['Area'] = 'Département';
$strings['Institution'] = 'Institution';
$strings['InscriptionDate'] = 'Date d\'inscription';
$strings['BossValidation'] = 'Validation du supérieur';
$strings['Decision'] = 'Décision';
$strings['AdvancedSubscriptionAdminViewTitle'] = 'Résultat de confirmation de demande d\'inscription';

$strings['AcceptInfinitive'] = 'Accepter';
$strings['RejectInfinitive'] = 'Refuser';
$strings['AreYouSureYouWantToAcceptSubscriptionOfX'] = 'Êtes-vous certain de vouloir accepter l\'inscription de %s?';
$strings['AreYouSureYouWantToRejectSubscriptionOfX'] = 'Êtes-vous certain de vouloir refuser l\'inscription de %s?';

$strings['MailTitle'] = 'Demande reçue pour le cours %s';
$strings['MailDear'] = 'Cher/Chère';
$strings['MailThankYou'] = 'Merci.';
$strings['MailThankYouCollaboration'] = 'Merci de votre collaboration.';

// Admin Accept
$strings['MailTitleAdminAcceptToAdmin'] = 'Information: Validation d\'inscription reçue';
$strings['MailContentAdminAcceptToAdmin'] = 'Nous avons bien reçu et enregistré votre validation de l\'inscription de <strong>%s</strong> au cours <strong>%s</strong>';
$strings['MailTitleAdminAcceptToStudent'] = 'Approuvé(e): Votre inscription au cours %s a été confirmée!';
$strings['MailContentAdminAcceptToStudent'] = 'C\'est avec plaisir que nous vous informons que votre inscription au cours <strong>%s</strong> démarrant le <strong>%s</strong> a été validée par les administrateurs. Nous espérons que votre motivation s\'est maintenue à 100% et que vous participerez à d\'autres cours ou répétiez ce cours à l\'avenir.';
$strings['MailTitleAdminAcceptToSuperior'] = 'Information: Validation de l\'inscription de %s au cours %s';
$strings['MailContentAdminAcceptToSuperior'] = 'L\'inscription de <strong>%s</strong> au cours <strong>%s</strong> qui démarre le <strong>%s</strong>, qui était en attente de validation par les organisateurs du cours, vient d\'être validée. Nous espérons que vous nous donnerez un coup de main pour assurer la disponibilité complète de votre collaborateur pour toute la durée du cours';

// Admin Reject
$strings['MailTitleAdminRejectToAdmin'] = 'Information: refus d\'inscription reçu';
$strings['MailContentAdminRejectToAdmin'] = 'Nous avons bien reçu et enregistré votre refus pour l\'inscription de <strong>%s</strong> au cours <strong>%s</strong>';
$strings['MailTitleAdminRejectToStudent'] = 'Votre demande d\'inscription au cours %s a été refusée';
$strings['MailContentAdminRejectToStudent'] = 'Nous déplorons le besoin de vous informer que vote demande d\'inscription au cours <strong>%s</strong> démarrant le <strong>%s</strong> a été refusée pour manque de place. Nous espérons que vous maintiendrez votre motivation et que vous pourrez participer au même ou à un autre cours lors d\'une prochaine occasion.';
$strings['MailTitleAdminRejectToSuperior'] = 'Information: Refus d\'inscription de %s au cours %s';
$strings['MailContentAdminRejectToSuperior'] = 'L\'inscription de <strong>%s</strong> au cours <strong>%s</strong>, qui avait été approuvée antérieurement, a été refusée par manque de place. Nous vous présentons nos excuses sincères.';

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

$strings['YouMustAcceptTermsAndConditions'] = 'Para inscribirse al curso <strong>%s</strong>, debe aceptar estos términos y condiciones.';
