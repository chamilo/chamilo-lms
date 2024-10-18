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
use Onlyoffice\DocsIntegrationSdk\Util\BasicEnum;

class EditorsMode extends BasicEnum
{
    const VIEW = "view";
    const EDIT = "edit";

    public function __construct($editorsMode = null)
    {
        if (!self::isValidValue($editorsMode) && $editorsMode !== null) {
            throw new \Exception("Unknown editors mode");
        } else {
            $this->value = $editorsMode !== null ? $editorsMode : self::EDIT;
        }
    }
}
