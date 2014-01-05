<?php
/*
 * Licensed to the Apache Software Foundation (ASF) under one
* or more contributor license agreements.  See the NOTICE file
* distributed with this work for additional information
* regarding copyright ownership.  The ASF licenses this file
* to you under the Apache License, Version 2.0 (the
* "License") +  you may not use this file except in compliance
* with the License.  You may obtain a copy of the License at
*
*   http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing,
* software distributed under the License is distributed on an
* "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
* KIND, either express or implied.  See the License for the
* specific language governing permissions and limitations
* under the License.
*/
/**
 * @package chamilo.plugin.openmeetings
 */
/**
 * Init
 */
require_once ('openmeetings_rest_service.php');
/**
 * Class OpenMeetingsGateway
 */
class OpenMeetingsGateway
{
    public $sessionId = "";
    public $config;
    private $rest;
    private $_user;
    private $_pass;
    private $_url;

    function __construct($host, $user, $pass)
    {
        $this->_user = urlencode($user);
        $this->_pass = urlencode($pass);
        $this->_url = $host;
        if (substr($this->_url, -1, 1) == '/') {
            $this->_url = substr($this->_url, 0, -1);
        }
        $this->rest = new OpenMeetingsRestService();
        $err = $this->rest->getError();
        if ($err) {
            error_log('Constructor error: ' . $err);
            error_log('Debug: ' . $this->rest->getDebug());;
            exit();
        }

    }
    
    function getRestUrl($name)
    {
        return $this->getUrl() . "/services/" . $name . "/";
    }
    
    function getUrl()
    {
        return $this->_url;
    }
    
    function var_to_str($in)
    {
        if (is_bool($in)) {
            return $in ? "true" : "false";
        } else {
            return $in;
        }
    }
    
    /**
     * TODO: Get Error Service and show detailed Error Message
     */
    function loginUser()
    {
        $returnValue = 0;
        $response = $this->rest->call($this->getRestUrl("UserService") . "getSession", "session_id");
        
        if ($this->rest->getError()) {
            error_log('Fault (Expect - The request contains an invalid SOAP body) '.print_r($response,1));

        } else {
            $err = $this->rest->getError();
            if ($err) {
                error_log('Error: ' . $err);
            } else {
                //error_log('getSession returned '.$response. ' - Storing as sessionId');
                $this->sessionId = $response;
                
                $url = $this->getRestUrl("UserService")
                        . "loginUser?"
                        . "SID=" . $this->sessionId
                        . "&username=" . $this->_user
                        . "&userpass=" . $this->_pass;
                $result = $this->rest->call($url);
                //error_log(__FILE__.'+'.__LINE__.': '.$url);
                if ($this->rest->getError()) {
                    error_log('Fault (Expect - The request contains an invalid SOAP body) '.print_r($result,1));
                } else {
                    $err = $this->rest->getError();
                    if ($err) {
                        error_log('Error '. $err);
                    } else {
                        $returnValue = $result;
                    }
                }
            }
        }
        
        if ($returnValue > 0) {
            return true;
        } else {
            return false;
        }
    }
    function updateRoomWithModeration($room)
    {
        $err = $this->rest->getError();
        if ($err) {
            error_log('Constructor error: ' . $err);
            error_log('Debug: ' . $this->rest->getDebug());
            exit();
        }
        
        $isModeratedRoom = false;
        if ($room->isModeratedRoom == 1) {
            $isModeratedRoom = true;
        }
        
        $result = $this->rest->call($this->getRestUrl("RoomService")
                . "updateRoomWithModeration?SID=" . $this->sessionId
                . "&room_id=" . $room->room_id
                . "&name=" . urlencode($room->name)
                . "&roomtypes_id=" . $room->roomtypes_id
                . "&comment=" . $room->comment
                . "&numberOfPartizipants=" . $room->numberOfPartizipants
                . "&ispublic=false"
                . "&appointment=false"
                . "&isDemoRoom=false"
                . "&demoTime=0"
                . "&isModeratedRoom=" . $this->var_to_str($isModeratedRoom));
        
        if ($result->fault) {
            error_log('Fault (Expect - The request contains an invalid SOAP body) '.print_r($result,1));
        } else {
            $err = $this->rest->getError();
            if ($err) {
                error_log('Error: ' . $err);
            } else {
                // echo '<h2>Result</h2><pre>'; print_r($result["return"]); echo '</pre>';
                //error_log('Room updated successfully '.print_r($result,1));
                return $result;
            }
        }
        return - 1;
    }
    
    /*
     * public String setUserObjectAndGenerateRecordingHashByURL(String SID, String username, String firstname, String lastname, Long externalUserId, String externalUserType, Long recording_id)
     */
    function setUserObjectAndGenerateRecordingHashByURL($username, $firstname, $lastname, $userId, $systemType, $recording_id)
    {
        $result = $this->rest->call($this->getRestUrl("UserService")
                . 'setUserObjectAndGenerateRecordingHashByURL?'
                . 'SID=' . $this->sessionId
                . '&username=' . urlencode($username)
                . '&firstname=' . urlencode($firstname)
                . '&lastname=' . urlencode($lastname)
                . '&externalUserId=' . $userId
                . '&externalUserType=' . urlencode($systemType)
                . '&recording_id=' . $recording_id, 'return');
        
        if ($result->fault) {
            error_log('Fault (Expect - The request contains an invalid SOAP body) '.print_r($result,1));
        } else {
            $err = $this->rest->getError();
            if ($err) {
                error_log('Error: '.$err);
            } else {
                return $result;
            }
        }
        return - 1;
    }
    function setUserObjectAndGenerateRoomHashByURLAndRecFlag($username, $firstname, $lastname, $profilePictureUrl, $email, $userId, $systemType, $room_id, $becomeModerator, $allowRecording)
    {
        $err = $this->rest->getError();
        if ($err) {
            error_log('Constructor error: ' . $err);
            error_log('Debug: ' . $this->rest->getDebug());;
            exit();
        }
        
        $result = $this->rest->call($this->getRestUrl("UserService")
                . "setUserObjectAndGenerateRoomHashByURLAndRecFlag?"
                . "SID=" . $this->sessionId
                . "&username=" . urlencode($username)
                . "&firstname=" . urlencode($firstname)
                . "&lastname=" . urlencode($lastname)
                . "&profilePictureUrl=" . urlencode($profilePictureUrl)
                . "&email=" . urlencode($email)
                . "&externalUserId=" . urlencode($userId)
                . "&externalUserType=" . urlencode($systemType)
                . "&room_id=" . urlencode($room_id)
                . "&becomeModeratorAsInt=" . $becomeModerator
                . "&showAudioVideoTestAsInt=1"
                . "&allowRecording=" . $this->var_to_str($allowRecording));
        
        if ($result->fault) {
            error_log('Fault (Expect - The request contains an invalid SOAP body) '.print_r($result,1));
        } else {
            $err = $this->rest->getError();
            if ($err) {
                error_log('Error: '.$err);
            } else {
                // echo '<h2>Result</h2><pre>'; print_r($result["return"]); echo '</pre>';
                return $result;
            }
        }
        return - 1;
    }
    function deleteRoom($openmeetings)
    {
        $err = $this->rest->getError();
        if ($err) {
            error_log('Constructor error: ' . $err);
            error_log('Debug: ' . $this->rest->getDebug());;
            exit();
        }
        
        $result = $this->rest->call($this->getRestUrl("RoomService") . "deleteRoom?SID=" . $this->sessionId
                . "&rooms_id=" . $openmeetings->room_id);
        
        if ($result->fault) {
            error_log('Fault (Expect - The request contains an invalid SOAP body) '.print_r($result,1));
        } else {
            $err = $this->rest->getError();
            if ($err) {
                error_log('Error: '.$err);
            } else {
                // echo '<h2>Result</h2><pre>'; print_r($result["return"]); echo '</pre>';
                // return $result["return"];
                return $result;
            }
        }
        return - 1;
    }
    
    /**
     * Generate a new room hash for entering a conference room
     */
    function setUserObjectAndGenerateRoomHash($username, $firstname, $lastname, $profilePictureUrl, $email, $externalUserId, $externalUserType, $room_id, $becomeModeratorAsInt, $showAudioVideoTestAsInt)
    {
        $result = $this->rest->call($this->getRestUrl("UserService")
                . "setUserObjectAndGenerateRoomHash?"
                . "SID=" . $this->sessionId
                . "&username=" . urlencode($username)
                . "&firstname=" . urlencode($firstname)
                . "&lastname=" . urlencode($lastname)
                . "&profilePictureUrl=" . urlencode($profilePictureUrl)
                . "&email=" . urlencode($email)
                . "&externalUserId=" . urlencode($externalUserId)
                . "&externalUserType=" . urlencode($externalUserType)
                . "&room_id=" . $room_id
                . "&becomeModeratorAsInt=" . $becomeModeratorAsInt
                . "&showAudioVideoTestAsInt=" . $showAudioVideoTestAsInt);
        
        if ($result->getError()) {
            error_log('Fault (Expect - The request contains an invalid SOAP body) '.print_r($result,1));
        } else {
            $err = $this->rest->getError();
            if ($err) {
                error_log('Error: '.$err);
            } else {
                // echo '<h2>Result</h2><pre>'; print_r($result["return"]); echo '</pre>';
                return $result;
            }
        }
        return - 1;
    }
    
    /**
     * Create a new conference room
     */
    function createRoomWithModAndType($room)
    {
        $url = $this->getRestUrl("RoomService")
                . 'addRoomWithModerationAndExternalType?'
                . 'SID=' . $room->SID
                . '&name=' . $room->roomname
                . '&roomtypes_id=' . $room->roomtypes_id
                . '&comment='. $room->comment
                . '&numberOfPartizipants=' . $room->numberOfPartizipants
                . '&ispublic=' . $room->ispublic
                . '&appointment=' . $room->appointment
                . '&isDemoRoom=' . $room->isDemoRoom
                . '&demoTime=' . $room->demoTime
                . '&isModeratedRoom=' . $room->isModeratedRoom
                . '&externalRoomType=' . $room->externalRoomType;
        error_log($url);
        $result = $this->rest->call($url);
        
        if ($this->rest->fault) {
            error_log('Fault (Expect - The request contains an invalid SOAP body) '.print_r($result,1));
        } else {
            $err = $this->rest->getError();
            if ($err) {
                error_log('Error: '.$err);
            } else {
                error_log('Creation of a new room succeeded: ID '.print_r($result,1));
                return $result;
            }
        }
        return - 1;
    }

    /**
     * Gets the list of open rooms of type "Chamilo"
     * @param   string  $type The type of external system connecting to OpenMeetings
     */
    public function getRoomsWithCurrentUsersByType($type = 'chamilolms')
    {
        //$this->loginUser();
        if (empty($this->sessionId)) {
            return false;
        }

        $url = $this->getRestUrl("RoomService") . "getRoomsWithCurrentUsersByListAndType?SID=" . $this->sessionId
            . "&start=1&max=1000&orderby=name&asc=true&externalRoomType=chamilolms";
        //$url = $this->getRestUrl("RoomService")
        //    . "getRoomTypes?"
        //    . "SID=" . $this->sessionId;
        //$url = $this->getRestUrl('JabberService') . 'getAvailableRooms?SID=' . $this->sessionId;
        error_log(__FILE__.'+'.__LINE__.' Calling WS: '.$url);
        $result = $this->rest->call($url, "return");
        $rooms = array();
        foreach ($result as $room) {
            error_log(__FILE__.'+'.__LINE__.': one room found on remote: '.print_r($room,1));
            if ($room['externalRoomType'] == $type && count($room['currentusers']) > 0 ) {
                $rooms[] = $room;
            }
        }
        return $result;
    }

    /**
     * Gets details of a remote room by room ID
     * @param   int $roomId The ID of the room, as of plugin_openmeetings.room_id
     * @return  mixed Room object
     */
    public function getRoomById($roomId = 0)
    {
        //$this->loginUser();
        if (empty($this->sessionId) or empty($roomId)) {
            return false;
        }
        $roomId = intval($roomId);

        $url = $this->getRestUrl("RoomService")
            . "getRoomById?"
            . "SID=" . $this->sessionId
            . "&rooms_id=".$roomId;
        //error_log(__FILE__.'+'.__LINE__.' Calling WS: '.$url);
        $result = $this->rest->call($url, "return");
        return $result;
    }

    /**
     * Get list of available recordings made by this instance
     */
    function getRecordingsByExternalRooms()
    {
        $url = $this->getRestUrl("RoomService")
            . "getFlvRecordingByExternalRoomType?"
            . "SID=" . $this->sessionId
            . "&externalRoomType=" . urlencode($this->config["moduleKey"]);
        
        $result = $this->rest->call($url, "return");
        
        return $result;
    }

    /**
     * Get list of available recordings made by user
     */
    function getRecordingsByExternalUser($id)
    {
        $url = $this->getRestUrl("RoomService")
            . "getFlvRecordingByExternalUserId?"
            . "SID=" . $this->sessionId
            . "&externalUserId=" . $id;
        
        $result = $this->rest->call($url, "return");
        
        return $result;
    }
}
