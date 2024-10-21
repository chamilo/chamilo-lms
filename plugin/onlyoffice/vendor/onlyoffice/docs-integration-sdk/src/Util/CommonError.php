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

class CommonError
{
    const NO_HEALTHCHECK_ENDPOINT = 1;
    const NO_DOCUMENT_SERVER_URL = 2;
    const NO_CONVERT_SERVICE_ENDPOINT = 3;
    const NO_JWT_HEADER = 4;
    const NO_JWT_PREFIX = 5;
    const READ_XML = 6;
    const BAD_RESPONSE_XML = 7;
    const NO_COMMAND_ENDPOINT = 8;
    const MIXED_CONTENT = 9;
    const BAD_HEALTHCHECK_STATUS = 10;
    const DOC_SERVICE_ERROR = 11;
    const NOT_SUPPORTED_VERSION = 12;
    const EMPTY_FORMATS_ASSET = 13;
    const FORMATS_ASSET_JSON_ERROR = 14;
    const UNKNOWN_EXT = 15;
    const FILE_TEMPLATE_IS_NOT_EXISTS = 16;
    const NO_API_URL = 17;
    const CALLBACK_NO_AUTH_TOKEN = 18;
    const CALLBACK_NO_STATUS = 18;

    public static function message($code): string
    {
        switch ($code) {
            case self::NO_HEALTHCHECK_ENDPOINT:
                return "There is no healthcheck endpoint in the application configuration";
            case self::NO_DOCUMENT_SERVER_URL:
                return "There is no document server URL in the application configuration";
            case self::NO_CONVERT_SERVICE_ENDPOINT:
                return "There is no convert service endpoint in the application configuration";
            case self::NO_JWT_HEADER:
                return "There is no JWT header in the application configuration";
            case self::NO_JWT_PREFIX:
                return "There is no JWT prefix in the application configuration";
            case self::READ_XML:
                return "Can't read XML";
            case self::BAD_RESPONSE_XML:
                return "Bad response";
            case self::NO_COMMAND_ENDPOINT:
                return "There is no command endpoint in the application configuration";
            case self::MIXED_CONTENT:
                return "Mixed Active Content is not allowed. HTTPS address for ONLYOFFICE Docs is required";
            case self::BAD_HEALTHCHECK_STATUS:
                return "Bad healthcheck status";
            case self::DOC_SERVICE_ERROR:
                return "Error occurred in the document service";
            case self::NOT_SUPPORTED_VERSION:
                return "Not supported version";
            case self::EMPTY_FORMATS_ASSET:
                return "Formats submodule error";
            case self::FORMATS_ASSET_JSON_ERROR:
                return "Formats submodule JSON error";
            case self::UNKNOWN_EXT:
                return "Unknown file extension";
            case self::FILE_TEMPLATE_IS_NOT_EXISTS:
                return "File template is not exists";
            case self::NO_API_URL:
                return "There is no document server API URL in the application configuration";
            case self::CALLBACK_NO_AUTH_TOKEN:
                return "Not found authorization token";
            case self::CALLBACK_NO_STATUS:
                return "Callback has no status";
            default:
                return "Unknown error";
        }
    }
}
