<?php

namespace Onlyoffice\DocsIntegrationSdk\Util;

/**
 *
 * (c) Copyright Ascensio System SIA 2024
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
use Dotenv\Dotenv;

/**
 * ENV Utility.
 *
 * @package Onlyoffice\DocsIntegrationSdk\Util
 */

class EnvUtil
{
 
    private const ENV_SETTINGS_PREFIX = "DOCS_INTEGRATION_SDK";

    public function __construct()
    {
        static::loadEnvSettings();
    }

    public static function loadEnvSettings()
    {
        $dotenv = Dotenv::createImmutable(dirname(dirname(__DIR__)));
        $dotenv->safeLoad();
    }

    public static function envKey($key)
    {
        return mb_strtoupper(self::ENV_SETTINGS_PREFIX . "_" . $key);
    }
}
