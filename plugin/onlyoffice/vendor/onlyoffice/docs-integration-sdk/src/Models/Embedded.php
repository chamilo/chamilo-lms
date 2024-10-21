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
use Onlyoffice\DocsIntegrationSdk\Models\Toolbar;

class Embedded extends JsonSerializable
{
    protected $embedUrl;
    protected $fullscreenUrl;
    protected $saveUrl;
    protected $shareUrl;
    protected $toolbarDocked;

    public function __construct(
        string $embedUrl = "",
        string $fullscreenUrl = "",
        string $saveUrl = "",
        string $shareUrl = "",
        Toolbar $toolbarDocked = null
    ) {
        $this->embedUrl = $embedUrl;
        $this->fullscreenUrl = $fullscreenUrl;
        $this->saveUrl = $saveUrl;
        $this->shareUrl = $shareUrl;
        $this->toolbarDocked = $toolbarDocked !== null ? $toolbarDocked : new Toolbar;
    }


    /**
     * Get the value of embedUrl
     */
    public function getEmbedUrl()
    {
        return $this->embedUrl;
    }

    /**
     * Set the value of embedUrl
     */
    public function setEmbedUrl($embedUrl)
    {
        $this->embedUrl = $embedUrl;
    }

    /**
     * Get the value of fullscreenUrl
     */
    public function getFullscreenUrl()
    {
        return $this->fullscreenUrl;
    }

    /**
     * Set the value of fullscreenUrl
     */
    public function setFullscreenUrl($fullscreenUrl)
    {
        $this->fullscreenUrl = $fullscreenUrl;
    }

    /**
     * Get the value of saveUrl
     */
    public function getSaveUrl()
    {
        return $this->saveUrl;
    }

    /**
     * Set the value of saveUrl
     */
    public function setSaveUrl($saveUrl)
    {
        $this->saveUrl = $saveUrl;
    }

    /**
     * Get the value of toolbarDocked
     */
    public function getToolbarDocked()
    {
        return $this->toolbarDocked;
    }

    /**
     * Set the value of toolbarDocked
     */
    public function setToolbarDocked($toolbarDocked)
    {
        $this->toolbarDocked = $toolbarDocked;
    }
}
