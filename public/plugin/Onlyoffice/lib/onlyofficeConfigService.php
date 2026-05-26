<?php
/**
 * (c) Copyright Ascensio System SIA 2025.
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
 */
use Onlyoffice\DocsIntegrationSdk\Models\Customization;
use Onlyoffice\DocsIntegrationSdk\Models\EditorsMode;
use Onlyoffice\DocsIntegrationSdk\Models\GoBack;
use Onlyoffice\DocsIntegrationSdk\Models\Permissions;
use Onlyoffice\DocsIntegrationSdk\Models\Type;
use Onlyoffice\DocsIntegrationSdk\Models\User;
use Onlyoffice\DocsIntegrationSdk\Service\DocEditorConfig\DocEditorConfigService;

class OnlyofficeConfigService extends DocEditorConfigService
{
    public function __construct($settingsManager, $jwtManager, $documentManager)
    {
        parent::__construct($settingsManager, $jwtManager, $documentManager);
    }

    public function getEditorsMode()
    {
        if ($this->isEditable() && $this->getAccessRights() && !$this->isReadOnly()) {
            return new EditorsMode('edit');
        }

        if ($this->canView()) {
            return new EditorsMode('view');
        }

        api_not_allowed(true);
    }

    public function isEditable()
    {
        return $this->documentManager->isDocumentEditable(
            (string) $this->documentManager->getDocInfo('title')
        );
    }

    public function canView()
    {
        return $this->documentManager->isDocumentViewable(
            (string) $this->documentManager->getDocInfo('title')
        );
    }

    public function getAccessRights()
    {
        // Exercise responses must remain editable.
        if (!empty($_GET['exerciseId']) || !empty($_GET['exeId'])) {
            return true;
        }

        // Explicit readonly flag always wins.
        if ($this->isReadOnly()) {
            return false;
        }

        // Standard teacher/admin edit permission in current context.
        if (api_is_platform_admin()) {
            return true;
        }

        if (api_is_allowed_to_edit(true, true)) {
            return true;
        }

        // Minimal fallback for user-owned resources when creator info exists.
        $currentUserId = (int) api_get_user_id();
        $creatorId = $this->resolveDocumentCreatorId();

        if ($creatorId > 0 && $creatorId === $currentUserId) {
            return true;
        }

        return false;
    }

    public function isReadOnly()
    {
        return (bool) $this->documentManager->getDocInfo('readonly');
    }

    public function getUser()
    {
        $user = new User();

        $userId = (int) api_get_user_id();
        $user->setId($userId);

        $userInfo = api_get_user_info($userId);
        $userName = '';

        if (is_array($userInfo)) {
            $userName = (string) ($userInfo['complete_name'] ?? $userInfo['username'] ?? '');
        }

        if ('' === trim($userName)) {
            $userName = 'user_'.$userId;
        }

        $user->setName($userName);

        return $user;
    }

    public function getCustomization(string $fileId)
    {
        $goback = new GoBack();

        $gobackUrl = $this->documentManager->getGobackUrl($fileId);
        if (!empty($gobackUrl)) {
            $goback->setUrl($gobackUrl);
        }
        $goback->setBlank(false);
        $customization = new Customization();
        $customization->setGoback($goback);
        $customization->setCompactHeader(true);
        $customization->setToolbarNoTabs(true);

        return $customization;
    }

    public function getLang()
    {
        return $this->getLangInfo();
    }

    public function getRegion()
    {
        return $this->getLangInfo();
    }

    public function getLangInfo()
    {
        $langInfo = LangManager::getLangUser();

        if (is_array($langInfo) && !empty($langInfo['isocode'])) {
            return (string) $langInfo['isocode'];
        }

        return 'en';
    }
    public function getPermissions(string $fileId = '')
    {
        $permsEdit = $this->getAccessRights() && !$this->isReadOnly();
        $isFillable = $this->documentManager->isDocumentFillable(
            (string) $this->documentManager->getDocInfo('title')
        );

        return new Permissions(
            null,
            null,
            null,
            null,
            null,
            null,
            $permsEdit,
            null,
            $isFillable,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );
    }

    public function getCoEditing(string $fileId = '', $mode = null, $type = null)
    {
        return null;
    }

    public function isMobileAgent(string $userAgent = '')
    {
        if ('' === trim($userAgent)) {
            $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
        }

        if ('' === trim($userAgent)) {
            return false;
        }

        return 1 === preg_match('/android|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile/i', $userAgent);
    }

    public function getType(string $userAgent = '')
    {
        return new Type($this->isMobileAgent($userAgent) ? 'mobile' : 'desktop');
    }

    private function resolveDocumentCreatorId(): int
    {
        $candidates = [
            $this->documentManager->getDocInfo('creator_id'),
            $this->documentManager->getDocInfo('insert_user_id'),
            $this->documentManager->getDocInfo('user_id'),
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate) && (int) $candidate > 0) {
                return (int) $candidate;
            }
        }

        return 0;
    }
}
