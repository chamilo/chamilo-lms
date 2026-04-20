<?php
/**
 * (c) Copyright Ascensio System SIA 2025.
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
require_once __DIR__.'/../../../main/inc/global.inc.php';

use ChamiloSession as Session;

api_block_anonymous_users();

$plugin = OnlyofficePlugin::create();
$appSettings = new OnlyofficeAppsettings($plugin);

sendOnlyofficeSaveAsHeaders();

if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? 'GET')) {
    sendOnlyofficeSaveAsResponse(['error' => 'Method not allowed'], 405);
}

$csrfToken = (string) ($_SERVER['HTTP_X_ONLYOFFICE_CSRF_TOKEN'] ?? '');
$sessionToken = (string) Session::read('onlyoffice_saveas_csrf_token');

if ('' === $csrfToken || '' === $sessionToken || !hash_equals($sessionToken, $csrfToken)) {
    sendOnlyofficeSaveAsResponse(['error' => 'Invalid CSRF token'], 403);
}

$body = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($body)) {
    sendOnlyofficeSaveAsResponse(['error' => 'Invalid request body'], 400);
}

$title = trim((string) ($body['title'] ?? ''));
$templateUrl = trim((string) ($body['url'] ?? ''));
$folderId = isset($body['folderId']) ? (int) $body['folderId'] : 0;

if ('' === $title || mb_strlen($title) > 255) {
    sendOnlyofficeSaveAsResponse(['error' => 'Invalid title'], 400);
}

if ('' === $templateUrl || !isAllowedOnlyofficeSaveAsUrl($templateUrl, $appSettings)) {
    sendOnlyofficeSaveAsResponse(['error' => 'Invalid template URL'], 400);
}

$userId = (int) api_get_user_id();
$sessionId = (int) api_get_session_id();
$courseId = (int) api_get_course_int_id();
$groupId = (int) api_get_group_id();

$courseInfo = api_get_course_info();
if (empty($courseInfo)) {
    sendOnlyofficeSaveAsResponse(['error' => 'Course context not found'], 403);
}

$courseCode = $courseInfo['code'];

$isMyDir = false;
if ($folderId > 0) {
    $folderInfo = DocumentManager::get_document_data_by_id(
        $folderId,
        $courseCode,
        true,
        $sessionId
    );

    if (empty($folderInfo)) {
        sendOnlyofficeSaveAsResponse(['error' => 'Folder not found'], 404);
    }

    $isMyDir = DocumentManager::is_my_shared_folder(
        $userId,
        $folderInfo['absolute_path'],
        $sessionId
    );
}

$groupRights = Session::read('group_member_with_upload_rights');
$isAllowToEdit = api_is_allowed_to_edit(true, true);

if (!($isAllowToEdit || $isMyDir || $groupRights)) {
    sendOnlyofficeSaveAsResponse(['error' => 'Not permitted'], 403);
}

$fileExt = strtolower((string) pathinfo($title, PATHINFO_EXTENSION));
$baseName = trim((string) pathinfo($title, PATHINFO_FILENAME));

if ('' === $baseName || '' === $fileExt) {
    sendOnlyofficeSaveAsResponse(['error' => 'Invalid file name'], 400);
}

if (!in_array($fileExt, ['docx', 'xlsx', 'pptx', 'pdf'], true)) {
    sendOnlyofficeSaveAsResponse(['error' => 'Unsupported file extension'], 400);
}

$result = OnlyofficeDocumentManager::createFile(
    $baseName,
    $fileExt,
    $folderId,
    $userId,
    $sessionId,
    $courseId,
    $groupId,
    $templateUrl
);

if (isset($result['error'])) {
    if ('fileIsExist' === $result['error']) {
        $result['error'] = 'File already exists';
    }

    if ('impossibleCreateFile' === $result['error']) {
        $result['error'] = 'Impossible to create file';
    }

    sendOnlyofficeSaveAsResponse($result, 400);
}

sendOnlyofficeSaveAsResponse(['success' => 'File is created']);

/**
 * Send standard JSON headers.
 */
function sendOnlyofficeSaveAsHeaders(): void
{
    @header('Content-Type: application/json');
    @header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    @header('Pragma: no-cache');
    @header('Expires: 0');
    @header('X-Robots-Tag: noindex');
    @header('X-Content-Type-Options: nosniff');
}

/**
 * Send a JSON response and stop execution.
 */
function sendOnlyofficeSaveAsResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);

    exit;
}

/**
 * Validate the save-as template URL.
 */
function isAllowedOnlyofficeSaveAsUrl(string $url, OnlyofficeAppsettings $appSettings): bool
{
    $parts = parse_url($url);
    if (!is_array($parts)) {
        return false;
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host = strtolower((string) ($parts['host'] ?? ''));

    if (!in_array($scheme, ['http', 'https'], true) || '' === $host) {
        return false;
    }

    $allowedHosts = [];

    foreach ([
        $appSettings->getDocumentServerUrl(),
        method_exists($appSettings, 'getDocumentServerInternalUrl') ? $appSettings->getDocumentServerInternalUrl() : '',
    ] as $allowedUrl) {
        $allowedParts = is_string($allowedUrl) ? parse_url($allowedUrl) : false;
        $allowedHost = is_array($allowedParts) ? strtolower((string) ($allowedParts['host'] ?? '')) : '';

        if ('' !== $allowedHost) {
            $allowedHosts[] = $allowedHost;
        }
    }

    $allowedHosts = array_values(array_unique($allowedHosts));

    return !empty($allowedHosts) && in_array($host, $allowedHosts, true);
}
