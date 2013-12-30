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
	
	function __construct($cfg)
    {
		$this->config = $cfg;
	}
	
	function getRestUrl($name)
    {
		return $this->getUrl() . "/services/" . $name . "/";
	}
	
	function getUrl()
    {
		// FIXME protocol should be added
		$port = $this->config["port"] == 80 ? '' : ":" . $this->config["port"];
		return $this->config["protocol"] . "://" . $this->config["host"] . $port . "/" . $this->config["webappname"];
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
	function loginuser()
    {
		$restService = new OpenMeetingsRestService();
		
		$response = $restService->call($this->getRestUrl("UserService") . "getSession", "session_id");
		
		if ($restService->getError()) {
			echo '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2><pre>';
			print_r($response);
			echo '</pre>';
		} else {
			$err = $restService->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
			} else {
				$this->session_id = $response;
				
				$result = $restService->call($this->getRestUrl("UserService") . "loginUser?SID=" . $this->session_id 
						. "&username=" . urlencode($this->config["adminUser"]) 
						. "&userpass=" . urlencode($this->config["adminPass"]));
				
				if ($restService->getError()) {
					echo '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2><pre>';
					print_r($result);
					echo '</pre>';
				} else {
					$err = $restService->getError();
					if ($err) {
						echo '<h2>Error</h2><pre>' . $err . '</pre>';
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
		$restService = new OpenMeetingsRestService();
		// echo $restService."<br/>";
		$err = $restService->getError();
		if ($err) {
			echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
			echo '<h2>Debug</h2><pre>' . htmlspecialchars($restService->getDebug(), ENT_QUOTES) . '</pre>';
			exit();
		}
		
		$isModeratedRoom = false;
		if ($openmeetings->is_moderated_room == 1) {
			$isModeratedRoom = true;
		}
		
		$result = $restService->call($this->getRestUrl("RoomService") . "updateRoomWithModeration?SID=" . $this->session_id 
				. "&room_id=" . $openmeetings->room_id . "&name=" . urlencode($openmeetings->roomname) . "&roomtypes_id=" 
				. urlencode($openmeetings->type) . "&comment=" . urlencode("Created by SOAP-Gateway") 
				. "&numberOfPartizipants=" . $openmeetings->max_user . "&ispublic=false" . "&appointment=false" . "&isDemoRoom=false" 
				. "&demoTime=0" . "&isModeratedRoom=" . $this->var_to_str($isModeratedRoom));
		
		if ($restService->fault) {
			echo '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2><pre>';
			print_r($result);
			echo '</pre>';
		} else {
			$err = $restService->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
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
		$restService = new OpenMeetingsRestService();
		$result = $restService->call($this->getRestUrl("UserService") . 'setUserObjectAndGenerateRecordingHashByURL?SID=' . $this->session_id 
				. '&username=' . urlencode($username) . '&firstname=' . urlencode($firstname) . '&lastname=' . urlencode($lastname) 
				. '&externalUserId=' . $userId . '&externalUserType=' . urlencode($systemType) . '&recording_id=' . $recording_id, 'return');
		
		if ($restService->fault) {
			echo '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2><pre>';
			print_r($result);
			echo '</pre>';
		} else {
			$err = $restService->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
			} else {
				return $result;
			}
		}
		return - 1;
	}
	function setUserObjectAndGenerateRoomHashByURLAndRecFlag($username, $firstname, $lastname, $profilePictureUrl, $email, $userId, $systemType, $room_id, $becomeModerator, $allowRecording)
    {
		$restService = new OpenMeetingsRestService();
		// echo $restService."<br/>";
		$err = $restService->getError();
		if ($err) {
			echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
			echo '<h2>Debug</h2><pre>' . htmlspecialchars($restService->getDebug(), ENT_QUOTES) . '</pre>';
			exit();
		}
		
		$result = $restService->call($this->getRestUrl("UserService") . "setUserObjectAndGenerateRoomHashByURLAndRecFlag?SID=" . $this->session_id 
				. "&username=" . urlencode($username) . "&firstname=" . urlencode($firstname) . "&lastname=" . urlencode($lastname) 
				. "&profilePictureUrl=" . urlencode($profilePictureUrl) . "&email=" . urlencode($email) . "&externalUserId=" . urlencode($userId) 
				. "&externalUserType=" . urlencode($systemType) . "&room_id=" . urlencode($room_id) . "&becomeModeratorAsInt=" . $becomeModerator 
				. "&showAudioVideoTestAsInt=1" . "&allowRecording=" . $this->var_to_str($allowRecording));
		
		if ($restService->fault) {
			echo '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2><pre>';
			print_r($result);
			echo '</pre>';
		} else {
			$err = $restService->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
			} else {
				// echo '<h2>Result</h2><pre>'; print_r($result["return"]); echo '</pre>';
				return $result;
			}
		}
		return - 1;
	}
	function deleteRoom($openmeetings)
    {
		// echo $client_roomService."<br/>";
		$restService = new OpenMeetingsRestService();
		$err = $restService->getError();
		if ($err) {
			echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
			echo '<h2>Debug</h2><pre>' . htmlspecialchars($restService->getDebug(), ENT_QUOTES) . '</pre>';
			exit();
		}
		
		$result = $restService->call($this->getRestUrl("RoomService") . "deleteRoom?SID=" . $this->session_id 
				. "&rooms_id=" . $openmeetings->room_id);
		
		if ($restService->fault) {
			echo '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2><pre>';
			print_r($result);
			echo '</pre>';
		} else {
			$err = $restService->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
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
		$restService = new OpenMeetingsRestService();
		
		$result = $restService->call($this->getRestUrl("UserService") . "setUserObjectAndGenerateRoomHash?SID=" . $this->session_id 
				. "&username=" . urlencode($username) . "&firstname=" . urlencode($firstname) . "&lastname=" . urlencode($lastname) 
				. "&profilePictureUrl=" . urlencode($profilePictureUrl) . "&email=" . urlencode($email) . "&externalUserId=" 
				. urlencode($externalUserId) . "&externalUserType=" . urlencode($externalUserType) . "&room_id=" . $room_id 
				. "&becomeModeratorAsInt=" . $becomeModeratorAsInt . "&showAudioVideoTestAsInt=" . $showAudioVideoTestAsInt);
		
		$err = $restService->getError();
		if ($err) {
			echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
			echo '<h2>Debug</h2><pre>' . htmlspecialchars($restService->getDebug(), ENT_QUOTES) . '</pre>';
			exit();
		}
		
		if ($restService->getError()) {
			echo '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2><pre>';
			print_r($result);
			echo '</pre>';
		} else {
			$err = $restService->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
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
		global $USER;
		
		$restService = new OpenMeetingsRestService();
		
		$isModeratedRoom = "false";
		if ($openmeetings->is_moderated_room == 1) {
			$isModeratedRoom = "true";
		}
		
		$url = $this->getRestUrl("RoomService") . 'addRoomWithModerationAndExternalType?SID=' . $this->session_id 
				. '&name=' . urlencode($openmeetings->roomname) . '&roomtypes_id=' . $openmeetings->type . '&comment=' 
				. urlencode('Created by SOAP-Gateway') . '&numberOfPartizipants=' . $openmeetings->max_user 
				. '&ispublic=false' . '&appointment=false' . '&isDemoRoom=false' . '&demoTime=0' . '&isModeratedRoom=' . $isModeratedRoom 
				. '&externalRoomType=' . urlencode($this->config["moduleKey"]);
		
		$result = $restService->call($url, "return");
		
		if ($restService->fault) {
			echo '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2><pre>';
			print_r($result);
			echo '</pre>';
		} else {
			$err = $restService->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
			} else {
				return $result;
			}
		}
		return - 1;
	}
	
	/**
	 * Get list of available recordings made by this instance
	 */
	function getRecordingsByExternalRooms()
    {
		$restService = new OpenMeetingsRestService();
		
		$url = $this->getRestUrl("RoomService") . "getFlvRecordingByExternalRoomType?SID=" . $this->session_id 
			. "&externalRoomType=" . urlencode($this->config["moduleKey"]);
		
		$result = $restService->call($url, "return");
		
		return $result;
	}

	/**
	 * Get list of available recordings made by user
	 */
	function getRecordingsByExternalUser($id)
    {
		$restService = new OpenMeetingsRestService();
		
		$url = $this->getRestUrl("RoomService") . "getFlvRecordingByExternalUserId?SID=" . $this->session_id 
			. "&externalUserId=" . $id;
		
		$result = $restService->call($url, "return");
		
		return $result;
	}
}
