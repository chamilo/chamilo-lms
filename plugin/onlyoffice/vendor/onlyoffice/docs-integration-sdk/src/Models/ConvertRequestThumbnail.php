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

class ConvertRequestThumbnail extends JsonSerializable
{

    protected $aspect;
    protected $first;
    protected $height;
    protected $width;

    public function __construct(int $aspect = 2, bool $first = true, int $height = 100, int $width = 100)
    {
        $this->aspect = $aspect;
        $this->first = $first;
        $this->height = $height;
        $this->width = $width;
    }


    /**
     * Get the value of aspect
     */
    public function getAspect()
    {
        return $this->aspect;
    }

    /**
     * Set the value of aspect
     */
    public function setAspect($aspect)
    {
        $this->aspect = $aspect;
    }

    /**
     * Get the value of first
     */
    public function getFirst()
    {
        return $this->first;
    }

    /**
     * Set the value of first
     */
    public function setFirst($first)
    {
        $this->first = $first;
    }

    /**
     * Get the value of height
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set the value of height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * Get the value of width
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set the value of width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }
}
