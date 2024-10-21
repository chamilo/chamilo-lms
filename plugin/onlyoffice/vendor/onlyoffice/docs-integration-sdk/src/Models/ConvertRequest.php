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
use Onlyoffice\DocsIntegrationSdk\Models\ConvertRequestThumbnail;

class ConvertRequest extends JsonSerializable
{
    protected $async;
    protected $codePage;
    protected $delimiter;
    protected $filetype;
    protected $key;
    protected $outputtype;
    protected $password;
    protected $region;
    protected $thumbnail;
    protected $title;
    protected $token;
    protected $url;

    public function __construct(
        bool $async = false,
        int $codepage = 65001,
        int $delimiter = 0,
        string $filetype = "",
        string $key = "",
        string $outputtype = "",
        string $password = "",
        string $region = "",
        ?ConvertRequestThumbnail $thumbnail = null,
        string $title = "",
        string $token = "",
        string $url = ""
    ) {
        $this->async = $async;
        $this->codePage = $codePage;
        $this->delimiter = $delimiter;
        $this->filetype = $filetype;
        $this->key = $key;
        $this->outputtype = $outputtype;
        $this->password = $password;
        $this->region = $region;
        $this->thumbnail = $thumbnail !== null ? $thumbnail : new ConvertRequestThumbnail;
        $this->title = $title;
        $this->token = $token;
        $this->url = $url;
    }

    /**
     * Get the value of async
     */
    public function getAsync()
    {
        return $this->async;
    }

    /**
     * Set the value of async
     */
    public function setAsync($async)
    {
        $this->async = $async;
    }

    /**
     * Get the value of codePage
     */
    public function getCodePage()
    {
        return $this->codePage;
    }

    /**
     * Set the value of codePage
     */
    public function setCodePage($codePage)
    {
        $this->codePage = $codePage;
    }

    /**
     * Get the value of delimiter
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * Set the value of delimiter
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
    }

    /**
     * Get the value of filetype
     */
    public function getFiletype()
    {
        return $this->filetype;
    }

    /**
     * Set the value of filetype
     */
    public function setFiletype($filetype)
    {
        $this->filetype = $filetype;
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
     * Get the value of outputtype
     */
    public function getOutputtype()
    {
        return $this->outputtype;
    }

    /**
     * Set the value of outputtype
     */
    public function setOutputtype($outputtype)
    {
        $this->outputtype = $outputtype;
    }

    /**
     * Get the value of password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set the value of password
     */
    public function setPassword($password)
    {
        $this->password = $password;
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
     */
    public function setRegion($region)
    {
        $this->region = $region;
    }

    /**
     * Get the value of thumbnail
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * Set the value of thumbnail
     */
    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;
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
     * Get the value of token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set the value of token
     */
    public function setToken($token)
    {
        $this->token = $token;
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
