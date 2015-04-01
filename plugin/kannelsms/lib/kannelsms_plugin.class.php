<?php
/* For licensing terms, see /vendor/license.txt */

/**
 * Class KannelsmsPlugin
 * This script contains SMS type constants and basic plugin functions
 * 
 * @package chamilo.plugin.kannelsms.lib
 * @author  Imanol Losada <imanol.losada@beeznest.com>
 */
class KannelsmsPlugin extends Plugin
{
    const WELCOME_LOGIN_PASSWORD = 0;
    const NEW_FILE_SHARED_COURSE_BY = 1;
    const ACCOUNT_APPROVED_CONNECT = 2;
    const NEW_COURSE_BEEN_CREATED = 3;
    const NEW_USER_SUBSCRIBED_COURSE = 4;
    const NEW_COURSE_SUGGESTED_TEACHER = 5;
    const COURSE_OPENING_REQUEST_CODE_REGISTERED = 6;
    const COURSE_OPENING_REQUEST_CODE_APPROVED = 7;
    const COURSE_OPENING_REQUEST_CODE_REJECTED = 8;
    const COURSE_OPENING_REQUEST_CODE = 9;
    const BEEN_SUBSCRIBED_COURSE = 10;
    const ASSIGNMENT_BEEN_CREATED_COURSE = 11;
    const ACCOUNT_CREATED_UPDATED_LOGIN_PASSWORD = 12;
    const PASSWORD_UPDATED_LOGIN_PASSWORD = 13;
    const REQUESTED_PASSWORD_CHANGE = 14;
    const RECEIVED_NEW_PERSONAL_MESSAGES = 15;
    const NEW_USER_PENDING_APPROVAL = 16;
    const POSTED_FORUM_COURSE = 17;
    const CHECK_EMAIL_CONNECT_MORE_INFO = 18;
    const STUDENT_ANSWERED_TEST = 19;
    const STUDENT_ANSWERED_TEST_OPEN_QUESTION = 20;
    const STUDENT_ANSWERED_TEST_VOICE_QUESTION = 21;
    const ANSWER_OPEN_QUESTION_TEST_REVIEWED = 22;
    const NEW_THREAD_STARTED_FORUM = 23;
    const NEW_ANSWER_POSTED_FORUM = 24;
    const NEW_SYSTEM_ANNOUNCEMENT_ADDED = 25;
    const TEST_NEW_SYSTEM_ANNOUNCEMENT_ADDED = 26;
    const SYSTEM_ANNOUNCEMENT_UPDATE = 27;
    const TEST_SYSTEM_ANNOUNCEMENT_UPDATE = 28;
    const USER_UPLOADED_ASSIGNMENT_COURSE_STUDENT_SUBMITS_PAPER = 29;
    const USER_UPLOADED_ASSIGNMENT_CHECK_STUDENT_SUBMITS_PAPER = 30;
    const USER_UPLOADED_ASSIGNMENT_COURSE = 31;
    const USER_UPLOADED_ASSIGNMENT_CHECK = 32;
    const SUBSCRIBED_SESSION = 33;
    const SUBSCRIBED_SESSION_CSV = 34;
    const USER_SUGGESTED_BE_FRIENDS = 35;
    const USER_ANSWERED_INBOX_MESSAGE = 36;
    const BEEN_INVITED_JOIN_GROUP = 37;
    const MESSAGES_SENT_EDITED_GROUP_EDITED = 38;
    const MESSAGES_SENT_EDITED_GROUP_ADDED = 39;
    const BEEN_INVITED_COMPLETE_SURVEY_COURSE = 40;
    const REMINDER_ASSIGNMENT_COURSE_DUE = 41;
    const USER_DETAILS_MODIFIED = 42;

    public $isCoursePlugin = true;
    public $isMailPlugin = true;

    /**
     * create (a singleton function that ensures KannelsmsPlugin instance is
     * created only once. If it is already created, it returns the instance)
     * @return  object  KannelsmsPlugin instance
     */
    public static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    /**
     * Constructor
     * @return  void
     */
    protected function __construct()
    {
        $fields = array(
            'tool_enable' => 'boolean',
            'hostAddress' => 'text',
            'username' => 'text',
            'password' => 'text',
            'from' => 'text'
        );
        $smsTypeOptions = $this->getSmsTypeOptions();
        foreach ($smsTypeOptions as $smsTypeOption) {
            $fields[$smsTypeOption] = 'checkbox';
        }
        parent::__construct('0.1', 'Imanol Losada', $fields);
    }

    /**
     * addMobilePhoneNumberField (adds a mobile phone number field if it is not
     * already created)
     * @return  void
     */
    private function addMobilePhoneNumberField()
    {
        $result = Database::select('mobile_phone_number', 'user_field');
        if (empty($result)) {
            require_once api_get_path(LIBRARY_PATH).'extra_field.lib.php';
            $extraField = new Extrafield('user');
            $extraField->save(array(
                'field_type' => 1,
                'field_variable' => 'mobile_phone_number',
                'field_display_text' => $this->get_lang('mobile_phone_number'),
                'field_default_value' => null,
                'field_order' => 2,
                'field_visible' => 1,
                'field_changeable' => 1,
                'field_filter' => null
            ));
        }
    }

    /**
     * getSmsTypeOptions (returns all SMS types)
     * @return  array   SMS types
     */
    private function getSmsTypeOptions()
    {
        return array(
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
            'MessageXUserDetailsModified'
        );
    }

    /**
     * install (installs the plugin)
     * @return  void
     */
    public function install()
    {
        $this->addMobilePhoneNumberField();
    }
    /**
     * install (uninstalls the plugin and removes all plugin's tables and/or rows)
     * @return  void
     */
    public function uninstall()
    {
        $tSettings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        $sql = "DELETE FROM $tSettings WHERE subkey = 'kannelsms'";
        Database::query($sql);
    }
}
