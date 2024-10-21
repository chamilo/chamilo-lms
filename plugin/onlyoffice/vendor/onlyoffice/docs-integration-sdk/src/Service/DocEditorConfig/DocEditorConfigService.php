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
use Onlyoffice\DocsIntegrationSdk\Manager\Settings\SettingsManager;
use Onlyoffice\DocsIntegrationSdk\Manager\Document\DocumentManager;
use Onlyoffice\DocsIntegrationSdk\Service\DocEditorConfig\DocEditorConfigServiceInterface;
use Onlyoffice\DocsIntegrationSdk\Manager\Security\JwtManager;
use Onlyoffice\DocsIntegrationSdk\Util\CommonError;
use Onlyoffice\DocsIntegrationSdk\Models\Config;
use Onlyoffice\DocsIntegrationSdk\Models\CoEditing;
use Onlyoffice\DocsIntegrationSdk\Models\Customization;
use Onlyoffice\DocsIntegrationSdk\Models\DocEditorConfig;
use Onlyoffice\DocsIntegrationSdk\Models\Document;
use Onlyoffice\DocsIntegrationSdk\Models\DocumentType;
use Onlyoffice\DocsIntegrationSdk\Models\EditorsMode;
use Onlyoffice\DocsIntegrationSdk\Models\Embedded;
use Onlyoffice\DocsIntegrationSdk\Models\GoBack;
use Onlyoffice\DocsIntegrationSdk\Models\Info;
use Onlyoffice\DocsIntegrationSdk\Models\Permissions;
use Onlyoffice\DocsIntegrationSdk\Models\Recent;
use Onlyoffice\DocsIntegrationSdk\Models\ReferenceData;
use Onlyoffice\DocsIntegrationSdk\Models\Template;
use Onlyoffice\DocsIntegrationSdk\Models\Type;
use Onlyoffice\DocsIntegrationSdk\Models\User;
use Onlyoffice\DocsIntegrationSdk\Util\EnvUtil;

abstract class DocEditorConfigService implements DocEditorConfigServiceInterface
{

    protected $documentManager;
    protected $jwtManager;
    protected $settingsManager;

    public function __construct(
        SettingsManager $settingsManager,
        JwtManager $jwtManager,
        DocumentManager $documentManager
    ) {
        EnvUtil::loadEnvSettings();
        $this->settingsManager = $settingsManager;
        $this->jwtManager = $jwtManager;
        $this->documentManager = $documentManager;
    }

    public function createConfig(string $fileId, EditorsMode $mode, string $userAgent)
    {
        $documentName = $this->documentManager->getDocumentName($fileId);
        $type = $this->getType($userAgent);
        $ext = $this->documentManager->getExt($documentName);
        $documentType = new DocumentType($this->documentManager->getDocType($ext));
        $document = $this->getDocument($fileId, $type);
        $editorConfig = $this->getDocEditorConfig($fileId, $mode, $type);
        $config = new Config(
            $documentType,
            "100%",
            "100%",
            "",
            $type,
            $editorConfig,
            $document
        );

        if ($this->jwtManager->isJwtEnabled()) {
            $config->setToken($this->jwtManager->jwtEncode($config));
        }
        return $config;
    }

    public function isMobileAgent(string $userAgent = "")
    {
        $userAgent = !empty($userAgent) ? $userAgent : $_SERVER["HTTP_USER_AGENT"];
        $envKey = EnvUtil::envKey("EDITING_SERVICE_MOBILE_USER_AGENT");
        // phpcs:ignore
        $agentList = isset($_ENV[$envKey]) && !empty($_ENV[$envKey]) ? $_ENV[$envKey] : "android|avantgo|playbook|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od|ad)|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\\/|plucker|pocket|psp|symbian|treo|up\\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino";
        return preg_match($agentList, $userAgent);
    }

    public function getDocEditorConfig(string $fileId, EditorsMode $mode, Type $type)
    {
        $permissions = $this->getPermissions($fileId);
        $editorConfig = new DocEditorConfig;
        $editorConfig->setCoEditing($this->getCoEditing($fileId, $mode, $type));
        $editorConfig->setCreateUrl($this->documentManager->getCreateUrl($fileId));
        $editorConfig->setUser($this->getUser());
        $editorConfig->setRecent($this->getRecent());
        $editorConfig->setTemplates($this->getTemplates($fileId));
        $editorConfig->setCustomization($this->getCustomization($fileId));
        $editorConfig->setLang($this->getLang());
        $editorConfig->setRegion($this->getRegion());

        if (($permissions->getEdit() || $permissions->getFillForms() ||
         $permissions->getComment() ||$permissions->getReview())
         && $mode->getValue() === EditorsMode::EDIT) {
            $editorConfig->setCallbackUrl($this->documentManager->getCallbackUrl($fileId));
        }

        if ($type->getValue() === Type::EMBEDDED) {
            $editorConfig->setEmbedded($this->getEmbedded($fileId));
        }

        return $editorConfig;
    }

    public function getDocument(string $fileId, Type $type)
    {
        $documentName = $this->documentManager->getDocumentName($fileId);
        $permissions = $this->getPermissions($fileId);
        $document = new Document(
            $this->documentManager->getExt($documentName),
            $this->documentManager->getDocumentKey($fileId, $type->getValue() === Type::EMBEDDED),
            $this->getReferenceData($fileId),
            $documentName,
            $this->documentManager->getFileUrl($fileId),
            $this->getInfo($fileId),
            $permissions
        );
   
        return $document;
    }

    public function getCustomization(string $fileId)
    {
        $goback = new GoBack;

        if (!empty($this->documentManager->getGobackUrl($fileId))) {
            $goback->setUrl($this->documentManager->getGobackUrl($fileId));
        }

        $customization = new Customization;
        $customization->setGoback($goback);

        return $customization;
    }

    public function getPermissions(string $fileId = "")
    {
        return null;
    }

    public function getReferenceData(string $fileId = "")
    {
        return null;
    }

    public function getInfo(string $fileId = "")
    {
        return null;
    }

    public function getCoEditing(string $fileId = "", EditorsMode $mode = null, Type $type)
    {
        return null;
    }

    public function getType(string $userAgent = "")
    {
        return $this->isMobileAgent($userAgent) ? new Type(Type::MOBILE) : new Type(Type::DESKTOP);
    }

    public function getUser()
    {
        return null;
    }

    public function getRecent()
    {
        return null;
    }

    public function getTemplates($fileId)
    {
        return null;
    }

    public function getEmbedded($fileId)
    {
        return null;
    }

    public function getLang()
    {
        return "en";
    }

    public function getRegion()
    {
        return "en-US";
    }
}
