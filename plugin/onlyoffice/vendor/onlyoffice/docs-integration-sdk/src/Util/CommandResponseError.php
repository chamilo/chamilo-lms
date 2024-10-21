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


/**
 * Error messages.
 *
 * @package Onlyoffice\DocsIntegrationSdk\Util
 */

class CommandResponseError
{
    const NO = 0;
    const KEY = 1;
    const CALLBACK_URL = 2;
    const INTERNAL_SERVER = 3;
    const FORCE_SAVE = 4;
    const COMMAND = 5;
    const TOKEN = 6;

    public static function message($code): string
    {
        switch ($code) {
            case self::NO:
                return "No errors";
            case self::KEY:
                return "Document key is missing or no document with such key could be found";
            case self::CALLBACK_URL:
                return "Callback url not correct";
            case self::FORCE_SAVE:
                return "No changes were applied to the document before the forcesave command was received";
            case self::INTERNAL_SERVER:
                return "Internal server error";
            case self::COMMAND:
                return "Command not correct";
            case self::TOKEN:
                return "Invalid token";
            default:
                return "Unknown error";
        }
    }
}
