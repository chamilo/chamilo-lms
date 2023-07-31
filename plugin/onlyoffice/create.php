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

$plugin = OnlyofficePlugin::create();

$mapFileFormat = [
    "text" => $plugin->get_lang("document"),
    "spreadsheet" => $plugin->get_lang("spreadsheet"),
    "presentation" => $plugin->get_lang("presentation"),
    "formTemplate" => $plugin->get_lang("formTemplate")
];

$userId = !empty($_GET["userId"]) ? $_GET['userId'] : 0;
$sessionId = !empty($_GET["sessionId"]) ? $_GET["sessionId"] : 0;
$courseId = !empty($_GET["courseId"]) ? $_GET["courseId"] : 0;
$groupId = !empty($_GET["groupId"]) ? $_GET["groupId"] : 0;
$folderId = !empty($_GET["folderId"]) ? $_GET["folderId"] : 0;

$courseInfo = api_get_course_info_by_id($courseId);
$courseCode = $courseInfo["code"];

$isMyDir = false;
if (!empty($folderId)) {
    $folderInfo = DocumentManager::get_document_data_by_id(
        $folderId,
        $courseCode,
        true,
        $sessionId
    );
    $isMyDir = DocumentManager::is_my_shared_folder(
        $userId,
        $folderInfo["absolute_path"],
        $sessionId
    );
}
$groupRights = Session::read('group_member_with_upload_rights');
$isAllowToEdit = api_is_allowed_to_edit(true, true);
if (!($isAllowToEdit || $isMyDir || $groupRights)) {
    api_not_allowed(true);
}

$form = new FormValidator(
    "doc_create",
    "post",
    api_get_path(WEB_PLUGIN_PATH) . "onlyoffice/create.php?userId=" . Security::remove_XSS($userId)
                                                        . "&groupId=" . Security::remove_XSS($groupId)
                                                        . "&courseId=" . Security::remove_XSS($courseId)
                                                        . "&sessionId=" . Security::remove_XSS($sessionId)
                                                        . "&folderId=" . Security::remove_XSS($folderId)
);

$form->addText("fileName", $plugin->get_lang("title"), true);
$form->addSelect("fileFormat", $plugin->get_lang("chooseFileFormat"), $mapFileFormat);
$form->addButtonCreate($plugin->get_lang("create"));

if ($form->validate()) {
    $values = $form->exportValues();

    $fileType = $values["fileFormat"];
    $fileExt = FileUtility::getDocExt($fileType);

    $result = FileUtility::createFile(
        $values["fileName"],
        $fileExt,
        $folderId,
        $userId,
        $sessionId,
        $courseId,
        $groupId
    );

    if (isset($result["error"])) {
        Display::addFlash(
            Display::return_message(
                $plugin->get_lang($result["error"]),
                "error"
            )
        );
    } else {
        header("Location: " . FileUtility::getUrlToLocation($courseCode, $sessionId, $groupId, $folderId));
        exit();
    }
}

    $goBackUrl = FileUtility::getUrlToLocation($courseCode, $sessionId, $groupId, $folderId);
    $actionsLeft = '<a href="'. $goBackUrl . '">' . Display::return_icon("back.png", get_lang("Back") . " " . get_lang("To") . " " . get_lang("DocumentsOverview"), "", ICON_SIZE_MEDIUM) . "</a>";

    Display::display_header($plugin->get_lang("createNewDocument"));
    echo Display::toolbarAction("actions-documents", [$actionsLeft]);
    echo $form->returnForm();
    Display::display_footer();
