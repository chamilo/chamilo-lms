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
    public $externalRoomType = 'chamilolms';
    private $table;

    public function __construct($id)
    {
        $this->table = Database::get_main_table('plugin_openmeetings');
        if (!empty($id)) {
            $roomData = Database::select('*', $this->table, array('where' => array('id = ?' => $id)), 'first');
            $this->rooms_id = $roomData['room_id'];
            $this->status = $roomData['status'];
            $this->name = $roomData['meeting_name'];
            $this->comment = $roomData['welcome_msg'];
        }
    }
}