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

use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CDocument;

const ONLYOFFICE_EDITOR_LOG_ENABLED = false;

$plugin = OnlyofficePlugin::create();

$isEnabled = 'true' === $plugin->get('enable_onlyoffice_plugin');
if (!$isEnabled) {
    exit('Document server is not enabled.');
}

$appSettings = new OnlyofficeAppsettings($plugin);
$documentServerUrl = $appSettings->getDocumentServerUrl();
if (empty($documentServerUrl)) {
    exit('Document server is not configured.');
}

$docApiUrl = $appSettings->getDocumentServerApiUrl();
if (empty($docApiUrl)) {
    exit('Document server API URL is not configured.');
}

$isMetaRequest = isset($_GET['meta']) && '1' === (string) $_GET['meta'];
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
$callbackUrl = null;

$jwtManager = new OnlyofficeJwtManager($appSettings);

onlyofficeEditorLog('DEBUG', 'Editor entry', [
    'docId' => $docId,
    'docPath' => $docPath,
    'courseId' => $courseId,
    'courseCode' => $courseCode,
    'sessionId' => $sessionId,
    'groupId' => $groupId,
    'userId' => $userId,
    'readOnly' => $isReadOnly,
    'forceEdit' => $forceEdit,
    'exerciseId' => $exerciseId,
    'exeId' => $exeId,
    'questionId' => $questionId,
    'meta' => $isMetaRequest,
]);

if (!empty($docPath)) {
    $filePath = api_get_path(SYS_COURSE_PATH).$docPath;
    if (!file_exists($filePath)) {
        onlyofficeEditorLog('ERROR', 'Original file not found', [
            'filePath' => $filePath,
        ]);
        exit('Error: Document not found.');
    }

    $extension = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));
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
    }

    $fileId = basename($newDocPath);
    $versionToken = buildOnlyofficeVersionTokenFromFile($userFilePath, $newDocPath);

    $downloadPayload = [
        'type' => 'download',
        'doctype' => 'exercise',
        'docPath' => $newDocPath,
        'courseId' => $courseId,
        'userId' => $userId,
        'docId' => $fileId,
        'sessionId' => $sessionId,
    ];

    $trackPayload = [
        'type' => 'track',
        'doctype' => 'exercise',
        'docPath' => $newDocPath,
        'courseId' => $courseId,
        'userId' => $userId,
        'docId' => $fileId,
        'sessionId' => $sessionId,
    ];

    if (!empty($groupId)) {
        $downloadPayload['groupId'] = $groupId;
        $trackPayload['groupId'] = $groupId;
    }

    $downloadHash = $jwtManager->getHash($downloadPayload);
    $trackHash = $jwtManager->getHash($trackPayload);

    $fileUrl = appendVersionTokenToUrl(
        api_get_path(WEB_PLUGIN_PATH).'Onlyoffice/callback.php?hash='.$downloadHash.'&docPath='.urlencode($newDocPath),
        $versionToken
    );

    $callbackUrl = appendVersionTokenToUrl(
        api_get_path(WEB_PLUGIN_PATH).'Onlyoffice/callback.php?hash='.$trackHash.'&docPath='.urlencode($newDocPath),
        $versionToken
    );

    $docInfo = [
        'iid' => null,
        'id' => null,
        'c_id' => $courseId,
        'path' => $newDocPath,
        'comment' => null,
        'title' => basename($userFilePath),
        'filetype' => 'file',
        'size' => (int) filesize($userFilePath),
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
        'direct_url' => $fileUrl,
        'basename' => basename($userFilePath),
        'parent_id' => 0,
        'parents' => [],
        'forceEdit' => $forceEdit,
        'exercise_id' => $exerciseId,
        'creator_id' => $userId,
        'version_token' => $versionToken,
    ];

    onlyofficeEditorLog('DEBUG', 'Resolved direct path document', [
        'fileId' => $fileId,
        'path' => $newDocPath,
        'versionToken' => $versionToken,
    ]);
} elseif (!empty($docId)) {
    $resolvedC2 = resolveDocumentSourceFromC2ForEditor($docId);

    if (null !== $resolvedC2) {
        $fileId = $docId;
        $versionToken = $resolvedC2['versionToken'];

        $downloadPayload = [
            'type' => 'download',
            'courseId' => $courseId,
            'userId' => $userId,
            'docId' => $docId,
            'sessionId' => $sessionId,
        ];

        $trackPayload = [
            'type' => 'track',
            'courseId' => $courseId,
            'userId' => $userId,
            'docId' => $docId,
            'sessionId' => $sessionId,
        ];

        if (!empty($groupId)) {
            $downloadPayload['groupId'] = $groupId;
            $trackPayload['groupId'] = $groupId;
        }

        $downloadHash = $jwtManager->getHash($downloadPayload);
        $trackHash = $jwtManager->getHash($trackPayload);

        $fileUrl = appendVersionTokenToUrl(
            api_get_path(WEB_PLUGIN_PATH).'Onlyoffice/callback.php?hash='.$downloadHash,
            $versionToken
        );

        $callbackUrl = appendVersionTokenToUrl(
            api_get_path(WEB_PLUGIN_PATH).'Onlyoffice/callback.php?hash='.$trackHash,
            $versionToken
        );

        $docInfo = [
            'iid' => $docId,
            'id' => $docId,
            'c_id' => $courseId,
            'path' => $resolvedC2['storagePath'],
            'comment' => $resolvedC2['comment'],
            'title' => $resolvedC2['title'],
            'filetype' => 'file',
            'size' => $resolvedC2['size'],
            'readonly' => (int) $isReadOnly,
            'session_id' => $sessionId,
            'url' => api_get_path(WEB_PLUGIN_PATH).'Onlyoffice/editor.php?docId='.$docId
                .($isReadOnly ? '&readOnly='.$isReadOnly : '')
                .($groupId ? '&groupId='.$groupId : '')
                .($forceEdit ? '&forceEdit=true' : ''),
            'document_url' => $callbackUrl,
            'direct_url' => $fileUrl,
            'basename' => basename((string) $resolvedC2['title']),
            'parent_id' => $resolvedC2['parentId'],
            'parents' => [],
            'forceEdit' => $forceEdit,
            'exercise_id' => $exerciseId,
            'creator_id' => $resolvedC2['creatorId'],
            'resource_node_id' => $resolvedC2['resourceNodeId'],
            'resource_file_id' => $resolvedC2['resourceFileId'],
            'version_token' => $versionToken,
        ];

        onlyofficeEditorLog('DEBUG', 'Resolved C2 document', [
            'docId' => $docId,
            'title' => $resolvedC2['title'],
            'resourceNodeId' => $resolvedC2['resourceNodeId'],
            'resourceFileId' => $resolvedC2['resourceFileId'],
            'storagePath' => $resolvedC2['storagePath'],
            'versionToken' => $versionToken,
        ]);
    } else {
        $docInfo = DocumentManager::get_document_data_by_id($docId, $courseCode, false, $sessionId);

        if ($docInfo) {
            $fileId = $docId;

            if (!isset($docInfo['forceEdit'])) {
                $docInfo['forceEdit'] = $forceEdit;
            }

            $versionToken = buildOnlyofficeVersionTokenFromLegacyDocInfo($docInfo, (string) $docId);

            $downloadPayload = [
                'type' => 'download',
                'courseId' => $courseId,
                'userId' => $userId,
                'docId' => $docId,
                'sessionId' => $sessionId,
            ];

            $trackPayload = [
                'type' => 'track',
                'courseId' => $courseId,
                'userId' => $userId,
                'docId' => $docId,
                'sessionId' => $sessionId,
            ];

            if (!empty($groupId)) {
                $downloadPayload['groupId'] = $groupId;
                $trackPayload['groupId'] = $groupId;
            }

            $downloadHash = $jwtManager->getHash($downloadPayload);
            $trackHash = $jwtManager->getHash($trackPayload);

            $fileUrl = appendVersionTokenToUrl(
                api_get_path(WEB_PLUGIN_PATH).'Onlyoffice/callback.php?hash='.$downloadHash,
                $versionToken
            );

            $callbackUrl = appendVersionTokenToUrl(
                api_get_path(WEB_PLUGIN_PATH).'Onlyoffice/callback.php?hash='.$trackHash,
                $versionToken
            );

            $docInfo['direct_url'] = $fileUrl;
            $docInfo['document_url'] = $callbackUrl;
            $docInfo['version_token'] = $versionToken;

            onlyofficeEditorLog('DEBUG', 'Resolved legacy document', [
                'docId' => $docId,
                'absolutePath' => $docInfo['absolute_path'] ?? '',
                'versionToken' => $versionToken,
            ]);
        }
    }
}

if (empty($docInfo) || empty($fileId)) {
    onlyofficeEditorLog('ERROR', 'Document not found', [
        'docId' => $docId,
        'docPath' => $docPath,
    ]);
    exit('Error: Document not found.');
}

if ($forceEdit) {
    $docInfo['forceEdit'] = true;
}

$documentManager = new OnlyofficeDocumentManager($appSettings, $docInfo);
$extension = strtolower((string) $documentManager->getExt((string) $documentManager->getDocInfo('title')));
$docType = $documentManager->getDocType($extension);

$editorReadOnly = shouldOpenOnlyofficeInReadOnlyMode(
    $extension,
    $isReadOnly,
    $forceEdit,
    $exeId
);

$fileIdentifier = $docId ? (string) $docId : md5((string) $docPath);
$versionToken = (string) ($docInfo['version_token'] ?? buildOnlyofficeVersionTokenFromLegacyDocInfo($docInfo, $fileIdentifier));
$runtimeIdentifier = buildOnlyofficeRuntimeFileIdentifier($fileIdentifier, $versionToken);
$runtimeKey = buildOnlyofficeRuntimeDocumentKey($fileIdentifier, $courseCode, $docInfo, $versionToken);
$documentIdentity = buildOnlyofficeDocumentIdentity($courseId, $sessionId, $groupId, $fileIdentifier);
$metaUrl = buildOnlyofficeMetaUrl($docId, $docPath, $groupId, $exerciseId, $exeId, $questionId, $isReadOnly, $forceEdit);

$fileUrl = $fileUrl ?? $documentManager->getFileUrl($runtimeIdentifier);

if (!empty($appSettings->getStorageUrl()) && !empty($fileUrl)) {
    $fileUrl = str_replace(api_get_path(WEB_PATH), $appSettings->getStorageUrl(), $fileUrl);
    if (!empty($callbackUrl)) {
        $callbackUrl = str_replace(api_get_path(WEB_PATH), $appSettings->getStorageUrl(), $callbackUrl);
    }
    if (!empty($metaUrl)) {
        $metaUrl = str_replace(api_get_path(WEB_PATH), $appSettings->getStorageUrl(), $metaUrl);
    }
}

if ($isMetaRequest) {
    sendOnlyofficeEditorNoCacheHeaders();
    @header('Content-Type: application/json');

    echo json_encode([
        'status' => 'ok',
        'docId' => $docId,
        'fileIdentifier' => $fileIdentifier,
        'documentIdentity' => $documentIdentity,
        'versionToken' => $versionToken,
        'key' => $runtimeKey,
        'readonly' => $editorReadOnly,
        'extension' => $extension,
        'size' => (int) ($docInfo['size'] ?? 0),
    ]);

    exit;
}

$configService = new OnlyofficeConfigService($appSettings, $jwtManager, $documentManager);
$editorsMode = $configService->getEditorsMode();

$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
$config = $configService->createConfig($runtimeIdentifier, $editorsMode, $userAgent);
$config = onlyofficeEditorConfigToArray($config);

if (empty($config) || !is_array($config)) {
    onlyofficeEditorLog('ERROR', 'Failed to generate editor configuration');
    exit('Error: Failed to generate the configuration for ONLYOFFICE.');
}

if (!isset($config['document']) || !is_array($config['document'])) {
    $config['document'] = [];
}

if (!isset($config['editorConfig']) || !is_array($config['editorConfig'])) {
    $config['editorConfig'] = [];
}

if (!isset($config['document']['permissions']) || !is_array($config['document']['permissions'])) {
    $config['document']['permissions'] = [];
}

if (!isset($config['editorConfig']['customization']) || !is_array($config['editorConfig']['customization'])) {
    $config['editorConfig']['customization'] = [];
}

$config['document']['url'] = $fileUrl;
$config['document']['title'] = (string) ($docInfo['title'] ?? basename((string) ($docInfo['path'] ?? 'document')));
$config['document']['key'] = $runtimeKey;
$config['document']['fileType'] = $extension;
$config['documentType'] = $docType;

$config['editorConfig']['callbackUrl'] = $callbackUrl;
$config['editorConfig']['mode'] = $editorReadOnly ? 'view' : 'edit';

$config['document']['permissions']['edit'] = !$editorReadOnly;
$config['document']['permissions']['review'] = !$editorReadOnly;
$config['document']['permissions']['comment'] = true;
$config['document']['permissions']['download'] = true;
$config['document']['permissions']['print'] = true;
$config['document']['permissions']['copy'] = true;

$config['editorConfig']['customization']['autosave'] = true;
$config['editorConfig']['customization']['forcesave'] = true;

$config = refreshOnlyofficeEditorToken($config, $jwtManager, $appSettings);

$isMobileAgent = $configService->isMobileAgent($userAgent);
$langCode = $configService->getLang();
$editorContainerId = 'iframeEditor';

onlyofficeEditorLog('DEBUG', 'Final config summary', [
    'docId' => (string) ($docId ?? 0),
    'key' => $config['document']['key'] ?? '',
    'fileUrl' => $config['document']['url'] ?? '',
    'callbackUrl' => $config['editorConfig']['callbackUrl'] ?? '',
    'readonly' => $editorReadOnly,
    'versionToken' => $versionToken,
    'extension' => $extension,
    'jwtTokenPresent' => !empty($config['token']),
    'metaUrl' => $metaUrl,
]);

sendOnlyofficeEditorNoCacheHeaders();

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
            const debugEnabled = <?php echo json_encode((bool) ONLYOFFICE_EDITOR_LOG_ENABLED); ?>;

            const isEditableMode = !!(
                config &&
                config.editorConfig &&
                config.editorConfig.mode === "edit"
            );

            function debugLog() {
                if (!debugEnabled) {
                    return;
                }

                const args = Array.prototype.slice.call(arguments);
                console.log.apply(console, args);
            }

            function getNavigationType() {
                try {
                    const entries = performance.getEntriesByType("navigation");
                    if (entries && entries.length > 0 && entries[0].type) {
                        return entries[0].type;
                    }

                    if (performance.navigation) {
                        if (performance.navigation.type === 1) {
                            return "reload";
                        }

                        if (performance.navigation.type === 0) {
                            return "navigate";
                        }
                    }
                } catch (error) {
                    debugLog("ONLYOFFICE failed to detect navigation type", error);
                }

                return "unknown";
            }

            function leaveEditorSafely() {
                if (window.history.length > 1) {
                    window.history.back();
                    return;
                }

                if (document.referrer && document.referrer !== window.location.href) {
                    window.location.href = document.referrer;
                    return;
                }

                window.location.href = <?php echo json_encode(api_get_path(WEB_PATH)); ?>;
            }

            function handleUnsafeReload() {
                alert("Refreshing the editor while the document session is still open is not supported. You will return to the previous page so the document can be reopened safely.");
                leaveEditorSafely();
            }

            function onAppReady() {
                debugLog("ONLYOFFICE editor ready");
            }

            function onDocumentReady() {
                debugLog("ONLYOFFICE document ready");
            }

            function onDocumentStateChange(event) {
                debugLog("ONLYOFFICE document state changed", event);
            }

            function onError(event) {
                debugLog("ONLYOFFICE editor error", event);
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
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(payload)
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (response) {
                        if (response && response.error) {
                            console.error("ONLYOFFICE save-as error:", response.error);
                        }
                    })
                    .catch(function (error) {
                        console.error("ONLYOFFICE save-as request failed:", error);
                    });
            }

            function onRequestEditRights() {
                const url = new URL(window.location.href);
                url.searchParams.set("forceEdit", "true");
                window.location.href = url.toString();
            }

            function checkDocsVersion() {
                if (typeof DocsAPI === "undefined" || !DocsAPI.DocEditor || typeof DocsAPI.DocEditor.version !== "function") {
                    console.error("ONLYOFFICE DocsAPI is not available.");
                    return false;
                }

                const docsVersion = DocsAPI.DocEditor.version().split(".");
                const major = parseInt(docsVersion[0] || "0", 10);
                const minor = parseInt(docsVersion[1] || "0", 10);

                if ((config.document && config.document.fileType === "pdf") && major < 8) {
                    window.location.href = errorPage + "?status=1";
                    return false;
                }

                if (major < 6 || (major === 6 && minor === 0)) {
                    window.location.href = errorPage + "?status=2";
                    return false;
                }

                return true;
            }

            function connectEditor() {
                if (!checkDocsVersion()) {
                    return;
                }

                const navigationType = getNavigationType();
                debugLog("ONLYOFFICE navigation type", navigationType);

                if (isEditableMode && navigationType === "reload") {
                    handleUnsafeReload();
                    return;
                }

                config.events = {
                    onAppReady: onAppReady,
                    onDocumentReady: onDocumentReady,
                    onDocumentStateChange: onDocumentStateChange,
                    onError: onError,
                    onRequestSaveAs: onRequestSaveAs,
                    onRequestEditRights: onRequestEditRights
                };

                window.docEditor = new DocsAPI.DocEditor(editorContainerId, config);

                if (isMobileAgent) {
                    const iframe = document.querySelector("#" + editorContainerId + " iframe");
                    if (iframe) {
                        iframe.style.height = "100%";
                        iframe.style.top = "0";
                    }
                }
            }

            window.addEventListener("keydown", function (event) {
                if (!isEditableMode) {
                    return;
                }

                const key = String(event.key || "").toLowerCase();
                const isReloadShortcut =
                    key === "f5" ||
                    ((event.ctrlKey || event.metaKey) && key === "r");

                if (!isReloadShortcut) {
                    return;
                }

                event.preventDefault();
                handleUnsafeReload();
            });

            window.addEventListener("beforeunload", function (event) {
                if (!isEditableMode) {
                    return;
                }

                event.preventDefault();
                event.returnValue = "";
                return "";
            });

            window.addEventListener("load", connectEditor);
        })();
    </script>
    </body>
    </html>
<?php

/**
 * Resolve a C2 document for editor usage.
 */
function resolveDocumentSourceFromC2ForEditor(int $docId): ?array
{
    $entityManager = getEntityManagerForOnlyofficeEditor();
    if (null === $entityManager) {
        onlyofficeEditorLog('ERROR', 'Entity manager could not be resolved');

        return null;
    }

    /** @var CDocument|null $document */
    $document = $entityManager->getRepository(CDocument::class)->find($docId);
    if (!$document instanceof CDocument) {
        onlyofficeEditorLog('ERROR', 'CDocument not found', [
            'docId' => $docId,
        ]);

        return null;
    }

    $resourceNode = $document->getResourceNode();
    if (!$resourceNode instanceof ResourceNode) {
        onlyofficeEditorLog('ERROR', 'Resource node not found', [
            'docId' => $docId,
        ]);

        return null;
    }

    $resourceFile = $resourceNode->getFirstResourceFile();
    if (!$resourceFile) {
        onlyofficeEditorLog('ERROR', 'Resource file not found', [
            'docId' => $docId,
            'resourceNodeId' => (int) $resourceNode->getId(),
        ]);

        return null;
    }

    $resourceNodeRepository = getResourceNodeRepositoryForOnlyofficeEditor();
    if (null === $resourceNodeRepository) {
        onlyofficeEditorLog('ERROR', 'ResourceNodeRepository could not be resolved');

        return null;
    }

    $storagePath = '';
    try {
        $storagePath = (string) $resourceNodeRepository->getFilename($resourceFile);
    } catch (\Throwable $e) {
        onlyofficeEditorLog('WARNING', 'Failed to resolve storage path', [
            'message' => $e->getMessage(),
        ]);
    }

    $title = (string) ($resourceFile->getOriginalName() ?: $document->getTitle() ?: $resourceNode->getTitle());
    $size = (int) ($resourceFile->getSize() ?? 0);

    $versionToken = buildOnlyofficeVersionTokenFromDatabase(
        $entityManager,
        (int) $resourceNode->getId(),
        (int) $resourceFile->getId(),
        $size,
        $storagePath ?: $title
    );

    return [
        'title' => $title,
        'size' => $size,
        'comment' => method_exists($document, 'getComment') ? $document->getComment() : null,
        'storagePath' => $storagePath,
        'resourceNodeId' => (int) $resourceNode->getId(),
        'resourceFileId' => (int) $resourceFile->getId(),
        'parentId' => $resourceNode->getParent() ? (int) $resourceNode->getParent()->getId() : 0,
        'creatorId' => $resourceNode->getCreator() ? (int) $resourceNode->getCreator()->getId() : (int) api_get_user_id(),
        'versionToken' => $versionToken,
    ];
}

/**
 * Resolve Doctrine entity manager.
 */
function getEntityManagerForOnlyofficeEditor()
{
    try {
        if (isset(Container::$container) && null !== Container::$container) {
            if (Container::$container->has('doctrine.orm.entity_manager')) {
                return Container::$container->get('doctrine.orm.entity_manager');
            }

            if (Container::$container->has('doctrine')) {
                $doctrine = Container::$container->get('doctrine');

                return $doctrine->getManager();
            }
        }
    } catch (\Throwable $e) {
        onlyofficeEditorLog('WARNING', 'Failed to resolve entity manager from container', [
            'message' => $e->getMessage(),
        ]);
    }

    if (class_exists('Database') && method_exists('Database', 'getManager')) {
        try {
            return Database::getManager();
        } catch (\Throwable $e) {
            onlyofficeEditorLog('WARNING', 'Database::getManager failed', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    return null;
}

/**
 * Resolve ResourceNodeRepository.
 */
function getResourceNodeRepositoryForOnlyofficeEditor(): ?ResourceNodeRepository
{
    try {
        if (isset(Container::$container) && null !== Container::$container) {
            if (Container::$container->has(ResourceNodeRepository::class)) {
                $repo = Container::$container->get(ResourceNodeRepository::class);

                if ($repo instanceof ResourceNodeRepository) {
                    return $repo;
                }
            }

            $serviceIds = [
                'Chamilo\\CoreBundle\\Repository\\ResourceNodeRepository',
                'chamilo.repository.resource_node',
            ];

            foreach ($serviceIds as $serviceId) {
                if (!Container::$container->has($serviceId)) {
                    continue;
                }

                $repo = Container::$container->get($serviceId);

                if ($repo instanceof ResourceNodeRepository) {
                    return $repo;
                }
            }
        }
    } catch (\Throwable $e) {
        onlyofficeEditorLog('WARNING', 'Failed to resolve ResourceNodeRepository', [
            'message' => $e->getMessage(),
        ]);
    }

    return null;
}

/**
 * Build a version token from database timestamps for C2 documents.
 */
function buildOnlyofficeVersionTokenFromDatabase($entityManager, int $resourceNodeId, int $resourceFileId, int $size, string $storagePath): string
{
    try {
        $row = $entityManager->getConnection()->fetchAssociative(
            'SELECT rf.updated_at AS resource_file_updated_at,
                    rf.size AS resource_file_size,
                    rn.updated_at AS resource_node_updated_at
             FROM resource_file rf
             INNER JOIN resource_node rn ON rn.id = :resource_node_id
             WHERE rf.id = :resource_file_id',
            [
                'resource_node_id' => $resourceNodeId,
                'resource_file_id' => $resourceFileId,
            ]
        );

        $seed = [
            'resourceNodeId' => $resourceNodeId,
            'resourceFileId' => $resourceFileId,
            'resourceFileUpdatedAt' => $row['resource_file_updated_at'] ?? '',
            'resourceNodeUpdatedAt' => $row['resource_node_updated_at'] ?? '',
            'resourceFileSize' => $row['resource_file_size'] ?? $size,
            'storagePath' => $storagePath,
        ];

        return substr(hash('sha256', (string) json_encode($seed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)), 0, 20);
    } catch (\Throwable $e) {
        onlyofficeEditorLog('WARNING', 'Failed to build DB version token', [
            'message' => $e->getMessage(),
        ]);
    }

    return substr(hash('sha256', $resourceNodeId.'|'.$resourceFileId.'|'.$size.'|'.$storagePath), 0, 20);
}

/**
 * Build a version token from a real file.
 */
function buildOnlyofficeVersionTokenFromFile(string $absolutePath, string $fallbackPath = ''): string
{
    if ('' !== $absolutePath && file_exists($absolutePath)) {
        return substr(hash('sha256', implode('|', [
            $absolutePath,
            (string) filesize($absolutePath),
            (string) filemtime($absolutePath),
        ])), 0, 20);
    }

    return substr(hash('sha256', $fallbackPath), 0, 20);
}

/**
 * Build a version token from legacy document info.
 */
function buildOnlyofficeVersionTokenFromLegacyDocInfo(array $docInfo, string $fallbackIdentifier): string
{
    $absolutePath = (string) ($docInfo['absolute_path'] ?? '');

    if ('' !== $absolutePath && file_exists($absolutePath)) {
        return buildOnlyofficeVersionTokenFromFile($absolutePath, $fallbackIdentifier);
    }

    return substr(hash('sha256', implode('|', [
        $fallbackIdentifier,
        (string) ($docInfo['size'] ?? 0),
        (string) ($docInfo['path'] ?? ''),
        (string) ($docInfo['title'] ?? ''),
    ])), 0, 20);
}

/**
 * Build a runtime file identifier.
 */
function buildOnlyofficeRuntimeFileIdentifier(string $fileIdentifier, string $versionToken): string
{
    return substr(hash('sha256', $fileIdentifier.'|'.$versionToken), 0, 32);
}

/**
 * Build a runtime document key.
 */
function buildOnlyofficeRuntimeDocumentKey(string $fileIdentifier, string $courseCode, array $docInfo, string $versionToken): string
{
    $parts = [
        $courseCode,
        $fileIdentifier,
        (string) ($docInfo['iid'] ?? ''),
        (string) ($docInfo['title'] ?? ''),
        (string) ($docInfo['resource_file_id'] ?? ''),
        (string) ($docInfo['resource_node_id'] ?? ''),
        $versionToken,
    ];

    return substr(hash('sha256', implode('|', $parts)), 0, 32);
}

/**
 * Build a stable document identity for browser-side reload handling.
 */
function buildOnlyofficeDocumentIdentity(int $courseId, int $sessionId, int $groupId, string $fileIdentifier): string
{
    return implode(':', [
        'c'.$courseId,
        's'.$sessionId,
        'g'.$groupId,
        'f'.$fileIdentifier,
    ]);
}

/**
 * Build meta URL for the current editor request.
 */
function buildOnlyofficeMetaUrl(
    ?int $docId,
    ?string $docPath,
    int $groupId,
    ?int $exerciseId,
    ?int $exeId,
    ?int $questionId,
    ?int $isReadOnly,
    bool $forceEdit
): string {
    $params = [
        'meta' => '1',
    ];

    if (!empty($docId)) {
        $params['docId'] = (string) $docId;
    }

    if (!empty($docPath)) {
        $params['doc'] = $docPath;
    }

    if (!empty($groupId)) {
        $params['groupId'] = (string) $groupId;
    }

    if (!empty($exerciseId)) {
        $params['exerciseId'] = (string) $exerciseId;
    }

    if (!empty($exeId)) {
        $params['exeId'] = (string) $exeId;
    }

    if (!empty($questionId)) {
        $params['questionId'] = (string) $questionId;
    }

    if (!empty($isReadOnly)) {
        $params['readOnly'] = (string) $isReadOnly;
    }

    if ($forceEdit) {
        $params['forceEdit'] = 'true';
    }

    return api_get_path(WEB_PLUGIN_PATH).'Onlyoffice/editor.php?'.http_build_query($params);
}

/**
 * Append version token to URL.
 */
function appendVersionTokenToUrl(string $url, string $versionToken): string
{
    if ('' === $versionToken) {
        return $url;
    }

    $separator = str_contains($url, '?') ? '&' : '?';

    return $url.$separator.'v='.rawurlencode($versionToken);
}

/**
 * Decide if the editor must open in read-only mode.
 */
function shouldOpenOnlyofficeInReadOnlyMode(string $extension, ?int $isReadOnly, bool $forceEdit, ?int $exeId): bool
{
    if ($forceEdit) {
        return false;
    }

    if (\in_array($extension, ['pdf'], true)) {
        return true;
    }

    if ($exeId) {
        return false;
    }

    if (!empty($isReadOnly)) {
        return true;
    }

    if (api_is_allowed_to_edit(false, true, true, false)) {
        return false;
    }

    return true;
}

/**
 * Normalize config to array.
 */
function onlyofficeEditorConfigToArray(mixed $config): array
{
    if (is_array($config)) {
        return $config;
    }

    $json = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (false === $json) {
        return [];
    }

    $array = json_decode($json, true);

    return is_array($array) ? $array : [];
}

/**
 * Refresh editor token after final config changes.
 */
function refreshOnlyofficeEditorToken(array $config, $jwtManager, $appSettings): array
{
    if (!is_object($jwtManager) || !method_exists($jwtManager, 'isJwtEnabled') || !$jwtManager->isJwtEnabled()) {
        unset($config['token']);

        return $config;
    }

    $payload = $config;
    unset($payload['token']);

    $token = '';

    try {
        $token = (string) $jwtManager->encode($payload, $appSettings->getJwtKey());
    } catch (\Throwable $e) {
        try {
            $token = (string) $jwtManager->encode($payload);
        } catch (\Throwable $inner) {
            onlyofficeEditorLog('WARNING', 'Failed to refresh editor JWT token', [
                'message' => $inner->getMessage(),
            ]);
        }
    }

    if ('' !== $token) {
        $config['token'] = $token;
    } else {
        unset($config['token']);
    }

    return $config;
}

/**
 * Send anti-cache headers for editor page.
 */
function sendOnlyofficeEditorNoCacheHeaders(): void
{
    @header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    @header('Pragma: no-cache');
    @header('Expires: 0');
    @header('X-Robots-Tag: noindex');
    @header('X-Content-Type-Options: nosniff');
}

/**
 * Structured logger helper.
 */
function onlyofficeEditorLog(string $level, string $message, array $context = []): void
{
    if (!ONLYOFFICE_EDITOR_LOG_ENABLED) {
        return;
    }

    $line = 'ONLYOFFICE EDITOR: '.$level.' - '.$message;

    if (!empty($context)) {
        $json = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (false !== $json) {
            $line .= ' | '.$json;
        }
    }

    error_log($line);
}
