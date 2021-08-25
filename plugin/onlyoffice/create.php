<?php
/**
 * (c) Copyright Ascensio System SIA 2021.
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

$plugin = OnlyofficePlugin::create();

$mapFileFormat = [
    "text" => $plugin->get_lang("document"),
    "spreadsheet" => $plugin->get_lang("spreadsheet"),
    "presentation" => $plugin->get_lang("presentation"),
];

$userId = $_GET["userId"];
$sessionId = $_GET["sessionId"];
$docId = $_GET["folderId"];
$courseId = $_GET["courseId"];

$courseInfo = api_get_course_info_by_id($courseId);
$courseCode = $courseInfo["code"];

$docInfo = DocumentManager::get_document_data_by_id($docId, $courseCode, true, $sessionId);

$groupRights = Session::read('group_member_with_upload_rights');
$isAllowToEdit = api_is_allowed_to_edit(true, true);
$isMyDir = DocumentManager::is_my_shared_folder($userId, $docInfo["absolute_path"], $sessionId);
if (!($isAllowToEdit || $isMyDir || $groupRights)) {
    api_not_allowed(true);
}

$form = new FormValidator("doc_create",
                          "post",
                          api_get_path(WEB_PLUGIN_PATH)."onlyoffice/create.php");

$form->addText("fileName", $plugin->get_lang("title"), true);
$form->addSelect("fileFormat", $plugin->get_lang("chooseFileFormat"), $mapFileFormat);
$form->addButtonCreate($plugin->get_lang("create"));

$form->addHidden("groupId", (int) $_GET["groupId"]);
$form->addHidden("courseId", (int) $_GET["courseId"]);
$form->addHidden("sessionId", (int) $_GET["sessionId"]);
$form->addHidden("userId", (int) $_GET["userId"]);
$form->addHidden("folderId", (int) $_GET["folderId"]);
$form->addHidden("goBackUrl", $_SERVER["HTTP_REFERER"]);

if ($form->validate()) {
    $values = $form->exportValues();

    $folderId = $values["folderId"];
    $userId = $values["userId"];
    $groupId = $values["groupId"];
    $sessionId = $values["sessionId"];
    $courseId = $values["courseId"];
    $goBackUrl = $values["goBackUrl"];

    $fileType = $values["fileFormat"];
    $fileExt = FileUtility::getDocExt($fileType);
    $fileTitle = $values["fileName"].".".$fileExt;

    $courseInfo = api_get_course_info_by_id($courseId);
    $courseCode = $courseInfo["code"];

    $fileNamePrefix = DocumentManager::getDocumentSuffix($courseInfo, $sessionId, $groupId);
    $fileName = $values["fileName"].$fileNamePrefix.".".$fileExt;

    $groupInfo = GroupManager::get_group_properties($groupId);

    $emptyTemplatePath = TemplateManager::getEmptyTemplate($fileExt);

    $fileRelatedPath = "/";
    if (!empty($folderId)) {
        $document_data = DocumentManager::get_document_data_by_id($folderId, $courseCode, true, $sessionId);
        $folderPath = $document_data["absolute_path"];
        $fileRelatedPath = $fileRelatedPath.substr($document_data["absolute_path_from_document"], 10)."/".$fileName;
    } else {
        $folderPath = api_get_path(SYS_COURSE_PATH).api_get_course_path($courseCode)."/document";
        if (!empty($groupId)) {
            $folderPath = $folderPath."/".$groupInfo["directory"];
            $fileRelatedPath = $groupInfo["directory"]."/";
        }
        $fileRelatedPath = $fileRelatedPath.$fileName;
    }
    $filePath = $folderPath."/".$fileName;

    if (file_exists($filePath)) {
        Display::addFlash(Display::return_message($plugin->get_lang("fileIsExist"), "error"));
        goto display;
    }

    if ($fp = @fopen($filePath, "w")) {
        $content = file_get_contents($emptyTemplatePath);
        fputs($fp, $content);
        fclose($fp);

        chmod($filePath, api_get_permissions_for_new_files());

        $documentId = add_document($courseInfo,
                                    $fileRelatedPath,
                                    "file",
                                    filesize($filePath),
                                    $fileTitle,
                                    null,
                                    false);
        if ($documentId) {
            api_item_property_update($courseInfo,
                                        TOOL_DOCUMENT,
                                        $documentId,
                                        "DocumentAdded",
                                        $userId,
                                        $groupInfo,
                                        null,
                                        null,
                                        null,
                                        $sessionId);

            header("Location: ".$goBackUrl);
            exit();
        }
    } else {
        Display::addFlash(Display::return_message($plugin->get_lang("impossibleCreateFile"), "error"));
    }
}

display:
    $goBackUrl = $goBackUrl ?: $_SERVER["HTTP_REFERER"];
    $actionsLeft = '<a href="'.$goBackUrl.'">'.Display::return_icon("back.png", get_lang("Back")." ".get_lang("To")." ".get_lang("DocumentsOverview"), "", ICON_SIZE_MEDIUM)."</a>";

    Display::display_header($plugin->get_lang("createNewDocument"));
    echo Display::toolbarAction("actions-documents", [$actionsLeft]);
    echo $form->returnForm();
    Display::display_footer();
