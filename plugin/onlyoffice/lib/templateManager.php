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

class TemplateManager {

    /**
     * Mapping local path to templates
     *
     * @var Array
     */
    private static $localPath = [
        "bg" => "bg-BG",
        "cs" => "cs-CS",
        "de" => "de-DE",
        "el" => "el-GR",
        "en" => "en-US",
        "es" => "es-ES",
        "fr" => "fr-FR",
        "it" => "it-IT",
        "ja" => "ja-JP",
        "ko" => "ko-KR",
        "lv" => "lv-LV",
        "nl" => "nl-NL",
        "pl" => "pl-PL",
        "pt" => "pt-PT",
        "pt-BR" => "pt-BR",
        "ru" => "ru-RU",
        "sk" => "sk-SK",
        "sv" => "sv-SE",
        "uk" => "uk-UA",
        "vi" => "vi-VN",
        "zh" => "zh-CN"
    ];

    /**
     * Return path to template new file
     */
    public static function getEmptyTemplate($fileExtension): string
    {
        $langInfo = LangManager::getLangUser();
        $lang = $langInfo["isocode"];
        if (!array_key_exists($lang, self::$localPath)) {
            $lang = "en";
        }
        $templateFolder = api_get_path(SYS_PLUGIN_PATH) . "onlyoffice/assets/" . self::$localPath[$lang];

        return $templateFolder . "/" . ltrim($fileExtension, ".") . ".zip";
    }
}
