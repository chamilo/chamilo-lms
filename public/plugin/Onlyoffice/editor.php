<?php
/**
 * (c) Copyright Ascensio System SIA 2025.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * You may not use this file except in compliance with the License.
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

$plugin = OnlyofficePlugin::create();

$isEnabled = 'true' === $plugin->get('enable_onlyoffice_plugin');
if (!$isEnabled) {
    exit("Document server is not enabled.");
}

$appSettings = new OnlyofficeAppsettings($plugin);
$documentServerUrl = $appSettings->getDocumentServerUrl();
if (empty($documentServerUrl)) {
    exit("Document server is not configured.");
}

$docApiUrl = $appSettings->getDocumentServerApiUrl();
if (empty($docApiUrl)) {
    exit("Document server API URL is not configured.");
}

$docId = isset($_GET['docId']) ? (int) $_GET['docId'] : null;
$docPath = isset($_GET['doc']) ? urldecode((string) $_GET['doc']) : null;

$groupId = isset($_GET['groupId']) && !empty($_GET['groupId'])
    ? (int) $_GET['groupId']
    : (!empty($_GET['gidReq']) ? (int) $_GET['gidReq'] : 0);

$userId = (int) api_get_user_id();
$userInfo = api_get_user_info($userId);
$sessionId = (int) api_get_session_id();
$courseId = (int) api_get_course_int_id();
$courseInfo = api_get_course_info();
if (empty($courseInfo)) {
    api_not_allowed(true);
}
$courseCode = $courseInfo['code'];
$exerciseId = isset($_GET['exerciseId']) ? (int) $_GET['exerciseId'] : null;
$exeId = isset($_GET['exeId']) ? (int) $_GET['exeId'] : null;
$questionId = isset($_GET['questionId']) ? (int) $_GET['questionId'] : null;
$isReadOnly = isset($_GET['readOnly']) ? (int) $_GET['readOnly'] : null;
$forceEdit = isset($_GET['forceEdit']) && in_array(strtolower((string) $_GET['forceEdit']), ['1', 'true', 'yes', 'on'], true);

$docInfo = null;
$fileId = null;
$fileUrl = null;

if (!empty($docPath)) {
    $filePath = api_get_path(SYS_COURSE_PATH).$docPath;
    if (!file_exists($filePath)) {
        error_log('ONLYOFFICE editor: original file not found -> '.$filePath);
        exit('Error: Document not found.');
    }

    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    $fileUrl = api_get_path(WEB_COURSE_PATH).$docPath;
    $newDocPath = $docPath;
    $userFilePath = $filePath;

    if ($exeId) {
        $newDocPath = api_get_course_path()."/exercises/onlyoffice/{$exerciseId}/{$questionId}/{$userId}/response_{$exeId}.{$extension}";
        $userFilePath = api_get_path(SYS_COURSE_PATH).$newDocPath;

        if (!file_exists($userFilePath)) {
            if (!is_dir(dirname($userFilePath))) {
                mkdir(dirname($userFilePath), 0775, true);
            }
            if (!copy($filePath, $userFilePath)) {
                exit('Error: Failed to create a document copy.');
            }
        }
        $fileUrl = api_get_path(WEB_COURSE_PATH).$newDocPath;
    }

    $fileId = basename($newDocPath);
    $absolutePath = $userFilePath;
    $absoluteParentPath = dirname($userFilePath).'/';
    $data = [
        'type' => 'download',
        'doctype' => 'exercise',
        'docPath' => urlencode($newDocPath),
        'courseId' => $courseId,
        'userId' => $userId,
        'docId' => $fileId,
        'sessionId' => $sessionId,
    ];

    if (!empty($groupId)) {
        $data['groupId'] = $groupId;
    }

    $jwtManager = new OnlyofficeJwtManager($appSettings);
    $hashUrl = $jwtManager->getHash($data);
    $callbackUrl = api_get_path(WEB_PLUGIN_PATH).'Onlyoffice/callback.php?hash='.$hashUrl.'&docPath='.urlencode($newDocPath);

    $docInfo = [
        'iid' => null,
        'id' => null,
        'c_id' => $courseId,
        'path' => $newDocPath,
        'comment' => null,
        'title' => basename($userFilePath),
        'filetype' => 'file',
        'size' => filesize($userFilePath),
        'readonly' => (int) $isReadOnly,
        'session_id' => $sessionId,
        'url' => api_get_path(WEB_PLUGIN_PATH).'Onlyoffice/editor.php?doc='.urlencode($newDocPath)
            .($exeId ? '&exeId='.$exeId : '')
            .($exerciseId ? '&exerciseId='.$exerciseId : '')
            .($questionId ? '&questionId='.$questionId : '')
            .($isReadOnly ? '&readOnly='.$isReadOnly : '')
            .($groupId ? '&groupId='.$groupId : '')
            .($forceEdit ? '&forceEdit=true' : ''),
        'document_url' => $callbackUrl,
        'absolute_path' => $absolutePath,
        'absolute_path_from_document' => '/document/'.basename($userFilePath),
        'absolute_parent_path' => $absoluteParentPath,
        'direct_url' => $callbackUrl,
        'basename' => basename($userFilePath),
        'parent_id' => 0,
        'parents' => [],
        'forceEdit' => $forceEdit,
        'exercise_id' => $exerciseId,
        'creator_id' => $userId,
    ];
} elseif (!empty($docId)) {
    $docInfo = DocumentManager::get_document_data_by_id($docId, $courseCode, false, $sessionId);

    if ($docInfo) {
        $fileId = $docId;

        if (!isset($docInfo['forceEdit'])) {
            $docInfo['forceEdit'] = $forceEdit;
        }

        $fileUrl = (new OnlyofficeDocumentManager($appSettings, $docInfo))->getFileUrl((string) $docId);
    }
}

if (empty($docInfo) || empty($fileId)) {
    error_log('ONLYOFFICE editor: document not found.');
    exit('Error: Document not found.');
}

$jwtManager = new OnlyofficeJwtManager($appSettings);

if ($forceEdit) {
    $docInfo['forceEdit'] = true;
}

$documentManager = new OnlyofficeDocumentManager($appSettings, $docInfo);
$extension = $documentManager->getExt((string) $documentManager->getDocInfo('title'));
$docType = $documentManager->getDocType($extension);

$fileIdentifier = $docId ? (string) $docId : md5((string) $docPath);
$key = $documentManager->getDocumentKey($fileIdentifier, $courseCode);
$fileUrl = $fileUrl ?? $documentManager->getFileUrl($fileIdentifier);

if (!empty($appSettings->getStorageUrl()) && !empty($fileUrl)) {
    $fileUrl = str_replace(api_get_path(WEB_PATH), $appSettings->getStorageUrl(), $fileUrl);
}

$configService = new OnlyofficeConfigService($appSettings, $jwtManager, $documentManager);
$editorsMode = $configService->getEditorsMode();

$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
$config = $configService->createConfig($fileIdentifier, $editorsMode, $userAgent);
$config = json_decode(json_encode($config), true);
if (empty($config) || !is_array($config)) {
    error_log('ONLYOFFICE editor: failed to generate editor configuration.');
    exit('Error: Failed to generate the configuration for ONLYOFFICE.');
}

if (!isset($config['document']) || !is_array($config['document'])) {
    $config['document'] = [];
}

if (!isset($config['editorConfig']) || !is_array($config['editorConfig'])) {
    $config['editorConfig'] = [];
}

if (!empty($fileUrl)) {
    $config['document']['url'] = $fileUrl;
}

if (!empty($key)) {
    $config['document']['key'] = $key;
}

if (!empty($docType)) {
    $config['documentType'] = $docType;
}

$isMobileAgent = $configService->isMobileAgent($userAgent);
$langCode = $configService->getLang();
$editorContainerId = 'iframeEditor';

?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars((string) $langCode, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ONLYOFFICE</title>
    <style>
        html,
        body,
        #<?php echo $editorContainerId; ?> {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            overflow: hidden;
            background: #ffffff;
            font-family: Arial, sans-serif;
        }
    </style>
    <script type="text/javascript" src="<?php echo htmlspecialchars((string) $docApiUrl, ENT_QUOTES, 'UTF-8'); ?>"></script>
</head>
<body>
<div id="<?php echo $editorContainerId; ?>"></div>

<script type="text/javascript">
    (function () {
        const config = <?php echo json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
        const errorPage = <?php echo json_encode(api_get_path(WEB_PLUGIN_PATH).'Onlyoffice/error.php'); ?>;
        const saveAsUrl = <?php echo json_encode(api_get_path(WEB_PLUGIN_PATH).'Onlyoffice/ajax/saveas.php'); ?>;
        const folderId = <?php echo json_encode((int) ($docInfo['parent_id'] ?? 0)); ?>;
        const sessionId = <?php echo json_encode((int) $sessionId); ?>;
        const courseId = <?php echo json_encode((int) $courseId); ?>;
        const groupId = <?php echo json_encode((int) $groupId); ?>;
        const editorContainerId = <?php echo json_encode($editorContainerId); ?>;
        const isMobileAgent = <?php echo json_encode((bool) $isMobileAgent); ?>;

        function onAppReady() {
            console.log('ONLYOFFICE editor ready');
        }

        function onRequestSaveAs(event) {
            const payload = {
                title: event.data.title,
                url: event.data.url,
                folderId: folderId,
                sessionId: sessionId,
                courseId: courseId,
                groupId: groupId
            };

            fetch(saveAsUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (response) {
                    if (response && response.error) {
                        console.error('ONLYOFFICE save-as error:', response.error);
                    }
                })
                .catch(function (error) {
                    console.error('ONLYOFFICE save-as request failed:', error);
                });
        }

        function onRequestEditRights() {
            const url = new URL(window.location.href);
            url.searchParams.set('forceEdit', 'true');
            window.location.href = url.toString();
        }

        function checkDocsVersion() {
            if (typeof DocsAPI === 'undefined' || !DocsAPI.DocEditor || typeof DocsAPI.DocEditor.version !== 'function') {
                console.error('ONLYOFFICE DocsAPI is not available.');
                return false;
            }

            const docsVersion = DocsAPI.DocEditor.version().split('.');
            const major = parseInt(docsVersion[0] || '0', 10);
            const minor = parseInt(docsVersion[1] || '0', 10);

            if ((config.document && config.document.fileType === 'pdf') && major < 8) {
                window.location.href = errorPage + '?status=1';
                return false;
            }

            if (major < 6 || (major === 6 && minor === 0)) {
                window.location.href = errorPage + '?status=2';
                return false;
            }

            return true;
        }

        function connectEditor() {
            if (!checkDocsVersion()) {
                return;
            }

            config.events = {
                onAppReady: onAppReady,
                onRequestSaveAs: onRequestSaveAs,
                onRequestEditRights: onRequestEditRights
            };

            window.docEditor = new DocsAPI.DocEditor(editorContainerId, config);

            if (isMobileAgent) {
                const iframe = document.querySelector('#' + editorContainerId + ' iframe');
                if (iframe) {
                    iframe.style.height = '100%';
                    iframe.style.top = '0';
                }
            }
        }

        window.addEventListener('load', connectEditor);
    })();
</script>
</body>
</html>
