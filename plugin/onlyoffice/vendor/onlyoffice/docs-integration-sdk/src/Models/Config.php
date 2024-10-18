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
use Onlyoffice\DocsIntegrationSdk\Models\DocEditorConfig;
use Onlyoffice\DocsIntegrationSdk\Models\Document;
use Onlyoffice\DocsIntegrationSdk\Models\Type;

class Config extends JsonSerializable
{
    protected $documentType;
    protected $height;
    protected $width;
    protected $token;
    protected $type;
    protected $editorConfig;
    protected $document;
    

    public function __construct(
        DocumentType $documentType,
        string $height,
        string $width,
        string $token,
        Type $type,
        DocEditorConfig $editorConfig,
        Document $document
    ) {
        $this->documentType = $documentType;
        $this->height = $height;
        $this->width = $width;
        $this->token = $token;
        $this->type = $type;
        $this->editorConfig = $editorConfig;
        $this->document = $document;
    }
    /**
     * Get the value of documentType
     */
    public function getDocumentType()
    {
        return $this->documentType;
    }

    /**
     * Set the value of documentType
     */
    public function setDocumentType($documentType)
    {
        $this->documentType = $documentType;
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
     * Get the value of type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the value of type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get the value of editorConfig
     */
    public function getEditorConfig()
    {
        return $this->editorConfig;
    }

    /**
     * Set the value of editorConfig
     */
    public function setEditorConfig($editorConfig)
    {
        $this->editorConfig = $editorConfig;
    }

    /**
     * Get the value of document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Set the value of document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }
}
