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

class ConvertResponseError
{
    const UNKNOWN = -1;
    const TIMEOUT = -2;
    const CONVERSION = -3;
    const DOWNLOADING = -4;
    const PASSWORD = -5;
    const DATABASE = -6;
    const INPUT = -7;
    const TOKEN = -8;

    public static function message($code): string
    {
        switch ($code) {
            case self::UNKNOWN:
                return "Unknown error";
            case self::TIMEOUT:
                return "Timeout conversion error";
            case self::CONVERSION:
                return "Conversion error";
            case self::DOWNLOADING:
                return "Error while downloading the document file to be converted";
            case self::PASSWORD:
                return "Incorrect password";
            case self::DATABASE:
                return "Error while accessing the conversion result database";
            case self::INPUT:
                return "Error document request";
            case self::TOKEN:
                return "Invalid token";
            default:
                return "Undefined error";
        }
    }
}
