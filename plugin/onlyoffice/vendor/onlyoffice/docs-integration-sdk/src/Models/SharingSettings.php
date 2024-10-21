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
use Onlyoffice\DocsIntegrationSdk\Models\SharingSettingsPermissions;

class SharingSettings extends JsonSerializable
{
    protected $isLink;
    protected $sharingSettingsPermissions;
    protected $user;

    public function __construct(
        bool $isLink = false,
        SharingSettingsPermissions $sharingSettingsPermissions = null,
        string $user = ""
    ) {
        $this->isLink = $isLink;
        $this->sharingSettingsPermissions =
        $sharingSettingsPermissions !== null ? $sharingSettingsPermissions : new SharingSettingsPermissions;
        $this->user = $user;
    }

    /**
     * Get the value of isLink
     */
    public function getIsLink()
    {
        return $this->isLink;
    }

    /**
     * Set the value of isLink
     */
    public function setIsLink($isLink)
    {
        $this->isLink = $isLink;
    }

    /**
     * Get the value of sharingSettingsPermissions
     */
    public function getSharingSettingsPermissions()
    {
        return $this->sharingSettingsPermissions;
    }

    /**
     * Set the value of sharingSettingsPermissions
     */
    public function setSharingSettingsPermissions($sharingSettingsPermissions)
    {
        $this->sharingSettingsPermissions = $sharingSettingsPermissions;
    }

    /**
     * Get the value of user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the value of user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }
}
