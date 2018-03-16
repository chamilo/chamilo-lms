<?php
/**
 * Definition for the room class.
 *
 * @package chamilo.plugin.videoconference
 */

namespace Chamilo\Plugin\OpenMeetings;

/**
 * Class room.
 */
class Room
{
    public $SID;
    /**
     * Defining plural and non-plural because of inconsistency in OpenMeetings.
     */
    public $rooms_id;
    public $room_id;
    /**
     * Status is false for closed, true for open.
     */
    public $status = false;
    public $name;
    /**
     * Room types are described here http://openmeetings.apache.org/RoomService.html#addRoomWithModerationAndExternalType
     * 1 = Conference, 2 = Audience, 3 = Restricted, 4 = Interview
     * $roomTypeId = ( $this->isTeacher() ) ? 1 : 2 ;.
     */
    public $roomtypes_id = 1;
    public $comment;
    public $numberOfPartizipants = 40;
    public $ispublic = false;
    public $appointment = false;
    public $isDemoRoom = false;
    public $demoTime = 0;
    public $isModeratedRoom = true;
    public $externalRoomType = 'chamilolms';
    public $allowUserQuestions = false;
    public $isAudioOnly = false;
    public $waitForRecording = true;
    public $allowRecording = true;
    public $chamiloCourseId;
    public $chamiloSessionId;
    private $table;

    public function __construct()
    {
        $this->table = \Database::get_main_table('plugin_openmeetings');
        global $_configuration;
        $this->name = 'C'.api_get_course_int_id().'-'.api_get_session_id();
        $accessUrl = api_get_access_url($_configuration['access_url']);
        $this->externalRoomType = substr($accessUrl['url'], strpos($accessUrl['url'], '://') + 3, -1);
        if (strcmp($this->externalRoomType, 'localhost') == 0) {
            $this->externalRoomType = substr(api_get_path(WEB_PATH), strpos(api_get_path(WEB_PATH), '://') + 3, -1);
        }
        $this->externalRoomType = 'chamilolms.'.$this->externalRoomType;
    }

    /**
     * Get Room by id.
     *
     * @param int $id
     */
    public function getRoom($id)
    {
        if (!empty($id)) {
            $roomData = \Database::select('*', $this->table, ['where' => ['id = ?' => $id]], 'first');
            if (!empty($roomData)) {
                $this->rooms_id = $this->room_id = $roomData['room_id'];
                $this->status = $roomData['status'];
                $this->name = $roomData['meeting_name'];
                $this->comment = $roomData['welcome_msg'];
                $this->allowRecording = $roomData['record'];
                $this->chamiloCourseId = $roomData['c_id'];
                $this->chamiloSessionId = $roomData['session_id'];
            }
        }
    }

    /**
     * Sets the room ID and loads as much info as possible from the local table.
     *
     * @param int $id The room ID (from table.room_id)
     */
    public function loadRoomId($id)
    {
        if (!empty($id)) {
            $roomData = \Database::select('*', $this->table, ['where' => ['room_id = ?' => $id]], 'last');
            if (!empty($roomData)) {
                $this->rooms_id = $this->room_id = $roomData['room_id'];
                $this->status = $roomData['status'];
                $this->name = $roomData['meeting_name'];
                $this->comment = $roomData['welcome_msg'];
                $this->allowRecording = $roomData['record'];
                $this->chamiloCourseId = $roomData['c_id'];
                $this->chamiloSessionId = $roomData['session_id'];
            }
        }
    }

    /**
     * Gets a string from a boolean attribute.
     *
     * @param string $attribute  Name of the attribute
     * @param mixed  $voidReturn What to return if the value is not defined
     *
     * @return string The boolean value expressed as string ('true' or 'false')
     */
    public function getString($attribute, $voidReturn = false)
    {
        if (empty($attribute)) {
            return false;
        }
        if (!isset($this->$attribute)) {
            return $voidReturn;
        }

        return $this->$attribute ? 'true' : 'false';
    }
}
