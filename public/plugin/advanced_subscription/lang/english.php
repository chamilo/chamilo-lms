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
$strings['secret_key'] = 'Secret key';
$strings['terms_and_conditions'] = 'Terms and conditions';

/* String for error message about requirements */
$strings['AdvancedSubscriptionNotConnected'] = "You are not connected to platform. Please login first";
$strings['AdvancedSubscriptionProfileIncomplete'] = "You must complete at least <strong>%d percent</strong> of your profile. You have only completed <strong>%d percent</strong> at this point";
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
$strings['AdvancedSubscriptionQueueAdminApproved'] = "Congratulations!, your subscription request has been accepted by administrator.";
$strings['AdvancedSubscriptionQueueDefaultX'] = "There was an error, queue status %d is not defined by system.";

// Mail translations
$strings['MailStudentRequest'] = 'Student registration request';
$strings['MailBossAccept'] = 'Registration request accepted by boss';
$strings['MailBossReject'] = 'Registration request rejected by boss';
$strings['MailStudentRequestSelect'] = 'Student registration requests selection';
$strings['MailAdminAccept'] = 'Registration request accepted by administrator';
$strings['MailAdminReject'] = 'Registration request rejected by administrator';
$strings['MailStudentRequestNoBoss'] = 'Student registration request without boss';
$strings['MailRemindStudent'] = 'Subscription request reminder';
$strings['MailRemindSuperior'] = 'Subscription request are pending your approval';
$strings['MailRemindAdmin'] = 'Course subscription are pending your approval';

// TPL langs
$strings['SessionXWithoutVacancies'] = "The course \"%s\" has no vacancies.";
$strings['SuccessSubscriptionToSessionX'] = "<h4>¡Congratulations!</h4>Your subscription to \"%s\" course has been completed successfully.";
$strings['SubscriptionToOpenSession'] = "Subscription to open course";
$strings['GoToSessionX'] = "Go to \"%s\" course";
$strings['YouAreAlreadySubscribedToSessionX'] = "You are already subscribed to \"%s\" course.";

// Admin view
$strings['SelectASession'] = 'Select a training session';
$strings['SessionName'] = 'Session name';
$strings['Target'] = 'Target audience';
$strings['Vacancies'] = 'Vacancies';
$strings['RecommendedNumberOfParticipants'] = 'Recommended number of participants by area';
$strings['PublicationEndDate'] = 'Publication end date';
$strings['Mode'] = 'Mode';
$strings['Postulant'] = 'Postulant';
$strings['Area'] = 'Area';
$strings['Institution'] = 'Institution';
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
$strings['MailTitleAdminAcceptToAdmin'] = 'Notification: subscription validation received';
$strings['MailContentAdminAcceptToAdmin'] = 'We have received and registered your subscription validation for student <strong>%s</strong> to course <strong>%s</strong>';
$strings['MailTitleAdminAcceptToStudent'] = 'Accepted: Your subscription to course %s has been accepted!';
$strings['MailContentAdminAcceptToStudent'] = 'We are pleased to inform you that your registration to course <strong>%s</strong> starting on <strong>%s</strong> was validated by an administrator. We wish you good luck and hope you will consider participating to another course soon.';
$strings['MailTitleAdminAcceptToSuperior'] = 'Notification: Subscription validation of %s to course %s';
$strings['MailContentAdminAcceptToSuperior'] = 'Subscription of student <strong>%s</strong> to course <strong>%s</strong> starting on <strong>%s</strong> was pending but has been validated a few minutes ago. We kindly hope we can count on you to ensure the necessary availability of your collaborator during the course period.';

// Admin Reject
$strings['MailTitleAdminRejectToAdmin'] = 'Notification: Rejection received';
$strings['MailContentAdminRejectToAdmin'] = 'We have received and registered your rejection for the subscription of student <strong>%s</strong> to course <strong>%s</strong>';
$strings['MailTitleAdminRejectToStudent'] = 'Your subscription to course %s was rejected';
$strings['MailContentAdminRejectToStudent'] = 'We regret to inform you that your subscription to course <strong>%s</strong> starting on <strong>%s</strong> was rejected because of a lack of vacancies. We hope you will consider participating to another course soon.';
$strings['MailTitleAdminRejectToSuperior'] = 'Notification: Subscription refusal for student %s to course %s';
$strings['MailContentAdminRejectToSuperior'] = 'The subscription of <strong>%s</strong> to course <strong>%s</strong> that you previously validated was rejected because of a lack of vacancies. Our sincere apologies.';

// Superior Accept
$strings['MailTitleSuperiorAcceptToAdmin'] = 'Approval for subscription of %s to course %s ';
$strings['MailContentSuperiorAcceptToAdmin'] = 'The subscription of student <strong>%s</strong> to course <strong>%s</strong> has been accepted by his superior. You can <a href="%s">manage subscriptions here</a>';
$strings['MailTitleSuperiorAcceptToSuperior'] = 'Confirmation: Approval received for %s';
$strings['MailContentSuperiorAcceptToSuperior'] = 'We have received and registered you validation of subscription to course <strong>%s</strong> of your collaborator <strong>%s</strong>';
$strings['MailContentSuperiorAcceptToSuperiorSecond'] = 'The subscription is now pending for a vacancies confirmation. We will keep you informed about changes of status for this subscription';
$strings['MailTitleSuperiorAcceptToStudent'] = 'Accepted: Your subscription to course %s has been approved by your superior';
$strings['MailContentSuperiorAcceptToStudent'] = 'We are pleased to inform you that your subscription to course <strong>%s</strong> has been accepted by your superior. Your inscription is now pending for a vacancies confirmation. We will notify you as soon as it is confirmed.';

// Superior Reject
$strings['MailTitleSuperiorRejectToStudent'] = 'Notification: Your subscription to course %s has been refused';
$strings['MailContentSuperiorRejectToStudent'] = 'We regret to inform your subscription to course <strong>%s</strong> was NOT accepted. We hope this will not reduce your motivation and encourage you to register to another course or, on another occasion, this same course soon.';
$strings['MailTitleSuperiorRejectToSuperior'] = 'Confirmation: Rejection of subscription received for %s';
$strings['MailContentSuperiorRejectToSuperior'] = 'We have received and registered your rejection of subscription to course <strong>%s</strong> for your collaborator <strong>%s</strong>';

// Student Request
$strings['MailTitleStudentRequestToStudent'] = 'Notification: Subscription approval received';
$strings['MailContentStudentRequestToStudent'] = 'We have received and registered your subscription request to course <strong>%s</strong> starting on <strong>%s</strong>';
$strings['MailContentStudentRequestToStudentSecond'] = 'Your subscription is pending approval, first from your superior, then for the availability of vacancies. An email has been sent to your superior for review and approval. We will inform you when this situation changes.';
$strings['MailTitleStudentRequestToSuperior'] = 'Course subscription request from your collaborator';
$strings['MailContentStudentRequestToSuperior'] = 'We have received an subscription request of <strong>%s</strong> to course <strong>%s</strong>, starting on <strong>%s</strong>. Course details: <strong>%s</strong>.';
$strings['MailContentStudentRequestToSuperiorSecond'] = 'Your are welcome to accept or reject this subscription, clicking the corresponding button.';

// Student Request No Boss
$strings['MailTitleStudentRequestNoSuperiorToStudent'] = 'Your subscription request for %s has been received';
$strings['MailContentStudentRequestNoSuperiorToStudent'] = 'We have received and registered your subscription to course <strong>%s</strong> starting on <strong>%s</strong>.';
$strings['MailContentStudentRequestNoSuperiorToStudentSecond'] = 'Your subscription is pending availability of vacancies. You will get the results of your request approval (or rejection) soon.';
$strings['MailTitleStudentRequestNoSuperiorToAdmin'] = 'Subscription request of %s to course %s';
$strings['MailContentStudentRequestNoSuperiorToAdmin'] = 'The subscription of <strong>%s</strong> to course <strong>%s</strong> has been approved by default (no direct superior defined). You can <a href="%s">manage subscriptions here</strong></a>';

// Reminders
$strings['MailTitleReminderAdmin'] = 'Subscriptions to %s are pending confirmation';
$strings['MailContentReminderAdmin'] = 'The subscription requests for course <strong>%s</strong> are pending validation to be accepted. Please, go to <a href="%s">Administration page</a> to validate them.';
$strings['MailTitleReminderStudent'] = 'Information: Your subscription request is pending approval for course %s';
$strings['MailContentReminderStudent'] = 'This email is just to confirm we have received and registered your subscription request to course <strong>%s</strong>, starting on <strong>%s</strong>.';
$strings['MailContentReminderStudentSecond'] = 'Your subscription has not been approved by your superior yet, so we sent him a e-mail reminder.';
$strings['MailTitleReminderSuperior'] = 'Course subscription request for your collaborators';
$strings['MailContentReminderSuperior'] = 'We kindly remind you that we have received the subscription requests below to course <strong>%s</strong> from your collaborators. This course is starting on <strong>%s</strong>. Course details: <strong>%s</strong>.';
$strings['MailContentReminderSuperiorSecond'] = 'We invite you to accept or reject this subscription request by clicking the corresponding button for each collaborator.';
$strings['MailTitleReminderMaxSuperior'] = 'Reminder: Course subscription request for your collaborators';
$strings['MailContentReminderMaxSuperior'] = 'We kindly remind you that we have received the subscription requests below to course <strong>%s</strong> from your collaborators. This course is starting on <strong>%s</strong>. Course details: <strong>%s</strong>.';
$strings['MailContentReminderMaxSuperiorSecond'] = 'This course have limited vacancies and has received a high subscription request rate, So we recommend all areas to accept at most <strong>%s</strong> candidates. We invite you to accept or reject the inscription request by clicking the corresponding button for each collaborator.';

$strings['YouMustAcceptTermsAndConditions'] = 'To subscribe to course <strong>%s</strong>, you must accept these terms and conditions.';
