<?php

/* Strings for settings */
$strings['plugin_title'] = 'Advanced Subscription';
$strings['plugin_comment'] = 'Plugin for managing the registration queue and communication to sessions from an external website';
$strings['ws_url'] = 'Webservice url';
$strings['ws_url'] = 'Webservice url';
$strings['yearly_cost_unit_converter'] = 'Taxation unit (TU)';
$strings['min_profile_percentage'] = 'Minimum required of completed percentage profile';
$strings['yearly_cost_limit'] = 'Yearly limit TU for courses (measured in Taxation units)';
$strings['yearly_hours_limit'] = 'Yearly limit hours for courses';
$strings['courses_count_limit'] = 'Yearly limit times for courses';
$strings['check_induction'] = 'Activate induction course requirement';
$strings['course_session_credit_year_start_date'] = 'Year start date';

/* String for error message about requirements */
$strings['AdvancedSubscriptionNotConnected'] = "You are not connected to platform. Please login first";
$strings['AdvancedSubscriptionProfileIncomplete'] = "Your percentage completed profile require to exceed minimum percentage. Please complete percentage";
$strings['AdvancedSubscriptionIncompleteInduction'] = "You have not yet completed induction course. Please complete it first";
$strings['AdvancedSubscriptionCostXLimitReached'] = "We are sorry, you have already reached yearly limit mount for courses";
$strings['AdvancedSubscriptionTimeXLimitReached'] = "We are sorry, you have already reached yearly limit hours for courses";
$strings['AdvancedSubscriptionCourseXLimitReached'] = "We are sorry, you have already reached yearly limit times for courses";
$strings['AdvancedSubscriptionNotMoreAble'] = "We are sorry, you no longer fulfills the initial conditions to subscribe this course";

$strings['AdvancedSubscriptionNoQueue'] = "You are not subscribed for this course.";
$strings['AdvancedSubscriptionNoQueueIsAble'] = "You are not subscribed, but you are qualified for this course.";
$strings['AdvancedSubscriptionQueueStart'] = "Your subscription request is pending for approval by your boss, please wait attentive.";
$strings['AdvancedSubscriptionQueueBossDisapproved'] = "We are sorry, your subscription was rejected by your boss.";
$strings['AdvancedSubscriptionQueueBossApproved'] = "Your subscription request has been accepted by your boss, now is pending for vacancies.";
$strings['AdvancedSubscriptionQueueAdminDisapproved'] = "We are sorry, your subscription was rejected by the administrator.";
$strings['AdvancedSubscriptionQueueAdminApproved'] = "Congratulation, your subscription request has been accepted by administrator";
$strings['AdvancedSubscriptionQueueDefaultX'] = "There was an error, queue status %d is not defined by system.";

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
$strings['SelectASession'] = 'Select a session';
$strings['SessionName'] = 'Session name';
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