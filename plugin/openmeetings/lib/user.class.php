<?php
/**
 * Definition of the OpenMeetings user class.
 *
 * @package chamilo.plugin.openmeetings
 */

namespace Chamilo\Plugin\OpenMeetings;

/**
 * Class User.
 */
class User
{
    public $SID;
    public $username;
    public $userpass;
    public $firstname;
    public $lastname;
    public $profilePictureUrl;
    public $email;
    public $externalUserId;
    public $externalUserType;
    public $room_id;
    public $becomeModeratorAsInt;
    public $showAudioVideoTestAsInt;
    public $allowRecording;
    public $recording_id;

    public function __construct()
    {
    }
}
