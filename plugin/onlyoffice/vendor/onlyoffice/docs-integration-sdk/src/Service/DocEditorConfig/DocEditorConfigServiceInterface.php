<?php

namespace Onlyoffice\DocsIntegrationSdk\Service\DocEditorConfig;

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

/**
 * Editors config service Interface.
 *
 * @package Onlyoffice\DocsIntegrationSdk\Service\DocEditorConfig
 */

use Onlyoffice\DocsIntegrationSdk\Models\CoEditing;
use Onlyoffice\DocsIntegrationSdk\Models\Customization;
use Onlyoffice\DocsIntegrationSdk\Models\Document;
use Onlyoffice\DocsIntegrationSdk\Models\EditorsMode;
use Onlyoffice\DocsIntegrationSdk\Models\Embedded;
use Onlyoffice\DocsIntegrationSdk\Models\Info;
use Onlyoffice\DocsIntegrationSdk\Models\Permissions;
use Onlyoffice\DocsIntegrationSdk\Models\Recent;
use Onlyoffice\DocsIntegrationSdk\Models\ReferenceData;
use Onlyoffice\DocsIntegrationSdk\Models\Type;
use Onlyoffice\DocsIntegrationSdk\Models\User;

interface DocEditorConfigServiceInterface
{
    /**
     * Creates a configuration for the document editor using the User-Agent request header.
     *
     * @param string $fileId The file ID.
     * @param EditorsMode $mode The editor opening mode.
     * @param string $userAgent The User-Agent request header that is used
     * to determine the platform type ("desktop" or "mobile").
     * @throws Exception If the processing fails unexpectedly.
     * @return Config
     */
    public function createConfig(string $fileId, EditorsMode $mode, string $userAgent);

    /**
     * Checks whether the mobile agent is used or not.
     *
     * @param string $userAgent The User-Agent request header.
     * @throws Exception If the processing fails unexpectedly.
     * @return bool
     */
    public function isMobileAgent(string $userAgent);

    /**
     * Returns the DocEditorConfig object.
     *
     * @param string $fileId The file ID.
     * @param EditorsMode $mode The editor opening mode.
     * @param Type $type The platform type used to access the document.
     * @throws Exception If the processing fails unexpectedly.
     * @return DocEditorConfig
     */
    public function getDocEditorConfig(string $fileId, EditorsMode $mode, Type $type);

    /**
     * Returns the Document object.
     *
     * @param string $fileId The file ID.
     * @param Type $type The platform type used to access the document.
     * @throws Exception If the processing fails unexpectedly.
     * @return Document
     */
    public function getDocument(string $fileId, Type $type);

    /**
     * Returns the Customization object.
     *
     * @param string $fileId The file ID.
     * @throws Exception If the processing fails unexpectedly.
     * @return Customization
     */
    public function getCustomization(string $fileId);

    /**
     * Returns the Permissions object.
     *
     * @param string $fileId The file ID.
     * @throws Exception If the processing fails unexpectedly.
     * @return Permissions
     */
    public function getPermissions(string $fileId);

    /**
     * Returns the ReferenceData object.
     *
     * @param string $fileId The file ID.
     * @throws Exception If the processing fails unexpectedly.
     * @return ReferenceData
     */
    public function getReferenceData(string $fileId);

    /**
     * Returns the Info object.
     *
     * @param string $fileId The file ID.
     * @throws Exception If the processing fails unexpectedly.
     * @return Info
     */
    public function getInfo(string $fileId);

    /**
     * Returns the CoEditing object.
     *
     * @param string $fileId The file ID.
     * @param EditorsMode The editor opening mode.
     * @param Type The platform type used to access the document.
     * @throws Exception If the processing fails unexpectedly.
     * @return CoEditing
     */
    public function getCoEditing(string $fileId, EditorsMode $mode, Type $type);

    /**
     * Returns the Type object.
     *
     * @param string $userAgent The User-Agent request header.
     * @throws Exception If the processing fails unexpectedly.
     * @return Type
     */
    public function getType(string $userAgent);

    /**
     * Returns the User object.
     *
     * @throws Exception If the processing fails unexpectedly.
     * @return User
     */
    public function getUser();

    /**
     * Returns array of Recent objects.
     *
     * @throws Exception If the processing fails unexpectedly.
     * @return Recent[]
     */
    public function getRecent();

    /**
     * Returns array of Template objects.
     *
     * @param string $fileId The file ID.
     * @throws Exception If the processing fails unexpectedly.
     * @return Template[]
     */
    public function getTemplates(string $fileId);

    /**
     * Returns the Embedded object.
     *
     * @param string $fileId The file ID.
     * @throws Exception If the processing fails unexpectedly.
     * @return Embedded
     */
    public function getEmbedded(string $fileId);
}
