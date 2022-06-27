<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Class MeetingSettings. An instance of this class is included in each Meeting instance.
 */
class MeetingSettings
{
    use JsonDeserializableTrait;

    public const APPROVAL_TYPE_AUTOMATICALLY_APPROVE = 0;
    public const APPROVAL_TYPE_MANUALLY_APPROVE = 1;
    public const APPROVAL_TYPE_NO_REGISTRATION_REQUIRED = 2;

    public const REGISTRATION_TYPE_REGISTER_ONCE_ATTEND_ANY = 1;
    public const REGISTRATION_TYPE_REGISTER_EACH = 2;
    public const REGISTRATION_TYPE_REGISTER_ONCE_CHOOSE = 3;

    /** @var bool Start video when the host joins the meeting */
    public $host_video;

    /** @var bool Start video when participants join the meeting */
    public $participant_video;

    /** @var bool Host meeting in China */
    public $cn_meeting;

    /** @var bool Host meeting in India */
    public $in_meeting;

    /** @var bool Allow participants to join the meeting before the host starts the meeting.
     * Only used for scheduled or recurring meetings.
     */
    public $join_before_host;

    /** @var bool Mute participants upon entry */
    public $mute_upon_entry;

    /** @var bool Add watermark when viewing a shared screen */
    public $watermark;

    /** @var bool Use a personal meeting ID.
     * Only used for scheduled meetings and recurring meetings with no fixed time.
     */
    public $use_pmi;

    /** @var int Enable registration and set approval for the registration.
     * Note that this feature requires the host to be of **Licensed** user type.
     * **Registration cannot be enabled for a basic user.**
     */
    public $approval_type;

    /** @var int Used for recurring meeting with fixed time only. */
    public $registration_type;

    /** @var string either both, telephony or voip */
    public $audio;

    /** @var string either local, cloud or none */
    public $auto_recording;

    /** @var bool @deprecated only signed in users can join this meeting */
    public $enforce_login;

    /** @var string @deprecated only signed in users with specified domains can join meetings */
    public $enforce_login_domains;

    /** @var string Alternative host's emails or IDs: multiple values separated by a comma. */
    public $alternative_hosts;

    /** @var bool Close registration after event date */
    public $close_registration;

    /** @var bool Enable waiting room */
    public $waiting_room;

    /** @var bool undocumented */
    public $request_permission_to_unmute_participants;

    /** @var string[] List of global dial-in countries */
    public $global_dial_in_countries;

    /** @var GlobalDialInNumber[] Global Dial-in Countries/Regions */
    public $global_dial_in_numbers;

    /** @var string Contact name for registration */
    public $contact_name;

    /** @var string Contact email for registration */
    public $contact_email;

    /** @var bool Send confirmation email to registrants upon successful registration */
    public $registrants_confirmation_email;

    /** @var bool Send email notifications to registrants about approval, cancellation, denial of the registration.
     * The value of this field must be set to true in order to use the `registrants_confirmation_email` field.
     */
    public $registrants_email_notification;

    /** @var bool Only authenticated users can join meetings. */
    public $meeting_authentication;

    /** @var string Meeting authentication option id. */
    public $authentication_option;

    /** @var string
     * @see https://support.zoom.us/hc/en-us/articles/360037117472-Authentication-Profiles-for-Meetings-and-Webinars#h_5c0df2e1-cfd2-469f-bb4a-c77d7c0cca6f
     */
    public $authentication_domains;

    /** @var string
     * @see https://support.zoom.us/hc/en-us/articles/360037117472-Authentication-Profiles-for-Meetings-and-Webinars#h_5c0df2e1-cfd2-469f-bb4a-c77d7c0cca6f
     */
    public $authentication_name;

    /**
     * MeetingSettings constructor.
     */
    public function __construct()
    {
        $this->global_dial_in_countries = [];
        $this->global_dial_in_numbers = [];
    }

    /**
     * {@inheritdoc}
     */
    public function itemClass($propertyName)
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
