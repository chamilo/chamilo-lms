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

const USER_AGENT_MOBILE = "/android|avantgo|playbook|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od|ad)|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\\/|plucker|pocket|psp|symbian|treo|up\\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i";

$plugin = OnlyofficePlugin::create();

$isEnable = $plugin->get("enable_onlyoffice_plugin") === 'true';
if (!$isEnable) {
    die ("Document server isn't enabled");
    return;
}

$documentServerUrl = $plugin->get("document_server_url");
if (empty($documentServerUrl)) {
    die ("Document server isn't configured");
    return;
}

$config = [];

$docApiUrl = $documentServerUrl . "/web-apps/apps/api/documents/api.js";

$docId = $_GET["docId"];
$groupId = isset($_GET["groupId"]) && !empty($_GET["groupId"]) ? $_GET["groupId"] : null;

$userId = api_get_user_id();

$userInfo = api_get_user_info($userId);

$sessionId = api_get_session_id();
$courseId = api_get_course_int_id();
$courseInfo = api_get_course_info();
$courseCode = $courseInfo["code"];

$docInfo = DocumentManager::get_document_data_by_id($docId, $courseCode, false, $sessionId);

$extension = strtolower(pathinfo($docInfo["title"], PATHINFO_EXTENSION));

$langInfo = LangManager::getLangUser();

$docType = FileUtility::getDocType($extension);
$key = FileUtility::getKey($courseCode, $docId);
$fileUrl = FileUtility::getFileUrl($courseId, $userId, $docId, $sessionId, $groupId);

$config = [
    "type" => "desktop",
    "documentType" => $docType,
    "document" => [
        "fileType" => $extension,
        "key" => $key,
        "title" => $docInfo["title"],
        "url" => $fileUrl
    ],
    "editorConfig" => [
        "lang" => $langInfo["isocode"],
        "region" => $langInfo["isocode"],
        "user" => [
            "id" => strval($userId),
            "name" => $userInfo["username"]
        ],
        "customization" => [
            "goback" => [
                "blank" => false,
                "requestClose" => false,
                "text" => get_lang("Back"),
                "url" => FileUtility::getUrlToLocation($courseCode, $sessionId, $groupId, $docInfo["parent_id"])
            ],
            "compactHeader" => true,
            "toolbarNoTabs" => true
        ]
    ]
];

$userAgent = $_SERVER["HTTP_USER_AGENT"];

$isMobileAgent = preg_match(USER_AGENT_MOBILE, $userAgent);
if ($isMobileAgent) {
    $config["type"] = "mobile";
}

$isAllowToEdit = api_is_allowed_to_edit(true, true);
$isMyDir = DocumentManager::is_my_shared_folder(
    $userId,
    $docInfo["absolute_parent_path"],
    $sessionId
);

$isGroupAccess = false;
if (!empty($groupId)) {
    $groupProperties = GroupManager::get_group_properties($groupId);
    $docInfoGroup = api_get_item_property_info(
        api_get_course_int_id(),
        "document",
        $docId,
        $sessionId
    );
    $isGroupAccess = GroupManager::allowUploadEditDocument(
        $userId,
        $courseCode,
        $groupProperties,
        $docInfoGroup
    );

    $isMemberGroup = GroupManager::is_user_in_group($userId, $groupProperties);

    if (!$isGroupAccess) {
        if (!$groupProperties["status"]) {
            api_not_allowed(true);
        }
        if (!$isMemberGroup && $groupProperties["doc_state"] != 1) {
            api_not_allowed(true);
        }
    }
}

$accessRights = $isAllowToEdit || $isMyDir || $isGroupAccess;
$canEdit = in_array($extension, FileUtility::$can_edit_types);

$isVisible = DocumentManager::check_visibility_tree($docId, $courseInfo, $sessionId, $userId, $groupId);
$isReadonly = $docInfo["readonly"];

if (!$isVisible) {
    api_not_allowed(true);
}

if ($canEdit && $accessRights && !$isReadonly) {
    $config["editorConfig"]["mode"] = "edit";
    $config["editorConfig"]["callbackUrl"] = getCallbackUrl(
        $docId,
        $userId,
        $courseId,
        $sessionId,
        $groupId
    );
} else {
    $canView = in_array($extension, FileUtility::$can_view_types);
    if ($canView) {
        $config["editorConfig"]["mode"] = "view";
    } else {
        api_not_allowed(true);
    }
}
$config["document"]["permissions"]["edit"] = $accessRights && !$isReadonly;

if (!empty($plugin->get("jwt_secret"))) {
    $token = \Firebase\JWT\JWT::encode($config, $plugin->get("jwt_secret"));
    $config["token"] = $token;
}

/**
 * Return callback url
 */
function getCallbackUrl(int $docId, int $userId, int $courseId, int $sessionId, int $groupId = null): string
{
    $url = "";

    $data = [
        "type" => "track",
        "courseId" => $courseId,
        "userId" => $userId,
        "docId" => $docId,
        "sessionId" => $sessionId
    ];

    if (!empty($groupId)) {
        $data["groupId"] = $groupId;
    }

    $hashUrl = Crypt::GetHash($data);

    return $url . api_get_path(WEB_PLUGIN_PATH) . "onlyoffice/callback.php?hash=" . $hashUrl;
}

?>
<title>ONLYOFFICE</title>
<style>
    #app > iframe {
        height: calc(100% - 140px);
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
<script type="text/javascript" src=<?php echo $docApiUrl?>></script>
<script type="text/javascript">
    var onAppReady = function () {
        innerAlert("Document editor ready");
    };

    var onRequestSaveAs = function (event) {
        var url = <?php echo json_encode(api_get_path(WEB_PLUGIN_PATH))?> + "onlyoffice/ajax/saveas.php";
        var folderId = <?php echo json_encode($docInfo["parent_id"])?>;
        var saveData = {
            title: event.data.title,
            url: event.data.url,
            folderId: folderId ? folderId : 0,
            sessionId: <?php echo json_encode($sessionId)?>,
            courseId: <?php echo json_encode($courseId)?>,
            groupId: <?php echo json_encode($groupId)?>
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

    var connectEditor = function () {
        var config = <?php echo json_encode($config)?>;

        if ((config.document.fileType === "docxf" || config.document.fileType === "oform")
            && DocsAPI.DocEditor.version().split(".")[0] < 7) {
            <?php
                echo Display::addFlash(
                        Display::return_message(
                            $plugin->get_lang("UpdateOnlyoffice"),
                            "error"
                        )
                    ); 
            ?>;
            return;
        }

        $("#cm-content")[0].remove(".container");
        $("#main").append('<div id="app-onlyoffice">' +
                            '<div id="app">' +
                                '<div id="iframeEditor">' +
                                '</div>' +
                            '</div>' +
                          '</div>');

        var isMobileAgent = <?php echo json_encode($isMobileAgent)?>;

        config.events = {
            "onAppReady": onAppReady,
            "onRequestSaveAs": onRequestSaveAs
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
<?php echo Display::display_header(); ?>
