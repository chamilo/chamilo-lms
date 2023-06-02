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

class LangManager {

    /**
     * Return lang info for current user
     */
    public static function getLangUser(): array
    {
        $langInfo = [];
        $userLang = api_get_language_from_type("user_profil_lang");
        if (empty($userLang)) {
            $userLang = api_get_language_from_type("course_lang");
        }
        if (empty($userLang)) {
            $langId = SubLanguageManager::get_platform_language_id();
            $langInfo = api_get_language_info($langId);
            return $langInfo;
        }

        $allLang = SubLanguageManager::getAllLanguages();
        foreach($allLang as $langItem) {
            if ($langItem["english_name"] === $userLang) {
                $langInfo = $langItem;
                break;
            }
        }

        return $langInfo;
    }
}
