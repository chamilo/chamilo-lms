<?php
/* For licensing terms, see /vendor/license.txt */

/**
 * Class Clockworksms
 * This script handles incoming SMS information, process it and sends an SMS if everything is right.
 *
 * @package chamilo.plugin.clockworksms.lib
 *
 * @author  Imanol Losada <imanol.losada@beeznest.com>
 *
 * Clockworksms-Chamilo connector class
 */
class Clockworksms implements SmsPluginLibraryInterface
{
    public $apiKey;
    public $api;
    public $plugin_enabled = false;

    /**
     * Constructor (generates a connection to the API).
     *
     * @param   string  Clockworksms API key required to use the plugin
     */
    public function __construct($apiKey = null)
    {
        $plugin = ClockworksmsPlugin::create();
        $clockWorkSMSPlugin = $plugin->get('tool_enable');
        if (empty($apiKey)) {
            $clockWorkSMSApiKey = $plugin->get('api_key');
        } else {
            $clockWorkSMSApiKey = $apiKey;
        }
        $this->table = Database::get_main_table('user_field_values');
        if ($clockWorkSMSPlugin == true) {
            $this->apiKey = $clockWorkSMSApiKey;
            // Setting Clockworksms api
            if (!defined('CONFIG_SECURITY_API_KEY')) {
                define('CONFIG_SECURITY_API_KEY', $this->apiKey);
            }
            $trimmedApiKey = trim(CONFIG_SECURITY_API_KEY);
            if (!empty($trimmedApiKey)) {
                $this->api = new Clockwork(CONFIG_SECURITY_API_KEY);
            } else {
                $this->api = new Clockwork(' ');
                $recipient_name = api_get_person_name(
                    api_get_setting('administratorName'),
                    api_get_setting('administratorSurname'),
                    null,
                    PERSON_NAME_EMAIL_ADDRESS
                );
                $email_form = api_get_setting('emailAdministrator');
                $emailsubject = 'Clockworksms error';
                $emailbody = 'Key cannot be blank';
                $sender_name = $recipient_name;
                $email_admin = $email_form;
                api_mail_html(
                    $recipient_name,
                    $email_form,
                    $emailsubject,
                    $emailbody,
                    $sender_name,
                    $email_admin
                );
            }
            $this->plugin_enabled = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMobilePhoneNumberById($userId)
    {
        $mobilePhoneNumberExtraField = new ExtraField('user');
        $mobilePhoneNumberExtraField = $mobilePhoneNumberExtraField->get_handler_field_info_by_field_variable(
            'mobile_phone_number'
        );

        $mobilePhoneNumberExtraFieldValue = new ExtraFieldValue('user');
        $mobilePhoneNumberExtraFieldValue = $mobilePhoneNumberExtraFieldValue->get_values_by_handler_and_field_id(
            $userId,
            $mobilePhoneNumberExtraField['id']
        );

        return $mobilePhoneNumberExtraFieldValue['value'];
    }

    /**
     * send (sends an SMS to the user).
     *
     * @param   array   Data needed to send the SMS. It is mandatory to include the
     *                  'smsType' and 'userId' (or 'mobilePhoneNumber') fields at least.
     *                  More data may be neccesary depending on the message type
     * Example: $additional_parameters = array(
     *              'smsType' => EXAMPLE_SMS_TYPE,
     *              'userId' => $userId,
     *              'moreData' => $moreData
     *          );
     */
    public function send($additionalParameters)
    {
        $trimmedKey = trim(CONFIG_SECURITY_API_KEY);
        if (!empty($trimmedKey)) {
            $phoneExists = array_key_exists('mobilePhoneNumber', $additionalParameters);
            $to = $phoneExists ? $additionalParameters['mobilePhoneNumber'] : $this->getMobilePhoneNumberById($additionalParameters['userId']);

            $message = [
                'to' => $to,
                'message' => $this->getSms($additionalParameters),
            ];

            if (!empty($message['message'])) {
                $this->api->send($message);
            }
        }
    }

    /**
     * buildSms (builds an SMS from a template and data).
     *
     * @param ClockworksmsPlugin $plugin
     * @param Template           $tpl
     * @param string  Template file name
     * @param string $messageKey Text key from lang file
     * @param array  $parameters Data to fill message variables (if any)
     *
     * @return string
     */
    public function buildSms(
        $plugin,
        $tpl,
        $templateName,
        $messageKey,
        $parameters = []
    ) {
        // Send direct message with out using plugin get_lang
        if (isset($parameters['direct_message'])) {
            return $parameters['direct_message'];
        }

        $message = $plugin->get_lang($messageKey);
        if ($parameters !== null) {
            $message = vsprintf($message, $parameters);
        }

        return $message;
    }

    /**
     * getSms (returns an SMS message depending of its type).
     *
     * @param   array   Data needed to send the SMS. It is mandatory to include the
     *                  'smsType' and 'userId' (or 'mobilePhoneNumber') fields at least.
     *                  More data may be neccesary depending on the message type
     * Example: $additional_parameters = array(
     *              'smsType' => EXAMPLE_SMS_TYPE,
     *              'userId' => $userId,
     *              'moreData' => $moreData
     *          );
     *
     * @return string A ready to be sent SMS
     */
    public function getSms($additionalParameters)
    {
        $plugin = ClockworksmsPlugin::create();
        $tool_name = $plugin->get_lang('plugin_title');
        $tpl = new Template($tool_name);

        switch ($additionalParameters['smsType']) {
            case SmsPlugin::WELCOME_LOGIN_PASSWORD:
                $userInfo = api_get_user_info($additionalParameters['userId']);

                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'welcome_login_password.tpl',
                    'WelcomeXLoginXPasswordX',
                    [
                        api_get_setting('siteName'),
                        $userInfo['username'],
                        $additionalParameters['password'],
                    ]
                );
                break;
            case SmsPlugin::NEW_FILE_SHARED_COURSE_BY:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'new_file_shared_course_by.tpl',
                    'XNewFileSharedCourseXByX',
                    [
                        api_get_setting('siteName'),
                        $additionalParameters['courseTitle'],
                        $additionalParameters['userUsername'],
                    ]
                );
                break;
            case SmsPlugin::ACCOUNT_APPROVED_CONNECT:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'account_approved_connect.tpl',
                    'XAccountApprovedConnectX',
                    [
                        api_get_setting('siteName'),
                        $tpl->params['_p']['web'],
                    ]
                );
                break;
            case SmsPlugin::NEW_COURSE_BEEN_CREATED:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'new_course_been_created.tpl',
                    'XNewCourseXBeenCreatedX',
                    [
                        api_get_setting('siteName'),
                        $additionalParameters['courseName'],
                        $additionalParameters['creatorUsername'],
                    ]
                );
                break;
            case SmsPlugin::NEW_USER_SUBSCRIBED_COURSE:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'new_user_subscribed_course.tpl',
                    'XNewUserXSubscribedCourseX',
                    [
                        api_get_setting('siteName'),
                        $additionalParameters['userUsername'],
                        $additionalParameters['courseCode'],
                    ]
                );
                break;
            case SmsPlugin::NEW_COURSE_SUGGESTED_TEACHER:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'new_course_suggested_teacher.tpl',
                    'XNewCourseSuggestedTeacherX',
                    [
                        api_get_setting('siteName'),
                        $additionalParameters['userUsername'],
                    ]
                );
                break;
            case SmsPlugin::COURSE_OPENING_REQUEST_CODE_REGISTERED:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'course_opening_request_code_registered.tpl',
                    'XCourseOpeningRequestCodeXRegistered',
                    [
                        api_get_setting('siteName'),
                        $additionalParameters['courseCode'],
                    ]
                );
                break;
            case SmsPlugin::COURSE_OPENING_REQUEST_CODE_APPROVED:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'course_opening_request_course_code_approved.tpl',
                    'XCourseOpeningRequestCourseCodeXApproved',
                    [
                        api_get_setting('siteName'),
                        $additionalParameters['courseCode'],
                    ]
                );
                break;
            case SmsPlugin::COURSE_OPENING_REQUEST_CODE_REJECTED:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'request_open_course_code_rejected.tpl',
                    'XRequestOpenCourseCodeXReject',
                    [
                        api_get_setting('siteName'),
                        $additionalParameters['courseCode'],
                    ]
                );
                break;
            case SmsPlugin::COURSE_OPENING_REQUEST_CODE:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'course_opening_request_course_code.tpl',
                    'XCourseOpeningRequestCourseCodeX',
                    [
                        api_get_setting('siteName'),
                        $additionalParameters['courseCode'],
                    ]
                );
                break;
            case SmsPlugin::BEEN_SUBSCRIBED_COURSE:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'been_subscribed_course.tpl',
                    'XBeenSubscribedCourseX',
                    [
                        api_get_setting('siteName'),
                        $additionalParameters['courseTitle'],
                    ]
                );
                break;
            case SmsPlugin::ASSIGNMENT_BEEN_CREATED_COURSE:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'assignment_been_created_course.tpl',
                    'XAssignmentBeenCreatedCourseX',
                    [
                        api_get_setting('siteName'),
                        $additionalParameters['courseTitle'],
                    ]
                );
                break;
            case SmsPlugin::CERTIFICATE_NOTIFICATION:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'certificate_notification.tpl',
                    '',
                    $additionalParameters
                );
                break;
            // Message types to be implemented. Fill the array parameter with arguments.
            /*case SmsPlugin::ACCOUNT_CREATED_UPDATED_LOGIN_PASSWORD:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'account_created_updated_login_password.tpl',
                    'XAccountCreatedUpdatedLoginXPasswordX',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::PASSWORD_UPDATED_LOGIN_PASSWORD:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'password_updated_login_password.tpl',
                    'XPasswordUpdatedLoginXPasswordX',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::REQUESTED_PASSWORD_CHANGE:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'requested_password_change.tpl',
                    'XPasswordUpdatedLoginXPasswordX',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::RECEIVED_NEW_PERSONAL_MESSAGES:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'received_new_personal_messages.tpl',
                    'XReceivedNewPersonalMessages',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::NEW_USER_PENDING_APPROVAL:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'new_user_pending_approval.tpl',
                    'XNewUserXPendingApproval',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::POSTED_FORUM_COURSE:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'posted_forum_course.tpl',
                    'XXPostedForumXCourseX',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::CHECK_EMAIL_CONNECT_MORE_INFO:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'check_email_connect_more_info.tpl',
                    'XXXCheckEmailConnectMoreInfo',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::STUDENT_ANSWERED_TEST:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'student_answered_test.tpl',
                    'XXStudentXAnsweredTestX',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::STUDENT_ANSWERED_TEST_OPEN_QUESTION:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'student_answered_test_open_question.tpl',
                    'XXStudentXAnsweredTestXOpenQuestion',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::STUDENT_ANSWERED_TEST_VOICE_QUESTION:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'student_answered_test_voice_question.tpl',
                    'XXStudentXAnsweredTestXVoiceQuestion',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::ANSWER_OPEN_QUESTION_TEST_REVIEWED:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'answer_open_question_test_reviewed.tpl',
                    'XXAnswerOpenQuestionTestXReviewed',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::NEW_THREAD_STARTED_FORUM:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'new_thread_started_forum.tpl',
                    'XXNewThreadXStartedForumX',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::NEW_ANSWER_POSTED_FORUM:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'new_answer_posted_forum.tpl',
                    'XXNewAnswerPostedXForumX',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::NEW_SYSTEM_ANNOUNCEMENT_ADDED:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'new_system_announcement_added.tpl',
                    'XXNewSystemAnnouncementAdded',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::TEST_NEW_SYSTEM_ANNOUNCEMENT_ADDED:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'test_new_system_announcement_added.tpl',
                    'XTestXNewSystemAnnouncementAdded',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::SYSTEM_ANNOUNCEMENT_UPDATE:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'system_announcement_update.tpl',
                    'XXSystemAnnouncementUpdate',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::TEST_SYSTEM_ANNOUNCEMENT_UPDATE:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'test_system_announcement_update.tpl',
                    'XXSystemAnnouncementUpdate',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::USER_UPLOADED_ASSIGNMENT_COURSE_STUDENT_SUBMITS_PAPER:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'user_uploaded_assignment_course_student_submits_paper.tpl',
                    'XUserXUploadedAssignmentXCourseXStudentSubmitsPaper',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::USER_UPLOADED_ASSIGNMENT_CHECK_STUDENT_SUBMITS_PAPER:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'user_uploaded_assignment_check_student_submits_paper.tpl',
                    'XUserXUploadedAssignmentXCheckXStudentSubmitsPaper',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::USER_UPLOADED_ASSIGNMENT_COURSE:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'user_uploaded_assignment_course.tpl',
                    'XUserXUploadedAssignmentXCourseX',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::USER_UPLOADED_ASSIGNMENT_CHECK:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'user_uploaded_assignment_check.tpl',
                    'XUserXUploadedAssignmentXCheckX',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::SUBSCRIBED_SESSION:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'subscribed_session.tpl',
                    'XSubscribedSessionX',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::SUBSCRIBED_SESSION_CSV:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'subscribed_session_csv.tpl',
                    'XSubscribedSessionXCSV',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::USER_SUGGESTED_BE_FRIENDS:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'user_suggested_be_friends.tpl',
                    'XUserXSuggestedBeFriends',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::USER_ANSWERED_INBOX_MESSAGE:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'user_answered_inbox_message.tpl',
                    'XUserXAnsweredInboxMessage',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::BEEN_INVITED_JOIN_GROUP:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'been_invited_join_group.tpl',
                    'XBeenInvitedJoinGroupX',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::MESSAGES_SENT_EDITED_GROUP_EDITED:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'messages_sent_edited_group_edited.tpl',
                    'XMessagesSentEditedGroupXEdited',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::MESSAGES_SENT_EDITED_GROUP_ADDED:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'messages_sent_edited_group_added.tpl',
                    'XMessagesSentEditedGroupXAdded',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::BEEN_INVITED_COMPLETE_SURVEY_COURSE:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'been_invited_complete_survey_course.tpl',
                    'XBeenInvitedCompleteSurveyXCourseX',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::REMINDER_ASSIGNMENT_COURSE_DUE:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'reminder_assignment_course_due.tpl',
                    'XReminderAssignmentXCourseXDue',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            /*case SmsPlugin::USER_DETAILS_MODIFIED:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'user_details_modified.tpl',
                    'XUserDetailsModified',
                    array(
                        api_get_setting('siteName')
                    )
                );
                break;*/
            default:
                return '';
        }
    }
}
