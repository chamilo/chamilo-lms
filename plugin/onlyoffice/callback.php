<?php
/**
 * (c) Copyright Ascensio System SIA 2024.
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
 */

require_once __DIR__.'/../../main/inc/global.inc.php';

use ChamiloSession as Session;
use Onlyoffice\DocsIntegrationSdk\Models\Callback as OnlyofficeCallback;
use Onlyoffice\DocsIntegrationSdk\Models\CallbackDocStatus;

$plugin = OnlyofficePlugin::create();

if (isset($_GET['hash']) && !empty($_GET['hash'])) {
    $callbackResponseArray = [];
    @header('Content-Type: application/json; charset==utf-8');
    @header('X-Robots-Tag: noindex');
    @header('X-Content-Type-Options: nosniff');

    $appSettings = new OnlyofficeAppsettings($plugin);
    $jwtManager = new OnlyofficeJwtManager($appSettings);
    list($hashData, $error) = $jwtManager->readHash($_GET['hash'], api_get_security_key());
    if (null === $hashData) {
        $callbackResponseArray['status'] = 'error';
        $callbackResponseArray['error'] = $error;
        exit(json_encode($callbackResponseArray));
    }

    $type = $hashData->type;
    $courseId = $hashData->courseId;
    $userId = $hashData->userId;
    $docId = $hashData->docId;
    $groupId = $hashData->groupId;
    $sessionId = $hashData->sessionId;

    $courseInfo = api_get_course_info_by_id($courseId);
    $courseCode = $courseInfo['code'];

    if (!empty($userId)) {
        $userInfo = api_get_user_info($userId);
    } else {
        $result['error'] = 'User not found';
        exit(json_encode($result));
    }

    if (api_is_anonymous()) {
        $loggedUser = [
            'user_id' => $userInfo['id'],
            'status' => $userInfo['status'],
            'uidReset' => true,
        ];

        Session::write('_user', $loggedUser);
        Login::init_user($loggedUser['user_id'], true);
    } else {
        $userId = api_get_user_id();
    }

    switch ($type) {
        case 'track':
            $callbackResponseArray = track();
            exit(json_encode($callbackResponseArray));
        case 'download':
            $callbackResponseArray = download();
            exit(json_encode($callbackResponseArray));
        case 'empty':
            $callbackResponseArray = emptyFile();
            exit(json_encode($callbackResponseArray));
        default:
            $callbackResponseArray['status'] = 'error';
            $callbackResponseArray['error'] = '404 Method not found';
            exit(json_encode($callbackResponseArray));
    }
}

/**
 * Handle request from the document server with the document status information.
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
    global $appSettings;
    global $jwtManager;

    if (($body_stream = file_get_contents('php://input')) === false) {
        $result['error'] = 'Bad Request';

        return $result;
    }

    $data = json_decode($body_stream, true);

    if (null === $data) {
        $result['error'] = 'Bad Response';

        return $result;
    }

    if ($jwtManager->isJwtEnabled()) {
        if (!empty($data['token'])) {
            try {
                $payload = $jwtManager->decode($data['token'], $appSettings->getJwtKey());
            } catch (UnexpectedValueException $e) {
                $result['status'] = 'error';
                $result['error'] = '403 Access denied';

                return $result;
            }
        } else {
            $token = substr(getallheaders()[$appSettings->getJwtHeader()], strlen('Bearer '));
            try {
                $decodeToken = $jwtManager->decode($token, $appSettings->getJwtKey());
                $payload = $decodeToken->payload;
            } catch (UnexpectedValueException $e) {
                $result['status'] = 'error';
                $result['error'] = '403 Access denied';

                return $result;
            }
        }

        $data['url'] = isset($payload->url) ? $payload->url : null;
        $data['status'] = $payload->status;
    }

    $docStatus = new CallbackDocStatus($data['status']);
    $callback = new OnlyofficeCallback();
    $callback->setStatus($docStatus);
    $callback->setKey($docId);
    $callback->setUrl($data['url']);
    $callbackService = new OnlyofficeCallbackService(
        $appSettings,
        $jwtManager,
        [
            'courseCode' => $courseCode,
            'userId' => $userId,
            'docId' => $docId,
            'groupId' => $groupId,
            'sessionId' => $sessionId,
            'courseInfo' => $courseInfo,
        ]);
    $result = $callbackService->processCallback($callback, $docId);

    return $result;
}

/**
 * Downloading file by the document service.
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
    global $appSettings;
    global $jwtManager;

    if ($jwtManager->isJwtEnabled()) {
        $token = substr(getallheaders()[$appSettings->getJwtHeader()], strlen('Bearer '));
        try {
            $payload = $jwtManager->decode($token, $appSettings->getJwtKey());
        } catch (UnexpectedValueException $e) {
            $result['status'] = 'error';
            $result['error'] = '403 Access denied';

            return $result;
        }
    }

    if (!empty($docId) && !empty($courseCode)) {
        $docInfo = DocumentManager::get_document_data_by_id($docId, $courseCode, false, $sessionId);

        if (false === $docInfo) {
            $result['error'] = 'File not found';

            return $result;
        }

        $filePath = $docInfo['absolute_path'];
    } else {
        $result['error'] = 'File not found';

        return $result;
    }

    @header('Content-Type: application/octet-stream');
    @header('Content-Disposition: attachment; filename='.$docInfo['title']);

    readfile($filePath);
    exit;
}

/**
 * Downloading empty file by the document service.
 */
function emptyFile()
{
    global $plugin;
    global $type;
    global $courseCode;
    global $userId;
    global $docId;
    global $groupId;
    global $sessionId;
    global $courseInfo;
    global $appSettings;
    global $jwtManager;


    if ($type !== 'empty') {
        $result['status'] = 'error';
        $result['error'] = 'Download empty with other action';
        return $result;
    }

    if ($jwtManager->isJwtEnabled()) {
        $token = substr(getallheaders()[$appSettings->getJwtHeader()], strlen('Bearer '));
        try {
            $payload = $jwtManager->decode($token, $appSettings->getJwtKey());
        } catch (UnexpectedValueException $e) {
            $result['status'] = 'error';
            $result['error'] = '403 Access denied';
            return $result;
        }
    }

    $template = TemplateManager::getEmptyTemplate('docx');

    if (!$template) {
        $result['status'] = 'error';
        $result['error'] = 'File not found';
        return $result;
    }

    @header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    @header('Content-Disposition: attachment; filename='.'docx.docx');
    readfile($template);
    exit;
}