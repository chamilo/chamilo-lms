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

class CoEditingMode extends BasicEnum
{
    const FAST = "fast";
    const STRICT = "strict";

    public function __construct($mode = null)
    {
        if (!self::isValidValue($mode) && $mode !== null) {
            throw new \Exception("Unknown co-editing mode");
        } else {
            $this->value = $mode !== null ? $mode : self::FAST;
        }
    }
}
