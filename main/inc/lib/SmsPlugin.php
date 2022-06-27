<?php
/* For licensing terms, see /license.txt */

/**
 * Class SmsPlugin.
 *
 * @author Julio Montoya
 */
class SmsPlugin extends Plugin
{
    public const WELCOME_LOGIN_PASSWORD = 0;
    public const NEW_FILE_SHARED_COURSE_BY = 1;
    public const ACCOUNT_APPROVED_CONNECT = 2;
    public const NEW_COURSE_BEEN_CREATED = 3;
    public const NEW_USER_SUBSCRIBED_COURSE = 4;
    public const NEW_COURSE_SUGGESTED_TEACHER = 5;
    public const COURSE_OPENING_REQUEST_CODE_REGISTERED = 6;
    public const COURSE_OPENING_REQUEST_CODE_APPROVED = 7;
    public const COURSE_OPENING_REQUEST_CODE_REJECTED = 8;
    public const COURSE_OPENING_REQUEST_CODE = 9;
    public const BEEN_SUBSCRIBED_COURSE = 10;
    public const ASSIGNMENT_BEEN_CREATED_COURSE = 11;
    public const ACCOUNT_CREATED_UPDATED_LOGIN_PASSWORD = 12;
    public const PASSWORD_UPDATED_LOGIN_PASSWORD = 13;
    public const REQUESTED_PASSWORD_CHANGE = 14;
    public const RECEIVED_NEW_PERSONAL_MESSAGES = 15;
    public const NEW_USER_PENDING_APPROVAL = 16;
    public const POSTED_FORUM_COURSE = 17;
    public const CHECK_EMAIL_CONNECT_MORE_INFO = 18;
    public const STUDENT_ANSWERED_TEST = 19;
    public const STUDENT_ANSWERED_TEST_OPEN_QUESTION = 20;
    public const STUDENT_ANSWERED_TEST_VOICE_QUESTION = 21;
    public const ANSWER_OPEN_QUESTION_TEST_REVIEWED = 22;
    public const NEW_THREAD_STARTED_FORUM = 23;
    public const NEW_ANSWER_POSTED_FORUM = 24;
    public const NEW_SYSTEM_ANNOUNCEMENT_ADDED = 25;
    public const TEST_NEW_SYSTEM_ANNOUNCEMENT_ADDED = 26;
    public const SYSTEM_ANNOUNCEMENT_UPDATE = 27;
    public const TEST_SYSTEM_ANNOUNCEMENT_UPDATE = 28;
    public const USER_UPLOADED_ASSIGNMENT_COURSE_STUDENT_SUBMITS_PAPER = 29;
    public const USER_UPLOADED_ASSIGNMENT_CHECK_STUDENT_SUBMITS_PAPER = 30;
    public const USER_UPLOADED_ASSIGNMENT_COURSE = 31;
    public const USER_UPLOADED_ASSIGNMENT_CHECK = 32;
    public const SUBSCRIBED_SESSION = 33;
    public const SUBSCRIBED_SESSION_CSV = 34;
    public const USER_SUGGESTED_BE_FRIENDS = 35;
    public const USER_ANSWERED_INBOX_MESSAGE = 36;
    public const BEEN_INVITED_JOIN_GROUP = 37;
    public const MESSAGES_SENT_EDITED_GROUP_EDITED = 38;
    public const MESSAGES_SENT_EDITED_GROUP_ADDED = 39;
    public const BEEN_INVITED_COMPLETE_SURVEY_COURSE = 40;
    public const REMINDER_ASSIGNMENT_COURSE_DUE = 41;
    public const USER_DETAILS_MODIFIED = 42;
    public const CERTIFICATE_NOTIFICATION = 43;

    public $isCoursePlugin = true;
    public $isMailPlugin = true;

    /**
     * getSmsTypeOptions (returns all SMS types).
     *
     * @return array SMS types
     */
    public function getSmsTypeOptions()
    {
        return [
            'MessageWelcomeXLoginXPasswordX',
            'MessageXNewFileSharedCourseXByX',
            'MessageXAccountApprovedConnectX',
            'MessageXNewCourseXBeenCreatedX',
            'MessageXNewUserXSubscribedCourseX',
            'MessageXNewCourseSuggestedTeacherX',
            'MessageXCourseOpeningRequestCodeXRegistered',
            'MessageXCourseOpeningRequestCourseCodeXApproved',
            'MessageXRequestOpenCourseCodeXReject',
            'MessageXCourseOpeningRequestCourseCodeX',
            'MessageXBeenSubscribedCourseX',
            'MessageXAssignmentBeenCreatedCourseX',
            'MessageXAccountCreatedUpdatedLoginXPasswordX',
            'MessageXPasswordUpdatedLoginXPasswordX',
            'MessageXRequestedPasswordChange',
            'MessageXReceivedNewPersonalMessages',
            'MessageXNewUserXPendingApproval',
            'MessageXXPostedForumXCourseX',
            'MessageXXXCheckEmailConnectMoreInfo',
            'MessageXXStudentXAnsweredTestX',
            'MessageXXStudentXAnsweredTestXOpenQuestion',
            'MessageXXStudentXAnsweredTestXVoiceQuestion',
            'MessageXXAnswerOpenQuestionTestXReviewed',
            'MessageXXNewThreadXStartedForumX',
            'MessageXXNewAnswerPostedXForumX',
            'MessageXXNewSystemAnnouncementAdded',
            'MessageXTestXNewSystemAnnouncementAdded',
            'MessageXXSystemAnnouncementUpdate',
            'MessageXTestXSystemAnnouncementUpdate',
            'MessageXUserXUploadedAssignmentXCourseXStudentSubmitsPaper',
            'MessageXUserXUploadedAssignmentXCheckXStudentSubmitsPaper',
            'MessageXUserXUploadedAssignmentXCourseX',
            'MessageXUserXUploadedAssignmentXCheckX',
            'MessageXSubscribedSessionX',
            'MessageXSubscribedSessionXCSV',
            'MessageXUserXSuggestedBeFriends',
            'MessageXUserXAnsweredInboxMessage',
            'MessageXBeenInvitedJoinGroupX',
            'MessageXMessagesSentEditedGroupXEdited',
            'MessageXMessagesSentEditedGroupXAdded',
            'MessageXBeenInvitedCompleteSurveyXCourseX',
            'MessageXReminderAssignmentXCourseXDue',
            'MessageXUserDetailsModified',
        ];
    }

    /**
     * install (installs the plugin).
     */
    public function install()
    {
        $this->addMobilePhoneNumberField();
    }

    /**
     * addMobilePhoneNumberField (adds a mobile phone number field if it is not
     * already created).
     */
    private function addMobilePhoneNumberField()
    {
        $extraField = new ExtraField('user');
        $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable('mobile_phone_number');

        if (empty($extraFieldInfo)) {
            $extraField->save([
                'field_type' => 1,
                'variable' => 'mobile_phone_number',
                'display_text' => $this->get_lang('mobile_phone_number'),
                'default_value' => null,
                'field_order' => 2,
                'visible' => 1,
                'changeable' => 1,
                'filter' => null,
            ]);
        }
    }
}
