<?php
/**
 *
 * (c) Copyright Ascensio System SIA 2021
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

require_once __DIR__.'/../../main/inc/global.inc.php';

use ChamiloSession as Session;

/**
 * Status of the document
 */
const TrackerStatus_Editing = 1;
const TrackerStatus_MustSave = 2;
const TrackerStatus_Corrupted = 3;
const TrackerStatus_Closed = 4;
const TrackerStatus_ForceSave = 6;
const TrackerStatus_CorruptedForceSave = 7;

$plugin = OnlyofficePlugin::create();

if (isset($_GET["hash"]) && !empty($_GET["hash"])) {
    $callbackResponseArray = [];
    @header( 'Content-Type: application/json; charset==utf-8');
    @header( 'X-Robots-Tag: noindex' );
    @header( 'X-Content-Type-Options: nosniff' );

    list ($hashData, $error) = Crypt::ReadHash($_GET["hash"]);
    if ($hashData === null) {
        $callbackResponseArray["status"] = "error";
        $callbackResponseArray["error"] = $error;
        die(json_encode($callbackResponseArray));
    }

    $type = $hashData->type;
    $courseId = $hashData->courseId;
    $userId = $hashData->userId;
    $docId = $hashData->docId;
    $groupId = $hashData->groupId;
    $sessionId = $hashData->sessionId;

    $courseInfo = api_get_course_info_by_id($courseId);
    $courseCode = $courseInfo["code"];

    if (!empty($userId)) {
        $userInfo = api_get_user_info($userId);
    } else {
        $result["error"] = "User not found";
        die (json_encode($result));
    }

    if (api_is_anonymous()) {
        $loggedUser = [
            "user_id" => $userInfo["id"],
            "status" => $userInfo["status"],
            "uidReset" => true,
        ];

        Session::write("_user", $loggedUser);
        Login::init_user($loggedUser["user_id"], true);
    } else {
        $userId = api_get_user_id();
    }

    switch($type) {
        case "track":
            $callbackResponseArray = track();
            die (json_encode($callbackResponseArray));
        case "download":
            $callbackResponseArray = download();
            die (json_encode($callbackResponseArray));
        default:
            $callbackResponseArray["status"] = "error";
            $callbackResponseArray["error"] = "404 Method not found";
            die(json_encode($callbackResponseArray));
    }
}

/**
 * Handle request from the document server with the document status information
 */
function track(): array
{
    $result = [];

    global $plugin;
    global $courseCode;
    global $userId;
    global $docId;
    global $groupId;
    global $sessionId;
    global $courseInfo;

    if (($body_stream = file_get_contents("php://input")) === false) {
        $result["error"] = "Bad Request";
        return $result;
    }

    $data = json_decode($body_stream, true);

    if ($data === null) {
        $result["error"] = "Bad Response";
        return $result;
    }

    if (!empty($plugin->get("jwt_secret"))) {

        if (!empty($data["token"])) {
            try {
                $payload = \Firebase\JWT\JWT::decode($data["token"], $plugin->get("jwt_secret"), array("HS256"));
            } catch (\UnexpectedValueException $e) {
                $result["status"] = "error";
                $result["error"] = "403 Access denied";
                return $result;
            }
        } else {
            $token = substr(getallheaders()[AppConfig::JwtHeader()], strlen("Bearer "));
            try {
                $decodeToken = \Firebase\JWT\JWT::decode($token, $plugin->get("jwt_secret"), array("HS256"));
                $payload = $decodeToken->payload;
            } catch (\UnexpectedValueException $e) {
                $result["status"] = "error";
                $result["error"] = "403 Access denied";
                return $result;
            }
        }

        $data["url"] = isset($payload->url) ? $payload->url : null;
        $data["status"] = $payload->status;
    }

    $status = $data["status"];

    $track_result = 1;
    switch ($status) {
        case TrackerStatus_MustSave:
        case TrackerStatus_Corrupted:

            $downloadUri = $data["url"];

            if (!empty($docId) && !empty($courseCode)) {
                $docInfo = DocumentManager::get_document_data_by_id($docId, $courseCode, false, $sessionId);

                if ($docInfo === false) {
                    $result["error"] = "File not found";
                    return $result;
                }

                $filePath = $docInfo["absolute_path"];
            } else {
                $result["error"] = "Bad Request";
                return $result;
            }

            list ($isAllowToEdit, $isMyDir, $isGroupAccess, $isReadonly) = getPermissions($docInfo, $userId, $courseCode, $groupId, $sessionId);

            if ($isReadonly) {
                break;
            }

            if (($new_data = file_get_contents($downloadUri)) === false) {
                break;
            }

            if ($isAllowToEdit || $isMyDir || $isGroupAccess) {
                $groupInfo = GroupManager::get_group_properties($groupId);

                if ($fp = @fopen($filePath, "w")) {
                    fputs($fp, $new_data);
                    fclose($fp);
                    api_item_property_update($courseInfo,
                                                TOOL_DOCUMENT,
                                                $docId,
                                                "DocumentUpdated",
                                                $userId,
                                                $groupInfo,
                                                null,
                                                null,
                                                null,
                                                $sessionId);
                    update_existing_document($courseInfo,
                                                $docId,
                                                filesize($filePath),
                                                false);
                    $track_result = 0;
                    break;
                }
            }

        case TrackerStatus_Editing:
        case TrackerStatus_Closed:

            $track_result = 0;
            break;
    }

    $result["error"] = $track_result;
    return $result;
}

/**
 * Downloading file by the document service
 */
function download()
{
    global $plugin;
    global $courseCode;
    global $userId;
    global $docId;
    global $groupId;
    global $sessionId;
    global $courseInfo;

    if (!empty($plugin->get("jwt_secret"))) {
        $token = substr(getallheaders()[AppConfig::JwtHeader()], strlen("Bearer "));
        try {
            $payload = \Firebase\JWT\JWT::decode($token, $plugin->get("jwt_secret"), array("HS256"));

        } catch (\UnexpectedValueException $e) {
            $result["status"] = "error";
            $result["error"] = "403 Access denied";
            return $result;
        }
    }

    if (!empty($docId) && !empty($courseCode)) {
        $docInfo = DocumentManager::get_document_data_by_id($docId, $courseCode, false, $sessionId);

        if ($docInfo === false) {
            $result["error"] = "File not found";
            return $result;
        }

        $filePath = $docInfo["absolute_path"];
    } else {
        $result["error"] = "File not found";
        return $result;
    }

    @header("Content-Type: application/octet-stream");
    @header("Content-Disposition: attachment; filename=" . $docInfo["title"]);

    readfile($filePath);
}

/**
 * Method checks access rights to document and returns permissions
 */
function getPermissions(array $docInfo, int $userId, string $courseCode, int $groupId = null, int $sessionId = null): array
{
    $isAllowToEdit = api_is_allowed_to_edit(true, true);
    $isMyDir = DocumentManager::is_my_shared_folder($userId, $docInfo["absolute_parent_path"], $sessionId);

    $isGroupAccess = false;
    if (!empty($groupId)) {
        $courseInfo = api_get_course_info($courseCode);
        Session::write("_real_cid", $courseInfo["real_id"]);
        $groupProperties = GroupManager::get_group_properties($groupId);
        $docInfoGroup = api_get_item_property_info($courseInfo["real_id"], "document", $docInfo["id"], $sessionId);
        $isGroupAccess = GroupManager::allowUploadEditDocument($userId, $courseCode, $groupProperties, $docInfoGroup);
    }

    $isReadonly = $docInfo["readonly"];

    return [$isAllowToEdit, $isMyDir, $isGroupAccess, $isReadonly];
}
