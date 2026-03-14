<?php
/**
 * (c) Copyright Ascensio System SIA 2025.
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
require_once __DIR__.'/../../../main/inc/global.inc.php';

class LangManager
{
    /**
     * Return language info for the current user/session.
     */
    public static function getLangUser(): array
    {
        $locale = self::getCurrentLocaleFromSession();

        if (!empty($locale)) {
            $langInfo = self::findLanguageInfoByLocale($locale);
            if (!empty($langInfo)) {
                return $langInfo;
            }
        }

        $platformLangId = SubLanguageManager::get_platform_language_id();
        $platformLangInfo = api_get_language_info($platformLangId);

        return is_array($platformLangInfo) ? $platformLangInfo : [];
    }

    /**
     * Resolve the current locale from the Symfony/legacy session bridge.
     */
    private static function getCurrentLocaleFromSession(): ?string
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            return null;
        }

        $candidates = [
            $_SESSION['_locale_user'] ?? null,
            $_SESSION['_locale'] ?? null,
            $_SESSION['_locale_interface'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && '' !== trim($candidate)) {
                return trim($candidate);
            }
        }

        return null;
    }

    /**
     * Find a Chamilo language row matching a locale/code/name.
     */
    private static function findLanguageInfoByLocale(string $locale): array
    {
        $normalizedLocale = self::normalizeLocale($locale);
        $allLanguages = SubLanguageManager::getAllLanguages();

        if (!is_array($allLanguages)) {
            return [];
        }

        foreach ($allLanguages as $language) {
            if (!is_array($language)) {
                continue;
            }

            $candidates = [
                $language['english_name'] ?? null,
                $language['original_name'] ?? null,
                $language['isocode'] ?? null,
                $language['dokeos_folder'] ?? null,
                $language['folder'] ?? null,
            ];

            foreach ($candidates as $candidate) {
                if (!is_string($candidate) || '' === trim($candidate)) {
                    continue;
                }

                if (self::normalizeLocale($candidate) === $normalizedLocale) {
                    return $language;
                }
            }
        }

        return [];
    }

    /**
     * Normalize locale/name values for loose matching.
     */
    private static function normalizeLocale(string $value): string
    {
        $value = trim(strtolower($value));
        $value = str_replace('_', '-', $value);

        if (str_contains($value, '-')) {
            $parts = explode('-', $value);

            return $parts[0];
        }

        return $value;
    }
}
