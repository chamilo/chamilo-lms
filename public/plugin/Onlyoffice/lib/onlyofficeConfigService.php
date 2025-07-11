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
            $editorsMode = new EditorsMode('edit');
        } else {
            if ($this->canView()) {
                $editorsMode = new EditorsMode('view');
            } else {
                api_not_allowed(true);
            }
        }

        return $editorsMode;
    }

    public function isEditable()
    {
        return $this->documentManager->isDocumentEditable($this->documentManager->getDocInfo('title'));
    }

    public function canView()
    {
        return $this->documentManager->isDocumentViewable($this->documentManager->getDocInfo('title'));
    }

    public function getAccessRights()
    {
        $isAllowToEdit = api_is_allowed_to_edit(true, true);
        $isMyDir = DocumentManager::is_my_shared_folder(
            api_get_user_id(),
            $this->documentManager->getDocInfo('absolute_parent_path'),
            api_get_session_id()
        );
        $isGroupAccess = false;
        if (!empty($this->documentManager->getGroupId())) {
            $groupProperties = GroupManager::get_group_properties($this->documentManager->getGroupId());
            $docInfoGroup = api_get_item_property_info(
                api_get_course_int_id(),
                'document',
                $docId,
                $sessionId
            );
            $isGroupAccess = GroupManager::allowUploadEditDocument(
                $userId,
                $courseCode,
                $groupProperties,
                $docInfoGroup
            );
            $isMemberGroup = GroupManager::is_user_in_group($userId, $groupProperties);
            if (!$isGroupAccess) {
                if (!$groupProperties['status']) {
                    api_not_allowed(true);
                }
                if (!$isMemberGroup && 1 != $groupProperties['doc_state']) {
                    api_not_allowed(true);
                }
            }
        }

        // Allow editing if the document is part of an exercise
        if (!empty($_GET['exerciseId']) || !empty($_GET['exeId'])) {
            return true;
        }

        $accessRights = $isAllowToEdit || $isMyDir || $isGroupAccess;

        return $accessRights;
    }

    public function isReadOnly()
    {
        return $this->documentManager->getDocInfo('readonly');
    }

    public function getUser()
    {
        $user = new User();
        $user->setId(api_get_user_id());
        $userInfo = api_get_user_info($userId);
        $user->setName($userInfo['username']);

        return $user;
    }

    public function getCustomization(string $fileId)
    {
        $goback = new GoBack();

        if (!empty($this->documentManager->getGobackUrl($fileId))) {
            $goback->setUrl($this->documentManager->getGobackUrl($fileId));
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

        return $langInfo['isocode'];
    }

    public function getPermissions(string $fileId = '')
    {
        $permsEdit = $this->getAccessRights() && !$this->isReadOnly();
        $isFillable = $this->documentManager->isDocumentFillable($this->documentManager->getDocInfo('title'));

        $permissions = new Permissions(null,
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

        return $permissions;
    }

    public function getCoEditing(string $fileId = '', $mode = null, $type)
    {
        return null;
    }
}
