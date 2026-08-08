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
require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();

$plugin = OnlyofficePlugin::create();
$appSettings = new OnlyofficeAppsettings($plugin);
$documentManager = new OnlyofficeDocumentManager($appSettings, []);

$mapFileFormat = [
    'text' => $plugin->get_lang('document'),
    'spreadsheet' => $plugin->get_lang('spreadsheet'),
    'presentation' => $plugin->get_lang('presentation'),
    'formTemplate' => $plugin->get_lang('formTemplate'),
];

$userId = (int) api_get_user_id();
$sessionId = (int) api_get_session_id();
$courseId = (int) api_get_course_int_id();
$groupId = (int) api_get_group_id();
$folderId = isset($_GET['folderId']) ? (int) $_GET['folderId'] : 0;
$parentResourceNodeId = isset($_GET['parentResourceNodeId']) ? (int) $_GET['parentResourceNodeId'] : 0;
$returnUrl = resolveOnlyofficeCreateReturnUrl((string) ($_GET['returnUrl'] ?? ''));

$courseInfo = api_get_course_info();
if (empty($courseInfo)) {
    api_not_allowed(true);
}

$courseCode = $courseInfo['code'];

if (!api_is_allowed_to_edit(true, true)) {
    api_not_allowed(true);
}

$formActionParams = [
    'folderId' => $folderId,
    'parentResourceNodeId' => $parentResourceNodeId,
    'cid' => $courseId,
    'sid' => $sessionId,
    'gid' => $groupId,
];

if ('' !== $returnUrl) {
    $formActionParams['returnUrl'] = $returnUrl;
}

$form = new FormValidator(
    'doc_create',
    'post',
    api_get_path(WEB_PLUGIN_PATH).'Onlyoffice/create.php?'.http_build_query($formActionParams)
);

$form->addText('fileName', $plugin->get_lang('title'), true);
$form->addSelect('fileFormat', $plugin->get_lang('chooseFileFormat'), $mapFileFormat);
$form->addButtonCreate($plugin->get_lang('create'));

if ($form->validate()) {
    $values = $form->exportValues();

    $fileType = $values['fileFormat'];
    $fileExt = $documentManager->getDocExtByType($fileType);

    $result = OnlyofficeDocumentManager::createFile(
        $values['fileName'],
        $fileExt,
        $folderId,
        $userId,
        $sessionId,
        $courseId,
        $groupId,
        '',
        $parentResourceNodeId
    );

    if (isset($result['error'])) {
        Display::addFlash(
            Display::return_message(
                $plugin->get_lang($result['error']),
                'error'
            )
        );
    } else {
        $redirectUrl = '' !== $returnUrl
            ? $returnUrl
            : OnlyofficeDocumentManager::getUrlToLocation($courseCode, $sessionId, $groupId, $folderId);

        header('Location: '.$redirectUrl);
        exit;
    }
}

$goBackUrl = '' !== $returnUrl
    ? $returnUrl
    : OnlyofficeDocumentManager::getUrlToLocation($courseCode, $sessionId, $groupId, $folderId);
$actionsLeft = Display::url(
    Display::return_icon(
        'back.png',
        get_lang('Back').' '.get_lang('To').' '.get_lang('DocumentsOverview'),
        [],
        ICON_SIZE_MEDIUM
    ),
    $goBackUrl
);

Display::display_header($plugin->get_lang('createNewDocument'));
echo Display::toolbarAction('actions-documents', [$actionsLeft]);
echo $form->returnForm();
Display::display_footer();

/**
 * Resolve an internal return URL for the modern Documents interface.
 */
function resolveOnlyofficeCreateReturnUrl(string $rawReturnUrl): string
{
    $rawReturnUrl = trim($rawReturnUrl);
    if ('' === $rawReturnUrl) {
        return '';
    }

    $baseUrl = rtrim((string) api_get_path(WEB_PATH), '/');
    $baseParts = parse_url($baseUrl);
    if (!is_array($baseParts) || empty($baseParts['scheme']) || empty($baseParts['host'])) {
        return '';
    }

    if (str_starts_with($rawReturnUrl, '/') && !str_starts_with($rawReturnUrl, '//')) {
        $port = isset($baseParts['port']) ? ':'.(int) $baseParts['port'] : '';
        $rawReturnUrl = $baseParts['scheme'].'://'.$baseParts['host'].$port.$rawReturnUrl;
    }

    if (!filter_var($rawReturnUrl, FILTER_VALIDATE_URL)) {
        return '';
    }

    $returnParts = parse_url($rawReturnUrl);
    if (!is_array($returnParts) || empty($returnParts['scheme']) || empty($returnParts['host'])) {
        return '';
    }

    $basePort = isset($baseParts['port']) ? (int) $baseParts['port'] : null;
    $returnPort = isset($returnParts['port']) ? (int) $returnParts['port'] : null;

    if (
        strtolower((string) $baseParts['scheme']) !== strtolower((string) $returnParts['scheme'])
        || strtolower((string) $baseParts['host']) !== strtolower((string) $returnParts['host'])
        || $basePort !== $returnPort
    ) {
        return '';
    }

    return $rawReturnUrl;
}
