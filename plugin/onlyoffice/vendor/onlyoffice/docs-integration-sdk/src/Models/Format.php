<?php

namespace Onlyoffice\DocsIntegrationSdk\Models;

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

class Format extends JsonSerializable
{
    protected $name;
    protected $type;
    protected $actions;
    protected $convert;
    protected $mime;

    public function __construct(
        string $name,
        string $type = "",
        array $actions = [],
        array $convert = [],
        array $mime = []
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->actions = $actions;
        $this->convert = $convert;
        $this->mime = $mime;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
    }

    public function getActions()
    {
        return $this->actions;
    }

    public function setActions(array $actions)
    {
        $this->actions = $actions;
    }

    public function getConvert()
    {
        return $this->convert;
    }

    public function setConvert(array $convert)
    {
        $this->convert = $convert;
    }

    public function getMimes()
    {
        return $this->mime;
    }

    public function setMimes(array $mime)
    {
        $this->mime = $mime;
    }

    protected function hasAction(string $search)
    {
        return in_array($search, $this->actions);
    }

    public function isViewable()
    {
        return $this->hasAction("view");
    }

    public function isStrictlyEditable()
    {
        return $this->hasAction("edit");
    }

    public function isLossyEditable()
    {
        return $this->hasAction("lossy-edit");
    }

    public function isEditable()
    {
        return $this->hasAction("edit") || $this->hasAction("lossy-edit");
    }

    public function isAutoConvertable()
    {
        $this->hasAction("auto-convert");
    }

    public function isFillable()
    {
        return $this->hasAction("fill");
    }
}
