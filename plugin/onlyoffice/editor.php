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

$plugin = OnlyofficePlugin::create();

$isEnable = 'true' === $plugin->get('enable_onlyoffice_plugin');
if (!$isEnable) {
    exit("Document server isn't enabled");

    return;
}

$appSettings = new OnlyofficeAppsettings($plugin);
$documentServerUrl = $appSettings->getDocumentServerUrl();
if (empty($documentServerUrl)) {
    exit("Document server isn't configured");

    return;
}

$config = [];
$docApiUrl = $appSettings->getDocumentServerApiUrl();
$docId = $_GET['docId'];
$groupId = isset($_GET['groupId']) && !empty($_GET['groupId']) ? $_GET['groupId'] : null;
$userId = api_get_user_id();
$userInfo = api_get_user_info($userId);
$sessionId = api_get_session_id();
$courseId = api_get_course_int_id();
$courseInfo = api_get_course_info();
if (empty($courseInfo)) {
    api_not_allowed(true);
}
$courseCode = $courseInfo['code'];
$docInfo = DocumentManager::get_document_data_by_id($docId, $courseCode, false, $sessionId);
$langInfo = LangManager::getLangUser();
$jwtManager = new OnlyofficeJwtManager($appSettings);
if (isset($_GET['forceEdit']) && (bool) $_GET['forceEdit'] === true) {
    $docInfo['forceEdit'] = $_GET['forceEdit'];
}
$documentManager = new OnlyofficeDocumentManager($appSettings, $docInfo);
$extension = $documentManager->getExt($documentManager->getDocInfo('title'));
$docType = $documentManager->getDocType($extension);
$key = $documentManager->getDocumentKey($docId, $courseCode);
$fileUrl = $documentManager->getFileUrl($docId);

if (!empty($appSettings->getStorageUrl())) {
    $fileUrl = str_replace(api_get_path(WEB_PATH), $appSettings->getStorageUrl(), $fileUrl);
}

$configService = new OnlyofficeConfigService($appSettings, $jwtManager, $documentManager);
$editorsMode = $configService->getEditorsMode();
$config = $configService->createConfig($docId, $editorsMode, $_SERVER['HTTP_USER_AGENT']);
$config = json_decode(json_encode($config), true);
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
        var folderId = <?php echo json_encode($docInfo['parent_id']); ?>;
        var saveData = {
            title: event.data.title,
            url: event.data.url,
            folderId: folderId ? folderId : 0,
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
