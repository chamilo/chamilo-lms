<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

class WebinarSettings
{
    use JsonDeserializableTrait;

    public const APPROVAL_TYPE_AUTOMATICALLY_APPROVE = 0;
    public const APPROVAL_TYPE_MANUALLY_APPROVE = 1;
    public const APPROVAL_TYPE_NO_REGISTRATION_REQUIRED = 2;

    public const REGISTRATION_TYPE_REGISTER_ONCE_ATTEND_ANY = 1;
    public const REGISTRATION_TYPE_REGISTER_EACH = 2;
    public const REGISTRATION_TYPE_REGISTER_ONCE_CHOOSE = 3;

    /**
     * @var bool
     */
    public $host_video;
    /**
     * @var bool
     */
    public $panelists_video;
    /**
     * @var int
     */
    public $approval_type;
    /**
     * @var string
     */
    public $audio;
    /**
     * @var string
     */
    public $auto_recording;
    /**
     * @var bool
     */
    public $enforce_login;
    /**
     * @var string
     */
    public $enforce_login_domains;
    /**
     * @var string
     */
    public $alternative_hosts;
    /**
     * @var bool
     */
    public $close_registration;
    /**
     * @var bool
     */
    public $show_share_button;
    /**
     * @var bool
     */
    public $allow_multiple_devices;
    /**
     * @var bool
     */
    public $practice_session;
    /**
     * @var bool
     */
    public $hd_video;
    /**
     * @var object
     */
    public $question_answer;
    /**
     * @var bool
     */
    public $registrants_confirmation_email;
    /**
     * @var bool
     */
    public $on_demand;
    /**
     * @var bool
     */
    public $request_permission_to_unmute_participants;
    /**
     * @var array<int,string>
     */
    public $global_dial_in_countries;
    /**
     * @var array<int,GlobalDialInNumber>
     */
    public $global_dial_in_numbers;
    /**
     * @var string
     */
    public $contact_name;
    /**
     * @var string
     */
    public $contact_email;
    /**
     * @var int
     */
    public $registrants_restrict_number;
    /**
     * @var bool
     */
    public $registrants_email_notification;
    /**
     * @var bool
     */
    public $post_webinar_survey;
    /**
     * @var bool
     */
    public $meeting_authentication;
    /**
     * @var QuestionAndAnswer
     */
    public $question_and_answer;
    /**
     * @var bool
     */
    public $hd_video_for_attendees;
    /**
     * @var bool
     */
    public $send_1080p_video_to_attendees;
    /**
     * @var string
     */
    public $email_language;
    /**
     * @var bool
     */
    public $panelists_invitation_email_notification;
    /**
     * @var FollowUpUsers
     */
    public $attendees_and_panelists_reminder_email_notification;
    /**
     * @var FollowUpUsers
     */
    public $follow_up_attendees_email_notification;
    /**
     * @var FollowUpUsers
     */
    public $follow_up_absentees_email_notification;

    /**
     * @var int
     */
    public $registration_type;
    /**
     * @var string
     */
    public $auto;
    /**
     * @var string
     */
    public $survey_url;
    /**
     * @var string
     */
    public $authentication_option;
    /**
     * @var string
     */
    public $authentication_domains;
    /**
     * @var string
     */
    public $authentication_name;

    public function __construct()
    {
        $this->global_dial_in_countries = [];
        $this->global_dial_in_numbers = [];
        $this->question_and_answer = new QuestionAndAnswer();
        $this->attendees_and_panelists_reminder_email_notification = new FollowUpUsers();
        $this->follow_up_absentees_email_notification = new FollowUpUsers();
        $this->follow_up_attendees_email_notification = new FollowUpUsers();
    }

    public function itemClass($propertyName): string
    {
        if ('global_dial_in_countries' === $propertyName) {
            return 'string';
        }

        if ('global_dial_in_numbers' === $propertyName) {
            return GlobalDialInNumber::class;
        }

        throw new Exception("No such array property $propertyName");
    }
}
