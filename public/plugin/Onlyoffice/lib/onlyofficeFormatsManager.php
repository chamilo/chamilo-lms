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
use Onlyoffice\DocsIntegrationSdk\Manager\Formats\FormatsManager;
use Onlyoffice\DocsIntegrationSdk\Util\CommonError;

class OnlyofficeFormatsManager extends FormatsManager
{
    public function __construct()
    {
        $formats = self::getFormats();
        $this->formatsList = self::buildNamedFormatsArray($formats);
    }

    private static function getFormats()
    {
        $formats = file_get_contents(dirname(__DIR__).
        DIRECTORY_SEPARATOR.
        'vendor'.
        DIRECTORY_SEPARATOR.
        'onlyoffice'.
        DIRECTORY_SEPARATOR.
        'docs-integration-sdk'.
        DIRECTORY_SEPARATOR.
        'resources'.
        DIRECTORY_SEPARATOR.
        'assets'.
        DIRECTORY_SEPARATOR.
        'document-formats'.
        DIRECTORY_SEPARATOR.
        'onlyoffice-docs-formats.txt');

        if (empty($formats)) {
            $formats = file_get_contents(dirname(__DIR__).
            DIRECTORY_SEPARATOR.
            'vendor'.
            DIRECTORY_SEPARATOR.
            'onlyoffice'.
            DIRECTORY_SEPARATOR.
            'docs-integration-sdk'.
            DIRECTORY_SEPARATOR.
            'resources'.
            DIRECTORY_SEPARATOR.
            'assets'.
            DIRECTORY_SEPARATOR.
            'document-formats'.
            DIRECTORY_SEPARATOR.
            'onlyoffice-docs-formats.json');
        }

        if (!empty($formats)) {
            $formats = json_decode($formats);
            if (!empty($formats)) {
                return $formats;
            }
            throw new \Exception(CommonError::message(CommonError::EMPTY_FORMATS_ASSET));
        }
        throw new \Exception(CommonError::message(CommonError::EMPTY_FORMATS_ASSET));
    }
}
