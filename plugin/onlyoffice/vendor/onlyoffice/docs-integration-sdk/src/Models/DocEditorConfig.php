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
use Onlyoffice\DocsIntegrationSdk\Models\CoEditing;
use Onlyoffice\DocsIntegrationSdk\Models\EditorsMode;
use Onlyoffice\DocsIntegrationSdk\Models\Recent;
use Onlyoffice\DocsIntegrationSdk\Models\Template;
use Onlyoffice\DocsIntegrationSdk\Models\User;
use Onlyoffice\DocsIntegrationSdk\Models\Customization;
use Onlyoffice\DocsIntegrationSdk\Models\Embedded;

class DocEditorConfig extends JsonSerializable
{
    protected $callbackUrl;
    protected $coEditing;
    protected $createUrl;
    protected $lang;
    protected $location;
    protected $mode;
    protected $recent; // array of Recent
    protected $region;
    protected $templates; // aray of Template
    protected $user;
    protected $customization;
    protected $embedded;

    public function __construct(
        ?string $callbackUrl = "",
        ?CoEditing $coEditing = null,
        ?string $createUrl = "",
        ?string $lang = "en",
        ?string $location = "",
        ?EditorsMode $mode = null,
        ?array $recent = null,
        ?string $region = "en-US",
        ?array $templates = null,
        ?User $user = null,
        ?Customization $customization = null,
        ?Embedded $embedded = null
    ) {
        $this->callbackUrl = $callbackUrl;
        $this->coEditing = $coEditing;
        $this->createUrl = $createUrl;
        $this->lang = $lang;
        $this->location = $location;
        $this->mode = $mode;
        $this->recent = $recent;
        $this->region = $region;
        $this->templates = $templates;
        $this->user = $user;
        $this->customization = $customization;
        $this->embedded = $embedded;
    }

    /**
     * Get the value of callbackUrl
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }

    /**
     * Set the value of callbackUrl
     *
     */
    public function setCallbackUrl($callbackUrl)
    {
        $this->callbackUrl = $callbackUrl;
    }

    /**
     * Get the value of coEditing
     */
    public function getCoEditing()
    {
        return $this->coEditing;
    }

    /**
     * Set the value of coEditing
     *
     */
    public function setCoEditing($coEditing)
    {
        $this->coEditing = $coEditing;
    }

    /**
     * Get the value of createUrl
     */
    public function getCreateUrl()
    {
        return $this->createUrl;
    }

    /**
     * Set the value of createUrl
     *
     */
    public function setCreateUrl($createUrl)
    {
        $this->createUrl = $createUrl;
    }

    /**
     * Get the value of lang
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Set the value of lang
     *
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    /**
     * Get the value of location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set the value of location
     *
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * Get the value of mode
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Set the value of mode
     *
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * Get the value of recent
     */
    public function getRecent()
    {
        return $this->recent;
    }

    /**
     * Set the value of recent
     *
     */
    public function setRecent($recent)
    {
        $this->recent = $recent;
    }

    /**
     * Get the value of region
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Set the value of region
     *
     */
    public function setRegion($region)
    {
        $this->region = $region;
    }

    /**
     * Get the value of templates
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * Set the value of templates
     *
     */
    public function setTemplates($templates)
    {
        $this->templates = $templates;
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
     *
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Get the value of customization
     */
    public function getCustomization()
    {
        return $this->customization;
    }

    /**
     * Set the value of customization
     *
     */
    public function setCustomization($customization)
    {
        $this->customization = $customization;
    }

    /**
     * Get the value of embedded
     */
    public function getEmbedded()
    {
        return $this->embedded;
    }

    /**
     * Set the value of embedded
     *
     */
    public function setEmbedded($embedded)
    {
        $this->embedded = $embedded;
    }
}
