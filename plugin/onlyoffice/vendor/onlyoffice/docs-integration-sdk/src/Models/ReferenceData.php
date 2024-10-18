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

class ReferenceData extends JsonSerializable
{
    protected $fileKey;
    protected $instanceId;
    protected $key;

    public function __construct(?string $fileKey = "", ?string $instanceId = "", ?string $key = "")
    {
        $this->fileKey = $fileKey;
        $this->instanceId = $instanceId;
        $this->key = $key;
    }

    /**
     * Get the value of fileKey
     */
    public function getFileKey()
    {
        return $this->fileKey;
    }

    /**
     * Set the value of fileKey
     */
    public function setFileKey($fileKey)
    {
        $this->fileKey = $fileKey;
    }

    /**
     * Get the value of instanceId
     */
    public function getInstanceId()
    {
        return $this->instanceId;
    }

    /**
     * Set the value of instanceId
     */
    public function setInstanceId($instanceId)
    {
        $this->instanceId = $instanceId;
    }

    /**
     * Get the value of key
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the value of key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }
}
