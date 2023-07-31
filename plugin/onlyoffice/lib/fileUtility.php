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

require_once __DIR__ . "/../../../main/inc/global.inc.php";

class FileUtility {

    /**
     * Application name
     */
    public const app_name = "onlyoffice";

    /**
     * Extensions of files that can edit
     *
     * @var array
     */
    public static $can_edit_types = [
        "docx",
        "docxf",
        "oform",
        "xlsx",
        "pptx",
        "ppsx"
    ];

    /**
     * Extensions of files that can view
     *
     * @var array
     */
    public static $can_view_types = [
        "docx", "docxf", "oform", "xlsx", "pptx", "ppsx",
        "txt", "csv", "odt", "ods","odp",
        "doc", "xls", "ppt", "pps","epub",
        "rtf", "mht", "html", "htm","xps","pdf","djvu"
    ];

    /**
     * Extensions of text files
     *
     * @var array
     */
    public static $text_doc = [
        "docx", "docxf", "oform", "txt", "odt", "doc", "rtf", "html",
        "htm", "xps", "pdf", "djvu"
    ];

    /**
     * Extensions of presentation files
     *
     * @var array
     */
    public static $presentation_doc  = [
        "pptx", "ppsx", "odp", "ppt", "pps"
    ];

    /**
     * Extensions of spreadsheet files
     *
     * @var array
     */
    public static $spreadsheet_doc = [
        "xlsx", "csv", "ods", "xls"
    ];

    /**
     * Return file type by extension
     */
    public static function getDocType(string $extension): string
    {
        if (in_array($extension, self::$text_doc)) {
            return "text";
        }
        if (in_array($extension, self::$presentation_doc)) {
            return "presentation";
        }
        if (in_array($extension, self::$spreadsheet_doc)) {
            return "spreadsheet";
        }

        return "";
    }

    /**
     * Return file extension by file type
     */
    public static function getDocExt(string $type): string
    {
        if ($type === "text") {
            return "docx";
        }
        if ($type === "spreadsheet") {
            return "xlsx";
        }
        if ($type === "presentation") {
            return "pptx";
        }
        if ($type === "formTemplate") {
            return "docxf";
        }

        return "";
    }

    /**
     * Return file url for download
     */
    public static function getFileUrl(int $courseId, int $userId, int $docId, int $sessionId = null, int $groupId = null): string
    {

        $data = [
            "type" => "download",
            "courseId" => $courseId,
            "userId" => $userId,
            "docId" => $docId,
            "sessionId" => $sessionId
        ];

        if (!empty($groupId)) {
            $data["groupId"] = $groupId;
        }

        $hashUrl = Crypt::GetHash($data);

        return api_get_path(WEB_PLUGIN_PATH) . self::app_name . "/" . "callback.php?hash=" . $hashUrl;
    }

    /**
     * Return location file in chamilo documents
     */
    function getUrlToLocation($courseCode, $sessionId, $groupId, $folderId) {
        return api_get_path(WEB_CODE_PATH)."document/document.php"
                                            . "?cidReq=" . Security::remove_XSS($courseCode)
                                            . "&id_session=" . Security::remove_XSS($sessionId)
                                            . "&gidReq=" . Security::remove_XSS($groupId)
                                            . "&id=" . Security::remove_XSS($folderId);
    }

    /**
     * Return file key
     */
    public static function getKey(string $courseCode, int $docId): string
    {
        $docInfo = DocumentManager::get_document_data_by_id($docId, $courseCode);
        $mtime = filemtime($docInfo["absolute_path"]);

        $key = $mtime . $courseCode . $docId;
        return self::GenerateRevisionId($key);
    }

    /**
     * Translation key to a supported form
     */
    public static function GenerateRevisionId(string $expectedKey): string
    {
        if (strlen($expectedKey) > 20) $expectedKey = crc32( $expectedKey);
        $key = preg_replace("[^0-9-.a-zA-Z_=]", "_", $expectedKey);
        $key = substr($key, 0, min(array(strlen($key), 20)));
        return $key;
    }

    /**
     * Create new file
     */
    public static function createFile(
        string $basename,
        string $fileExt,
        int $folderId,
        int $userId,
        int $sessionId,
        int $courseId,
        int $groupId,
        string $templatePath = ""): array
    {
        $courseInfo = api_get_course_info_by_id($courseId);
        $courseCode = $courseInfo["code"];
        $groupInfo = GroupManager::get_group_properties($groupId);

        $fileTitle = Security::remove_XSS($basename). "." .$fileExt;

        $fileNamePrefix = DocumentManager::getDocumentSuffix($courseInfo, $sessionId, $groupId);
        $fileName = preg_replace('/\.\./', '', $basename) . $fileNamePrefix . "." . $fileExt;

        if (empty($templatePath)) {
            $templatePath = TemplateManager::getEmptyTemplate($fileExt);
        }

        $folderPath = '';
        $fileRelatedPath = "/";
        if (!empty($folderId)) {
            $document_data = DocumentManager::get_document_data_by_id(
                $folderId,
                $courseCode,
                true,
                $sessionId
            );
            $folderPath = $document_data["absolute_path"];
            $fileRelatedPath = $fileRelatedPath . substr($document_data["absolute_path_from_document"], 10) . "/" . $fileName;
        } else {
            $folderPath = api_get_path(SYS_COURSE_PATH) . api_get_course_path($courseCode) . "/document";
            if (!empty($groupId)) {
                $folderPath = $folderPath . "/" . $groupInfo["directory"];
                $fileRelatedPath = $groupInfo["directory"] . "/";
            }
            $fileRelatedPath = $fileRelatedPath . $fileName;
        }
        $filePath = $folderPath . "/" . $fileName;

        if (file_exists($filePath)) {
            return ["error" => "fileIsExist"];
        }
    
        if ($fp = @fopen($filePath, "w")) {
            $content = file_get_contents($templatePath);
            fputs($fp, $content);
            fclose($fp);
    
            chmod($filePath, api_get_permissions_for_new_files());
    
            $documentId = add_document(
                $courseInfo,
                $fileRelatedPath,
                "file",
                filesize($filePath),
                $fileTitle,
                null,
                false
            );
            if ($documentId) {
                api_item_property_update(
                    $courseInfo,
                   TOOL_DOCUMENT,
                    $documentId,
                    "DocumentAdded",
                    $userId,
                    $groupInfo,
                    null,
                    null,
                    null,
                    $sessionId
                );
            } else {
                return ["error" => "impossibleCreateFile"];
            }
        }

        return ["documentId" => $documentId];
    }
}
