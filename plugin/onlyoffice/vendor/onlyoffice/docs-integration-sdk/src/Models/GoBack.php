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

class GoBack extends JsonSerializable
{
    protected $blank;
    protected $requestClose;
    protected $text;
    protected $url;

    public function __construct(bool $blank = true, bool $requestClose = false, string $text = "", string $url = "")
    {
        $this->blank = $blank;
        $this->requestClose = $requestClose;
        $this->text = $text;
        $this->url = $url;
    }

    /**
     * Get the value of blank
     */
    public function getBlank()
    {
        return $this->blank;
    }

    /**
     * Set the value of blank
     */
    public function setBlank($blank)
    {
        $this->blank = $blank;
    }

    /**
     * Get the value of requestClose
     */
    public function getRequestClose()
    {
        return $this->requestClose;
    }

    /**
     * Set the value of requestClose
     */
    public function setRequestClose($requestClose)
    {
        $this->requestClose = $requestClose;
    }

    /**
     * Get the value of text
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set the value of text
     */
    public function setText($text)
    {
        $this->text = $text;
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
}
