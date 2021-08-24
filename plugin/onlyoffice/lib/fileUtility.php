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
require_once __DIR__."/../../../main/inc/global.inc.php";

class FileUtility
{
    /**
     * Application name.
     */
    public const app_name = "onlyoffice";

    /**
     * Extensions of files that can edit.
     *
     * @var array
     */
    public static $can_edit_types = [
        "docx",
        "xlsx",
        "pptx",
        "ppsx",
    ];

    /**
     * Extensions of files that can view.
     *
     * @var array
     */
    public static $can_view_types = [
        "docx", "xlsx", "pptx", "ppsx",
        "txt", "csv", "odt", "ods", "odp",
        "doc", "xls", "ppt", "pps", "epub",
        "rtf", "mht", "html", "htm", "xps", "pdf", "djvu",
    ];

    /**
     * Extensions of text files.
     *
     * @var array
     */
    public static $text_doc = [
        "docx", "txt", "odt", "doc", "rtf", "html",
        "htm", "xps", "pdf", "djvu",
    ];

    /**
     * Extensions of presentation files.
     *
     * @var array
     */
    public static $presentation_doc = [
        "pptx", "ppsx", "odp", "ppt", "pps",
    ];

    /**
     * Extensions of spreadsheet files.
     *
     * @var array
     */
    public static $spreadsheet_doc = [
        "xlsx", "csv", "ods", "xls",
    ];

    /**
     * Return file type by extension.
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
     * Return file extension by file type.
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

        return "";
    }

    /**
     * Return file url for download.
     */
    public static function getFileUrl(int $courseId, int $userId, int $docId, int $sessionId = null, int $groupId = null): string
    {
        $data = [
            "type" => "download",
            "courseId" => $courseId,
            "userId" => $userId,
            "docId" => $docId,
            "sessionId" => $sessionId,
        ];

        if (!empty($groupId)) {
            $data["groupId"] = $groupId;
        }

        $hashUrl = Crypt::GetHash($data);

        return api_get_path(WEB_PLUGIN_PATH).self::app_name."/"."callback.php?hash=".$hashUrl;
    }

    /**
     * Return file key.
     */
    public static function getKey(string $courseCode, int $docId): string
    {
        $docInfo = DocumentManager::get_document_data_by_id($docId, $courseCode);
        $mtime = filemtime($docInfo["absolute_path"]);

        $key = $mtime.$courseCode.$docId;

        return self::GenerateRevisionId($key);
    }

    /**
     * Translation key to a supported form.
     */
    public static function GenerateRevisionId(string $expectedKey): string
    {
        if (strlen($expectedKey) > 20) {
            $expectedKey = crc32($expectedKey);
        }
        $key = preg_replace("[^0-9-.a-zA-Z_=]", "_", $expectedKey);
        $key = substr($key, 0, min([strlen($key), 20]));

        return $key;
    }
}
