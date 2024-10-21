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

use Onlyoffice\DocsIntegrationSdk\Models\DocumentType;
use Onlyoffice\DocsIntegrationSdk\Models\ReferenceData;
use Onlyoffice\DocsIntegrationSdk\Models\Info;
use Onlyoffice\DocsIntegrationSdk\Models\Permissions;

class Document extends JsonSerializable
{

    protected $fileType;
    protected $key;
    protected $referenceData;
    protected $title;
    protected $url;
    protected $info;
    protected $permissions;

    public function __construct(
        ?string $fileType,
        ?string $key,
        ?ReferenceData $referenceData,
        ?string $title,
        ?string $url,
        ?Info $info,
        ?Permissions $permissions
    ) {
        $this->fileType = $fileType;
        $this->key = $key;
        $this->referenceData = $referenceData;
        $this->title = $title;
        $this->url = $url;
        $this->info = $info;
        $this->permissions = $permissions;
    }

    /**
     * Get the value of fileType
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     * Set the value of fileType
     */
    public function setFileType($fileType)
    {
        $this->fileType = $fileType;
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

    /**
     * Get the value of referenceData
     */
    public function getReferenceData()
    {
        return $this->referenceData;
    }

    /**
     * Set the value of referenceData

     */
    public function setReferenceData($referenceData)
    {
        $this->referenceData = $referenceData;
    }

    /**
     * Get the value of title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the value of title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get the value of url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the value of url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Get the value of info
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Set the value of info
     */
    public function setInfo($info)
    {
        $this->info = $info;
    }

    /**
     * Get the value of permissions
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Set the value of permissions
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;
    }
}
