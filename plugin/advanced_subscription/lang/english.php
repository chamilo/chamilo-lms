<?php

/* Strings for settings */
$strings['plugin_title'] = 'Advanced Subscription';
$strings['plugin_comment'] = 'Plugin for managing the registration queue and communication to sessions from an external website';
$strings['ws_url'] = 'Webservice url';
$strings['ws_url_help'] = 'The URL from which ingormation will be requested for the advanced subscription process';
$strings['check_induction'] = 'Enable induction course requirement';
$strings['check_induction_help'] = 'Check to make induction course mandatory';
$strings['yearly_cost_limit'] = 'Yearly TUV limit for courses (measured in Taxation units)';
$strings['yearly_cost_limit_help'] = "How much TUVs the student courses should cost at most.";
$strings['yearly_hours_limit'] = 'Yearly teaching hours limit for courses';
$strings['yearly_hours_limit_help'] = "How many teaching hours the student may follow by year.";
$strings['yearly_cost_unit_converter'] = 'Taxation unit value (TUV)';
$strings['yearly_cost_unit_converter_help'] = "The taxation unit value for the current year, in local currency";
$strings['courses_count_limit'] = 'Yearly courses limit';
$strings['courses_count_limit_help'] = "How many times a student can take courses. This value does <strong>not</strong> include induction courses";
$strings['course_session_credit_year_start_date'] = 'Year start date';
$strings['course_session_credit_year_start_date_help'] = "a date (dd/mm)";
$strings['min_profile_percentage'] = "Minimum required of completed percentage profile";
$strings['min_profile_percentage_help'] = "Percentage number( > 0.00 y < 100.00)";

/* String for error message about requirements */
$strings['AdvancedSubscriptionNotConnected'] = "You are not connected to platform. Please login first";
$strings['AdvancedSubscriptionProfileIncomplete'] = "Your percentage completed profile require to exceed minimum percentage. Please complete percentage";
$strings['AdvancedSubscriptionIncompleteInduction'] = "You have not yet completed induction course. Please complete it first";
$strings['AdvancedSubscriptionCostXLimitReached'] = "We are sorry, you have already reached yearly limit %s TUV cost for courses ";
$strings['AdvancedSubscriptionTimeXLimitReached'] = "We are sorry, you have already reached yearly limit %s hours for courses";
$strings['AdvancedSubscriptionCourseXLimitReached'] = "We are sorry, you have already reached yearly limit %s times for courses";
$strings['AdvancedSubscriptionNotMoreAble'] = "We are sorry, you no longer fulfills the initial conditions to subscribe this course";
$strings['AdvancedSubscriptionIncompleteParams'] = "The parameters are wrong or incomplete.";
$strings['AdvancedSubscriptionIsNotEnabled'] = "Advanced subscription is not enabled";

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
$strings['SelectASession'] = 'Select a training session';
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

$strings['AcceptInfinitive'] = 'Accept';
$strings['RejectInfinitive'] = 'Reject';
$strings['AreYouSureYouWantToAcceptSubscriptionOfX'] = 'Are you sure you want to accept the subscription of %s?';
$strings['AreYouSureYouWantToRejectSubscriptionOfX'] = 'Are you sure you want to reject the subscription of %s?';

$strings['MailTitle'] = 'Received request for course %s';
$strings['MailDear'] = 'Dear:';
$strings['MailThankYou'] = 'Thank you.';
$strings['MailThankYouCollaboration'] = 'Thank you for your help.';

// Admin Accept
$strings['MailTitleAdminAcceptToAdmin'] = 'Information: Has been received inscription validation';
$strings['MailContentAdminAcceptToAdmin'] = 'We have received and registered your inscription validation for student <strong>%s</strong> to course <strong>%s</strong>';
$strings['MailTitleAdminAcceptToStudent'] = 'Accepted: Your inscription to course %s was confirmed!';
$strings['MailContentAdminAcceptToStudent'] = 'We are pleased to inform your course registration <strong>%s</strong> starting at <strong>%s</strong> was validated by administrators. We hope to keep all your encouragement and participate in another course or, on another occasion, this same course.';
$strings['MailTitleAdminAcceptToSuperior'] = 'Information: Inscription validation for %s to course %s';
$strings['MailContentAdminAcceptToSuperior'] = 'Inscription for student <strong>%s</strong> to course <strong>%s</strong> starting at <strong>%s</strong>, was pending status, has been validated a few minutes ago. We hope you help us to assure full availability for your collaborator during the course period.';

// Admin Reject
$strings['MailTitleAdminRejectToAdmin'] = 'Information: Has been received inscription refusal';
$strings['MailContentAdminRejectToAdmin'] = 'We have received and registered your inscription refusal for student <strong>%s</strong> to course <strong>%s</strong>';
$strings['MailTitleAdminRejectToStudent'] = 'Your inscription to course %s was refused';
$strings['MailContentAdminRejectToStudent'] = 'We regret that your inscription to course <strong>%s</strong> starting at <strong>%s</strong> was rejected because of lack of vacancies. We hope to keep all your encouragement and participate in another course or, on another occasion, this same course.';
$strings['MailTitleAdminRejectToSuperior'] = 'Information: Inscription refusal for student %s to course %s';
$strings['MailContentAdminRejectToSuperior'] = 'The inscription for <strong>%s</strong> to course <strong>%s</strong>, it was accepted earlier, was rejected because of lack of vacancies. Our sincere apologies.';

// Superior Accept
$strings['MailTitleSuperiorAcceptToAdmin'] = 'Approval for %s to course %s ';
$strings['MailContentSuperiorAcceptToAdmin'] = 'The inscription for student <strong>%s</strong> to course <strong>%s</strong> has been accepted by superior. You can manage inscriptions <a href="%s"><strong>HERE</strong></a>';
$strings['MailTitleSuperiorAcceptToSuperior'] = 'Confirmation: Has been received approval for %s';
$strings['MailContentSuperiorAcceptToSuperior'] = 'We have received and registered your choice to accept inscription to course <strong>%s</strong> for your collaborator <strong>%s</strong>';
$strings['MailContentSuperiorAcceptToSuperiorSecond'] = 'Now, the inscription is pending for availability of vacancies. We will keep you informed about status for this step';
$strings['MailTitleSuperiorAcceptToStudent'] = 'Accepted: Your inscription to course %s has been approved by your superior';
$strings['MailContentSuperiorAcceptToStudent'] = 'We are pleased to inform your inscription to course <strong>%s</strong> has been accepted by your superior. Now, your inscription is pending to availability of vacancies. We will notify you as soon as it is validated.';

// Superior Reject
$strings['MailTitleSuperiorRejectToStudent'] = 'Information: Your inscription to course %s has been refused';
$strings['MailContentSuperiorRejectToStudent'] = 'We regret to inform your inscription to course <strong>%s</strong> was NOT accepted. We hope to keep all your encouragement and participate in another course or, on another occasion, this same course.';
$strings['MailTitleSuperiorRejectToSuperior'] = 'Confirmation: Has been received inscription refusal for %s';
$strings['MailContentSuperiorRejectToSuperior'] = 'We have received and registered your choice to reject inscription to course <strong>%s</strong> for your collaborator <strong>%s</strong>';

// Student Request
$strings['MailTitleStudentRequestToStudent'] = 'Information: Has been received inscription validation';
$strings['MailContentStudentRequestToStudent'] = 'We have received and registered your inscripción request to course <strong>%s</strong> starting at <strong>%s</strong>';
$strings['MailContentStudentRequestToStudentSecond'] = 'Your inscription is pending approval, first from your superior, then the availability of vacancies. An email has been sent to your superior for review and approval of your request.';
$strings['MailTitleStudentRequestToSuperior'] = 'Course inscription request from your collaborator';
$strings['MailContentStudentRequestToSuperior'] = 'We have received an inscription request for <strong>%s</strong> to course <strong>%s</strong>, starting at <strong>%s</strong>. Course details: <strong>%s</strong>.';
$strings['MailContentStudentRequestToSuperiorSecond'] = 'Your are welcome to accept or reject this inscription, clicking the corresponding button.';

// Student Request No Boss
$strings['MailTitleStudentRequestNoSuperiorToStudent'] = 'Has been received your inscription request for %s';
$strings['MailContentStudentRequestNoSuperiorToStudent'] = 'We have received and registered your inscription to course <strong>%s</strong> starting at <strong>%s</strong>.';
$strings['MailContentStudentRequestNoSuperiorToStudentSecond'] = 'Your inscription is pending availability of vacancies. Soon you will get the result of your request approval.';
$strings['MailTitleStudentRequestNoSuperiorToAdmin'] = 'Inscription request for %s to course %s';
$strings['MailContentStudentRequestNoSuperiorToAdmin'] = 'The inscription for <strong>%s</strong> to course <strong>%s</strong> has been approved by default, missing superior. You can manage inscriptions <a href="%s"><strong>HERE</strong></a>';

// Reminders
$strings['MailTitleReminderAdmin'] = 'Inscription to %s are pending confirmation';
$strings['MailContentReminderAdmin'] = 'The inscription below to course <strong>%s</strong> are pending validation to be accepted. Please, go to <a href="%s">Administration page</a> to validate them.';
$strings['MailTitleReminderStudent'] = 'Information: Your request is pending approval to course %s';
$strings['MailContentReminderStudent'] = 'This email is to confirm we have received and registered your inscription request to course <strong>%s</strong>, starting at <strong>%s</strong>.';
$strings['MailContentReminderStudentSecond'] = 'Your inscription has not yet been approved by your superior, so we send him an reminder email.';
$strings['MailTitleReminderSuperior'] = 'Course inscription request for your collaborators';
$strings['MailContentReminderSuperior'] = 'We remind you, we have received inscription requests below to course <strong>%s</strong> for your collaborators. This course is starting at <strong>%s</strong>. Course details: <strong>%s</strong>.';
$strings['MailContentReminderSuperiorSecond'] = 'We invite you to accept or reject inscription request, clicking corresponding button for each collaborator.';
$strings['MailTitleReminderMaxSuperior'] = 'Reminder: Course inscription request for your collaborators';
$strings['MailContentReminderMaxSuperior'] = 'We remind you, we have received inscription requests below to course <strong>%s</strong> for your collaborators. This course is starting at <strong>%s</strong>. Course details: <strong>%s</strong>.';
$strings['MailContentReminderMaxSuperiorSecond'] = 'This course have limited vacancies and has received a high inscription request rate, So we recommend all areas to accept at most <strong>%s</strong> candidates. We invite you to accept or reject inscription request, clicking corresponding button for each collaborator.';
