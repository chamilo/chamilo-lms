<?php

namespace Onlyoffice\DocsIntegrationSdk\Manager\Formats;

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
use Onlyoffice\DocsIntegrationSdk\Util\CommonError;
use Onlyoffice\DocsIntegrationSdk\Models\Format;

/**
 * Document formats manager.
 *
 * @package Onlyoffice\DocsIntegrationSdk\Manager\Formats
 */

class FormatsManager implements FormatsManagerInterface
{

    /**
     * List of formats
     *
     * @var array
     */
    protected $formatsList;

    public function __construct($nameAssoc = false)
    {
        $formats = self::getFormats();
        if ($nameAssoc === false) {
            $this->formatsList = self::buildDefaultFormatsArray($formats);
        } else {
            $this->formatsList = self::buildNamedFormatsArray($formats);
        }
    }

    protected static function buildDefaultFormatsArray(array $formats)
    {
        $formatsList = [];
        foreach ($formats as $format) {
            array_push($formatsList, new Format(
                $format->name,
                $format->type,
                $format->actions,
                $format->convert,
                $format->mime
            ));
        }
        return $formatsList;
    }

    protected static function buildNamedFormatsArray(array $formats)
    {
        $formatsList = [];
        foreach ($formats as $format) {
            $currentFormat = new Format(
                $format->name,
                $format->type,
                $format->actions,
                $format->convert,
                $format->mime
            );
            $formatsList[$currentFormat->getName()] = $currentFormat;
        }
        return $formatsList;
    }

    private static function getFormats()
    {
        $formats = file_get_contents(dirname(dirname(dirname(__DIR__))).
        DIRECTORY_SEPARATOR.
        "resources".
        DIRECTORY_SEPARATOR.
        "assets".
        DIRECTORY_SEPARATOR.
        "document-formats".
        DIRECTORY_SEPARATOR.
        "onlyoffice-docs-formats.json");
        if (!empty($formats)) {
            $formats = json_decode($formats);
            if (!empty($formats)) {
                return $formats;
            }
            throw new \Exception(CommonError::message(CommonError::EMPTY_FORMATS_ASSET));
        }
        throw new \Exception(CommonError::message(CommonError::EMPTY_FORMATS_ASSET));
    }

    public function getFormatsList()
    {
        return $this->formatsList;
    }

    public function getViewableList()
    {
        $viewableList = [];
        foreach ($this->formatsList as $format) {
            if ($format->isViewable()) {
                array_push($viewableList, $format);
            }
        }
        return $viewableList;
    }

    public function getEditableList()
    {
        $editableList = [];
        foreach ($this->formatsList as $format) {
            if ($format->isEditable()) {
                array_push($editableList, $format);
            }
        }
        return $editableList;
    }

    public function getConvertableList()
    {
        $convertableList = [];
        foreach ($this->formatsList as $format) {
            if ($format->isAutoConvertable()) {
                array_push($convertableList, $format);
            }
        }
        return $convertableList;
    }

    public function getFillableList()
    {
        $fillableList = [];
        foreach ($this->formatsList as $format) {
            if ($format->isFillable()) {
                array_push($fillableList, $format);
            }
        }
        return $fillableList;
    }
}
