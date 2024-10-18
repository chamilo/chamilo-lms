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
use Onlyoffice\DocsIntegrationSdk\Util\Changes;

class History extends JsonSerializable
{
    protected $serverVersion;
    protected $changes; // array of Changes

    public function __construct(string $serverVersion = "", array $changes = [])
    {
        $this->serverVersion = $serverVersion;
        $this->changes = $changes;
    }

    /**
     * Get the value of serverVersion
     */
    public function getServerVersion()
    {
        return $this->serverVersion;
    }

    /**
     * Set the value of serverVersion
     */
    public function setServerVersion($serverVersion)
    {
        $this->serverVersion = $serverVersion;
    }

    /**
     * Set the value of changes
     */
    public function setChanges($changes)
    {
        $this->changes = $changes;
    }
}
