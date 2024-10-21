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
use Onlyoffice\DocsIntegrationSdk\Models\SharingSettings;

class Info extends JsonSerializable
{
    protected $favorite;
    protected $folder;
    protected $owner;
    protected $sharingSettings; // array of SharingSettings
    protected $uploaded;

    public function __construct(
        bool $favorite = false,
        string $folder = "",
        string $owner = "",
        array $sharingSettings = [],
        string $uploaded = ""
    ) {
        $this->favorite = $favorite;
        $this->folder = $folder;
        $this->owner = $owner;
        $this->sharingSettings = $sharingSettings;
        $this->uploaded = $uploaded;
    }

    /**
     * Get the value of favorite
     */
    public function getFavorite()
    {
        return $this->favorite;
    }

    /**
     * Set the value of favorite
     */
    public function setFavorite($favorite)
    {
        $this->favorite = $favorite;
    }

    /**
     * Get the value of folder
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * Set the value of folder
     */
    public function setFolder($folder)
    {
        $this->folder = $folder;
    }

    /**
     * Get the value of owner
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set the value of owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * Get the value of sharingSettings
     */
    public function getSharingSettings()
    {
        return $this->sharingSettings;
    }

    /**
     * Set the value of sharingSettings
     */
    public function setSharingSettings($sharingSettings)
    {
        $this->sharingSettings = $sharingSettings;
    }

    /**
     * Get the value of uploaded
     */
    public function getUploaded()
    {
        return $this->uploaded;
    }

    /**
     * Set the value of uploaded
     */
    public function setUploaded($uploaded)
    {
        $this->uploaded = $uploaded;
    }
}
