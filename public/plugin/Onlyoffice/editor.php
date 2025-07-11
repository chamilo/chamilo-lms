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

$isEnable = 'true' === $plugin->get('enable_onlyoffice_plugin');
if (!$isEnable) {
    exit("Document server isn't enabled");
}

$appSettings = new OnlyofficeAppsettings($plugin);
$documentServerUrl = $appSettings->getDocumentServerUrl();
if (empty($documentServerUrl)) {
    exit("Document server isn't configured");
}

$config = [];
$docApiUrl = $appSettings->getDocumentServerApiUrl();
$docId = isset($_GET['docId']) ? (int) $_GET['docId'] : null;
$docPath = isset($_GET['doc']) ? urldecode($_GET['doc']) : null;

$groupId = isset($_GET['groupId']) && !empty($_GET['groupId']) ? (int) $_GET['groupId'] : (!empty($_GET['gidReq']) ? (int) $_GET['gidReq'] : null);
$userId = api_get_user_id();
$userInfo = api_get_user_info($userId);
$sessionId = api_get_session_id();
$courseId = api_get_course_int_id();
$courseInfo = api_get_course_info();
if (empty($courseInfo)) {
    api_not_allowed(true);
}
$courseCode = $courseInfo['code'];
$exerciseId = isset($_GET['exerciseId']) ? (int) $_GET['exerciseId'] : null;
$exeId = isset($_GET['exeId']) ? (int) $_GET['exeId'] : null;
$questionId = isset($_GET['questionId']) ? (int) $_GET['questionId'] : null;
$isReadOnly = isset($_GET['readOnly']) ? (int) $_GET['readOnly'] : null;
$docInfo = null;
$fileId = null;
$fileUrl = null;

if ($docPath) {
    $filePath = api_get_path(SYS_COURSE_PATH).$docPath;
    if (!file_exists($filePath)) {
        error_log("ERROR: Original file not found -> ".$filePath);
        exit("Error: Document not found.");
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
                exit("Error: Failed to create a copy of the file.");
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
        'courseId' => api_get_course_int_id(),
        'userId' => api_get_user_id(),
        'docId' => $fileId,
        'sessionId' => api_get_session_id(),
    ];

    $jwtManager = new OnlyofficeJwtManager($appSettings);
    $hashUrl = $jwtManager->getHash($data);
    $callbackUrl = api_get_path(WEB_PLUGIN_PATH).'onlyoffice/callback.php?hash='.$hashUrl;
    if ($exeId) {
        $callbackUrl .= '&docPath='.urlencode($newDocPath);
    } else {
        $callbackUrl .= '&docPath='.urlencode($newDocPath);
    }

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
        'url' => api_get_path(WEB_PLUGIN_PATH)."onlyoffice/editor.php?doc=".urlencode($newDocPath).($exeId ? "&exeId={$exeId}" : "").($isReadOnly ? "&readOnly={$isReadOnly}" : ""),
        'document_url' => $callbackUrl,
        'absolute_path' => $absolutePath,
        'absolute_path_from_document' => '/document/'.basename($userFilePath),
        'absolute_parent_path' => $absoluteParentPath,
        'direct_url' => $callbackUrl,
        'basename' => basename($userFilePath),
        'parent_id' => false,
        'parents' => [],
        'forceEdit' => $_GET['forceEdit'] ?? false,
        'exercise_id' => $exerciseId,
    ];
} elseif ($docId) {
    $docInfo = DocumentManager::get_document_data_by_id($docId, $courseCode, false, $sessionId);
    if ($docInfo) {
        $fileId = $docId;
        $fileUrl = (new OnlyofficeDocumentManager($appSettings, $docInfo))->getFileUrl($docId);
    }
}

if (!$docInfo || !$fileId) {
    error_log("ERROR: Document not found.");
    exit("Error: Document not found.");
}

$langInfo = LangManager::getLangUser();
$jwtManager = new OnlyofficeJwtManager($appSettings);
if (isset($_GET['forceEdit']) && (bool) $_GET['forceEdit'] === true) {
    $docInfo['forceEdit'] = $_GET['forceEdit'];
}
$documentManager = new OnlyofficeDocumentManager($appSettings, $docInfo);
$extension = $documentManager->getExt($documentManager->getDocInfo('title'));
$docType = $documentManager->getDocType($extension);
$fileIdentifier = $docId ? (string) $docId : md5($docPath);
$key = $documentManager->getDocumentKey($fileIdentifier, $courseCode);
$fileUrl = $fileUrl ?? $documentManager->getFileUrl($fileIdentifier);

if (!empty($appSettings->getStorageUrl()) && !empty($fileUrl)) {
    $fileUrl = str_replace(api_get_path(WEB_PATH), $appSettings->getStorageUrl(), $fileUrl);
}

$configService = new OnlyofficeConfigService($appSettings, $jwtManager, $documentManager);
$editorsMode = $configService->getEditorsMode();
$config = $configService->createConfig($fileIdentifier, $editorsMode, $_SERVER['HTTP_USER_AGENT']);
$config = json_decode(json_encode($config), true);

if (empty($config)) {
    error_log("ERROR: Failed to generate the configuration for OnlyOffice");
    exit("Error: Failed to generate the configuration for OnlyOffice.");
}

$isMobileAgent = $configService->isMobileAgent($_SERVER['HTTP_USER_AGENT']);

$showHeaders = true;
$headerHeight = 'calc(100% - 140px)';
if (!empty($_GET['nh'])) {
    $showHeaders = false;
    $headerHeight = '100%';
}

?>
<title>ONLYOFFICE</title>
<style>
    #app > iframe {
        height: <?php echo $headerHeight; ?>;
    }
    body {
        height: 100%;
    }
    .chatboxheadmain,
    .pull-right,
    .breadcrumb {
        display: none;
    }
</style>
<script type="text/javascript" src="<?php echo $docApiUrl; ?>"></script>
<script type="text/javascript">
    var onAppReady = function () {
        innerAlert("Document editor ready");
    };

    var onRequestSaveAs = function (event) {
        var url = <?php echo json_encode(api_get_path(WEB_PLUGIN_PATH)); ?> + "onlyoffice/ajax/saveas.php";
        var folderId = <?php echo json_encode($docInfo['parent_id'] ?? 0); ?>;
        var saveData = {
            title: event.data.title,
            url: event.data.url,
            folderId: folderId,
            sessionId: <?php echo json_encode($sessionId); ?>,
            courseId: <?php echo json_encode($courseId); ?>,
            groupId: <?php echo json_encode($groupId); ?>
        };

        $.ajax(url, {
            method: "POST",
            data: JSON.stringify(saveData),
            processData: false,
            contentType: "application/json",
            dataType: "json",
            success: function (response) {
                if (response.error) {
                    console.error("Create error: ", response.error);
                }
            },
            error: function (e) {
                console.error("Create error: ", e);
            }
        });
    };

    var onRequestEditRights = function () {
        location.href += "&forceEdit=true";
    }

    var connectEditor = function () {
        var config = <?php echo json_encode($config); ?>;
        var errorPage = <?php echo json_encode(api_get_path(WEB_PLUGIN_PATH).'onlyoffice/error.php'); ?>;

        var docsVersion = DocsAPI.DocEditor.version().split(".");
        if ((config.document.fileType === "pdf")
            && docsVersion[0] < 8) {
            window.location.href = errorPage + "?status=" + 1;
            return;
        }
        if (docsVersion[0] < 6
            || docsVersion[0] == 6 && docsVersion[1] == 0) {
            window.location.href = errorPage + "?status=" + 2;
            return;
        }

        $("#cm-content")[0].remove(".container");
        $("#main").append('<div id="app-onlyoffice">' +
                            '<div id="app">' +
                                '<div id="iframeEditor">' +
                                '</div>' +
                            '</div>' +
                          '</div>');

        var isMobileAgent = <?php echo json_encode($isMobileAgent); ?>;

        config.events = {
            "onAppReady": onAppReady,
            "onRequestSaveAs": onRequestSaveAs,
            "onRequestEditRights": onRequestEditRights
        };

        docEditor = new DocsAPI.DocEditor("iframeEditor", config);

        $(".navbar").css({"margin-bottom": "0px"});
        $("body").css({"margin": "0 0 0px"});
        if (isMobileAgent) {
            var frameEditor = $("#app > iframe")[0];
            $(frameEditor).css({"height": "100%", "top": "0px"});
        }
    }

    if (window.addEventListener) {
        window.addEventListener("load", connectEditor);
    } else if (window.attachEvent) {
        window.attachEvent("load", connectEditor);
    }

</script>
<?php
if ($showHeaders) {
    echo Display::display_header();
} else {
    echo Display::display_reduced_header();
}
?>
