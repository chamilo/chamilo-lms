<?php
/* For licensing terms, see /license.txt */

/**
 * Class clockworksms
 * This script initiates a video conference session, calling the Clockworksms
 * API
 * @package chamilo.plugin.clockworksms
 *
 * Clockworksms-Chamilo connector class
 */

class Clockworksms
{
    public $apiKey;
    public $api;
    public $plugin_enabled = false;

    /**
     * Constructor (generates a connection to the API)
     * @param string $apiKey
     */
    public function __construct($apiKey = null)
    {
        $plugin = ClockworksmsPlugin::create();
        $clockworksms_plugin = $plugin->get('tool_enable');
        if (empty($apiKey)) {
            $clockworksmsApiKey = $plugin->get('api_key');
        } else {
            $clockworksmsApiKey = $apiKey;
        }
        $this->table = Database::get_main_table('user_field_values');
        if ($clockworksms_plugin == true) {
            $this->apiKey = $clockworksmsApiKey;
            // Setting Clockworksms api
            define('CONFIG_SECURITY_API_KEY', $this->apiKey);
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
                $email_form = get_setting('emailAdministrator');
                $emailsubject = 'Clockworksms error';
                $emailbody = 'Key cannot be blank';
                $sender_name = $recipient_name;
                $email_admin = $email_form;
                api_mail_html($recipient_name, $email_form, $emailsubject, $emailbody, $sender_name, $email_admin);
            }
            $this->plugin_enabled = true;
        }
    }

    /**
     * @param int $userId
     * @return mixed
     */
    private function getMobilePhoneNumberById($userId)
    {
        require_once api_get_path(LIBRARY_PATH).'extra_field.lib.php';
        require_once api_get_path(LIBRARY_PATH).'extra_field_value.lib.php';

        $mobilePhoneNumberExtraField = new ExtraField('user');
        $mobilePhoneNumberExtraField = $mobilePhoneNumberExtraField->get_handler_field_info_by_field_variable('mobile_phone_number');

        $mobilePhoneNumberExtraFieldValue = new ExtraFieldValue('user');
        $mobilePhoneNumberExtraFieldValue = $mobilePhoneNumberExtraFieldValue->get_values_by_handler_and_field_id($userId, $mobilePhoneNumberExtraField['id']);

        return $mobilePhoneNumberExtraFieldValue['field_value'];
    }

    public function send($additionalParameters)
    {
        $trimmedKey = trim(CONFIG_SECURITY_API_KEY);
        if (!empty($trimmedKey)) {
            $message = array(
                "to" => array_key_exists("mobilePhoneNumber",$additionalParameters) ?
                    $additionalParameters['mobilePhoneNumber'] :
                    $this->getMobilePhoneNumberById($additionalParameters['userId']),
                "message" => $this->getSms($additionalParameters)
            );

            if (!empty($message['message'])) {
                $result = $this->api->send($message);

                // Commented for future message logging / tracking purposes
                /*if( $result["success"] ) {
                    echo "Message sent - ID: " . $result["id"];
                } else {
                    echo "Message failed - Error: " . $result["error_message"];
                }*/
            }

        }
    }

    public function buildSms($plugin, $tpl, $templateName, $messageKey, $parameters = null)
    {
        $result = Database::select(
            'selected_value',
            'settings_current',
            array(
                'where'=> array('variable = ?' => array('clockworksms_message'.$messageKey))
            )
        );

        if (empty($result)) {
            $tpl->assign('message', '');
        } else {
            $templatePath = 'clockworksms/sms_templates/';
            $content = $tpl->fetch($templatePath.$templateName);
            $message = $plugin->get_lang($messageKey);
            if ($parameters !== null) {
                $message = vsprintf($message, $parameters);
            }
            $tpl->assign('message', $message);
        }

        return $tpl->params['message'];
    }

    public function getSms($additionalParameters)
    {
        $plugin = ClockworksmsPlugin::create();
        $tool_name = $plugin->get_lang('plugin_title');
        $tpl = new Template($tool_name);

        switch (constant('ClockworksmsPlugin::'.$additionalParameters['smsType'])) {
            case ClockworksmsPlugin::WELCOME_LOGIN_PASSWORD:
                $userInfo = api_get_user_info($additionalParameters['userId']);
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'welcome_login_password.tpl',
                    'WelcomeXLoginXPasswordX',
                    array(
                        api_get_setting('siteName'),
                        $userInfo['username'],
                        $additionalParameters['password']
                    )
                );
                break;
            case ClockworksmsPlugin::NEW_FILE_SHARED_COURSE_BY:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'new_file_shared_course_by.tpl',
                    'XNewFileSharedCourseXByX',
                    array(
                        api_get_setting('siteName'),
                        $additionalParameters['courseTitle'],
                        $additionalParameters['userUsername']
                    )
                );
                break;
            case ClockworksmsPlugin::ACCOUNT_APPROVED_CONNECT:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'account_approved_connect.tpl',
                    'XAccountApprovedConnectX',
                    array(
                        api_get_setting('siteName'),
                        $tpl->params['_p']['web']
                    )
                );
                break;
            case ClockworksmsPlugin::NEW_COURSE_BEEN_CREATED:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'new_course_been_created.tpl',
                    'XNewCourseXBeenCreatedX',
                    array(
                        api_get_setting('siteName'),
                        $additionalParameters['courseName'],
                        $additionalParameters['creatorUsername']
                    )
                );
                break;
            case ClockworksmsPlugin::NEW_USER_SUBSCRIBED_COURSE:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'new_user_subscribed_course.tpl',
                    'XNewUserXSubscribedCourseX',
                    array(
                        api_get_setting('siteName'),
                        $additionalParameters['userUsername'],
                        $additionalParameters['courseCode']
                    )
                );
                break;
            case ClockworksmsPlugin::NEW_COURSE_SUGGESTED_TEACHER:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'new_course_suggested_teacher.tpl',
                    'XNewCourseSuggestedTeacherX',
                    array(
                        api_get_setting('siteName'),
                        $additionalParameters['userUsername']
                    )
                );
                break;
            case ClockworksmsPlugin::COURSE_OPENING_REQUEST_CODE_REGISTERED:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'course_opening_request_code_registered.tpl',
                    'XCourseOpeningRequestCodeXRegistered',
                    array(
                        api_get_setting('siteName'),
                        $additionalParameters['courseCode']
                    )
                );
                break;
            case ClockworksmsPlugin::COURSE_OPENING_REQUEST_CODE_APPROVED:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'course_opening_request_course_code_approved.tpl',
                    'XCourseOpeningRequestCourseCodeXApproved',
                    array(
                        api_get_setting('siteName'),
                        $additionalParameters['courseCode']
                    )
                );
                break;
            case ClockworksmsPlugin::COURSE_OPENING_REQUEST_CODE_REJECTED:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'request_open_course_code_rejected.tpl',
                    'XRequestOpenCourseCodeXReject',
                    array(
                        api_get_setting('siteName'),
                        $additionalParameters['courseCode']
                    )
                );
                break;
            case ClockworksmsPlugin::COURSE_OPENING_REQUEST_CODE:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'course_opening_request_course_code.tpl',
                    'XCourseOpeningRequestCourseCodeX',
                    array(
                        api_get_setting('siteName'),
                        $additionalParameters['courseCode']
                    )
                );
                break;
            case ClockworksmsPlugin::BEEN_SUBSCRIBED_COURSE:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'been_subscribed_course.tpl',
                    'XBeenSubscribedCourseX',
                    array(
                        api_get_setting('siteName'),
                        $additionalParameters['courseTitle']
                    )
                );
                break;
            case ClockworksmsPlugin::ASSIGNMENT_BEEN_CREATED_COURSE:
                return $this->buildSms(
                    $plugin,
                    $tpl,
                    'assignment_been_created_course.tpl',
                    'XAssignmentBeenCreatedCourseX',
                    array(
                        api_get_setting('siteName'),
                        $additionalParameters['courseTitle']
                    )
                );
                break;
            // Message types to be implemented. Fill the array parameter with arguments.
            /*case ClockworksmsPlugin::ACCOUNT_CREATED_UPDATED_LOGIN_PASSWORD:
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
            /*case ClockworksmsPlugin::PASSWORD_UPDATED_LOGIN_PASSWORD:
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
            /*case ClockworksmsPlugin::REQUESTED_PASSWORD_CHANGE:
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
            /*case ClockworksmsPlugin::RECEIVED_NEW_PERSONAL_MESSAGES:
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
            /*case ClockworksmsPlugin::NEW_USER_PENDING_APPROVAL:
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
            /*case ClockworksmsPlugin::POSTED_FORUM_COURSE:
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
            /*case ClockworksmsPlugin::CHECK_EMAIL_CONNECT_MORE_INFO:
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
            /*case ClockworksmsPlugin::STUDENT_ANSWERED_TEST:
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
            /*case ClockworksmsPlugin::STUDENT_ANSWERED_TEST_OPEN_QUESTION:
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
            /*case ClockworksmsPlugin::STUDENT_ANSWERED_TEST_VOICE_QUESTION:
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
            /*case ClockworksmsPlugin::ANSWER_OPEN_QUESTION_TEST_REVIEWED:
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
            /*case ClockworksmsPlugin::NEW_THREAD_STARTED_FORUM:
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
            /*case ClockworksmsPlugin::NEW_ANSWER_POSTED_FORUM:
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
            /*case ClockworksmsPlugin::NEW_SYSTEM_ANNOUNCEMENT_ADDED:
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
            /*case ClockworksmsPlugin::TEST_NEW_SYSTEM_ANNOUNCEMENT_ADDED:
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
            /*case ClockworksmsPlugin::SYSTEM_ANNOUNCEMENT_UPDATE:
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
            /*case ClockworksmsPlugin::TEST_SYSTEM_ANNOUNCEMENT_UPDATE:
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
            /*case ClockworksmsPlugin::USER_UPLOADED_ASSIGNMENT_COURSE_STUDENT_SUBMITS_PAPER:
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
            /*case ClockworksmsPlugin::USER_UPLOADED_ASSIGNMENT_CHECK_STUDENT_SUBMITS_PAPER:
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
            /*case ClockworksmsPlugin::USER_UPLOADED_ASSIGNMENT_COURSE:
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
            /*case ClockworksmsPlugin::USER_UPLOADED_ASSIGNMENT_CHECK:
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
            /*case ClockworksmsPlugin::SUBSCRIBED_SESSION:
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
            /*case ClockworksmsPlugin::SUBSCRIBED_SESSION_CSV:
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
            /*case ClockworksmsPlugin::USER_SUGGESTED_BE_FRIENDS:
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
            /*case ClockworksmsPlugin::USER_ANSWERED_INBOX_MESSAGE:
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
            /*case ClockworksmsPlugin::BEEN_INVITED_JOIN_GROUP:
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
            /*case ClockworksmsPlugin::MESSAGES_SENT_EDITED_GROUP_EDITED:
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
            /*case ClockworksmsPlugin::MESSAGES_SENT_EDITED_GROUP_ADDED:
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
            /*case ClockworksmsPlugin::BEEN_INVITED_COMPLETE_SURVEY_COURSE:
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
            /*case ClockworksmsPlugin::REMINDER_ASSIGNMENT_COURSE_DUE:
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
            /*case ClockworksmsPlugin::USER_DETAILS_MODIFIED:
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
