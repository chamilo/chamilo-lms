<?php
/**
 * Definition for the room class
 * @package chamilo.plugin.videoconference
 */
namespace Chamilo\Plugin\OpenMeetings;
/**
 * Class room
 */
class Room
{
    public $SID;
    public $rooms_id;
    public $status;
    public $name;
    public $roomtypes_id;
    public $comment;
    public $numberOfPartizipants;
    public $ispublic;
    public $appointment;
    public $isDemoRoom;
    public $demoTime;
    public $isModeratedRoom;
    public $externalRoomType;

    public function __construct()
    {
    }
}