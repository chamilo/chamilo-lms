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
    public $session_id = "";
    public $config;
    private $rest;
    
    function __construct()
    {
        $this->_user = CONFIG_OPENMEETINGS_USER;
        $this->_pass = CONFIG_OPENMEETINGS_PASS;
        $this->_url = CONFIG_OPENMEETINGS_SERVER_URL;
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
        // FIXME protocol should be added
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

        $response = $this->rest->call($this->getRestUrl("UserService") . "getSession", "session_id");
        
        if ($this->rest->getError()) {
            error_log('Fault (Expect - The request contains an invalid SOAP body) '.print_r($response,1));
        } else {
            $err = $this->rest->getError();
            if ($err) {
                error_log('Error: ' . $err);
            } else {
                $this->session_id = $response;
                
                $result = $this->rest->call($this->getRestUrl("UserService") . "loginUser?SID=" . $this->session_id
                        . "&username=" . urlencode(CONFIG_OPENMEETINGS_USER)
                        . "&userpass=" . urlencode(CONFIG_OPENMEETINGS_PASS));
                
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
    function updateRoomWithModeration($openmeetings)
    {
        $err = $this->rest->getError();
        if ($err) {
            error_log('Constructor error: ' . $err);
            error_log('Debug: ' . $this->rest->getDebug());
            exit();
        }
        
        $isModeratedRoom = false;
        if ($openmeetings->is_moderated_room == 1) {
            $isModeratedRoom = true;
        }
        
        $result = $this->rest->call($this->getRestUrl("RoomService") . "updateRoomWithModeration?SID=" . $this->session_id
                . "&room_id=" . $openmeetings->room_id . "&name=" . urlencode($openmeetings->roomname) . "&roomtypes_id=" 
                . urlencode($openmeetings->type) . "&comment=" . urlencode("Created by SOAP-Gateway") 
                . "&numberOfPartizipants=" . $openmeetings->max_user . "&ispublic=false" . "&appointment=false" . "&isDemoRoom=false" 
                . "&demoTime=0" . "&isModeratedRoom=" . $this->var_to_str($isModeratedRoom));
        
        if ($result->fault) {
            error_log('Fault (Expect - The request contains an invalid SOAP body) '.print_r($result,1));
        } else {
            $err = $this->rest->getError();
            if ($err) {
                error_log('Error: ' . $err);
            } else {
                // echo '<h2>Result</h2><pre>'; print_r($result["return"]); echo '</pre>';
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
        $result = $this->rest->call($this->getRestUrl("UserService") . 'setUserObjectAndGenerateRecordingHashByURL?SID=' . $this->session_id
                . '&username=' . urlencode($username) . '&firstname=' . urlencode($firstname) . '&lastname=' . urlencode($lastname) 
                . '&externalUserId=' . $userId . '&externalUserType=' . urlencode($systemType) . '&recording_id=' . $recording_id, 'return');
        
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
        
        $result = $this->rest->call($this->getRestUrl("UserService") . "setUserObjectAndGenerateRoomHashByURLAndRecFlag?SID=" . $this->session_id
                . "&username=" . urlencode($username) . "&firstname=" . urlencode($firstname) . "&lastname=" . urlencode($lastname) 
                . "&profilePictureUrl=" . urlencode($profilePictureUrl) . "&email=" . urlencode($email) . "&externalUserId=" . urlencode($userId) 
                . "&externalUserType=" . urlencode($systemType) . "&room_id=" . urlencode($room_id) . "&becomeModeratorAsInt=" . $becomeModerator 
                . "&showAudioVideoTestAsInt=1" . "&allowRecording=" . $this->var_to_str($allowRecording));
        
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
        
        $result = $this->rest->call($this->getRestUrl("RoomService") . "deleteRoom?SID=" . $this->session_id
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
        $result = $this->rest->call($this->getRestUrl("UserService") . "setUserObjectAndGenerateRoomHash?SID=" . $this->session_id
                . "&username=" . urlencode($username) . "&firstname=" . urlencode($firstname) . "&lastname=" . urlencode($lastname) 
                . "&profilePictureUrl=" . urlencode($profilePictureUrl) . "&email=" . urlencode($email) . "&externalUserId=" 
                . urlencode($externalUserId) . "&externalUserType=" . urlencode($externalUserType) . "&room_id=" . $room_id 
                . "&becomeModeratorAsInt=" . $becomeModeratorAsInt . "&showAudioVideoTestAsInt=" . $showAudioVideoTestAsInt);
        
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
    function createRoomWithModAndType($openmeetings)
    {
        $isModeratedRoom = "false";
        if ($openmeetings->is_moderated_room == 1) {
            $isModeratedRoom = "true";
        }
        
        $url = $this->getRestUrl("RoomService") . 'addRoomWithModerationAndExternalType?SID=' . $this->session_id 
                . '&name=' . urlencode($openmeetings->roomname) . '&roomtypes_id=' . $openmeetings->type . '&comment=' 
                . urlencode('Created by SOAP-Gateway') . '&numberOfPartizipants=' . $openmeetings->max_user 
                . '&ispublic=false' . '&appointment=false' . '&isDemoRoom=false' . '&demoTime=0' . '&isModeratedRoom=' . $isModeratedRoom 
                . '&externalRoomType=' . urlencode($this->config["moduleKey"]);
        
        $result = $this->rest->call($url, "return");
        
        if ($this->rest->fault) {
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

    /**
     * Gets the list of open rooms of type "Chamilo"
     */
    public function getRoomsWithCurrentUsersByType($sid)
    {
        if (empty($sid)) {
            if (empty($this->session_id)) {
                return false;
            }
            $sid = $this->session_id;
        }
        //$url = $this->getRestUrl("RoomService") . "getRoomsWithCurrentUsersByListAndType?SID=" . $sid
        //    . "&start=1&max=100&orderby=name&asc=true&externalRoomType=chamilo";
        $url = $this->getRestUrl("RoomService") . "getRoomTypes?SID=" . $sid;
           // . "&roomtypes_id=1";
error_log($url);
        $result = $this->rest->call($url, "return");
        error_log(print_r($result,1));
        return $result;

    }

    /**
     * Get list of available recordings made by this instance
     */
    function getRecordingsByExternalRooms()
    {
        $url = $this->getRestUrl("RoomService") . "getFlvRecordingByExternalRoomType?SID=" . $this->session_id
            . "&externalRoomType=" . urlencode($this->config["moduleKey"]);
        
        $result = $this->rest->call($url, "return");
        
        return $result;
    }

    /**
     * Get list of available recordings made by user
     */
    function getRecordingsByExternalUser($id)
    {
        $url = $this->getRestUrl("RoomService") . "getFlvRecordingByExternalUserId?SID=" . $this->session_id
            . "&externalUserId=" . $id;
        
        $result = $this->rest->call($url, "return");
        
        return $result;
    }
}
