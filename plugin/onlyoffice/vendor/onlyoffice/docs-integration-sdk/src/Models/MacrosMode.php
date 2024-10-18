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

class MacrosMode extends BasicEnum
{
    const DISABLE = "disable";
    const ENABLE = "enable";
    const WARN = "warn";

    public function __construct($macrosMode = null)
    {
        if (!self::isValidValue($macrosMode) && $macrosMode !== null) {
            throw new \Exception("Unknown macros mode");
        } else {
            $this->value = $macrosMode !== null ? $macrosMode : self::WARN;
        }
    }
}
