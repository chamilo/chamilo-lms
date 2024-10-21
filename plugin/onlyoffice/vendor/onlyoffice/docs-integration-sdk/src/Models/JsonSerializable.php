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

abstract class JsonSerializable implements \JsonSerializable
{
    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        foreach ($vars as $key => $var) {
            if (empty($var) && !is_bool($var)) {
                unset($vars[$key]);
            } else {
                if (is_object($var)) {
                    if (property_exists($var, "value")) {
                        if (empty($var->value)) {
                            unset($vars[$key]);
                        } else {
                            $vars[$key] = $var->value;
                        }
                    }
                }
            }
        }
        return $vars;
    }

    public function mapFromArray(array $values)
    {
        foreach ($values as $key => $value) {
            try {
                $mapperFunction = "set" . lcfirst($key);
                $this->{$mapperFunction}($value);
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}
