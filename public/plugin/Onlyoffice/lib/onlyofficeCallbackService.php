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
use Onlyoffice\DocsIntegrationSdk\Service\Callback\CallbackService;

class OnlyofficeCallbackService extends CallbackService
{
    private $docData;

    public function __construct($settingsManager, $jwtManager, $docData = [])
    {
        parent::__construct($settingsManager, $jwtManager);
        $this->docData = $docData;
        $this->trackResult = 1;
    }

    public function processTrackerStatusEditing($callback, string $fileid)
    {
        return $this->processTrackerStatusEditingAndClosed($callback, $fileid);
    }

    public function processTrackerStatusMustsave($callback, string $fileid)
    {
        return $this->processTrackerStatusMustsaveAndCorrupted($callback, $fileid);
    }

    public function processTrackerStatusCorrupted($callback, string $fileid)
    {
        return $this->processTrackerStatusMustsaveAndCorrupted($callback, $fileid);
    }

    public function processTrackerStatusClosed($callback, string $fileid)
    {
        return $this->processTrackerStatusEditingAndClosed($callback, $fileid);
    }

    public function processTrackerStatusForcesave($callback, string $fileid)
    {
        $result['error'] = $this->trackResult;

        return $result;
    }

    public function processTrackerStatusMustsaveAndCorrupted($callback, string $fileid)
    {
        $downloadUri = $callback->getUrl();
        $this->settingsManager->replaceDocumentServerUrlToInternal($downloadUri);
        try {
            if (!empty($this->docData['docId']) && !empty($this->docData['courseCode'])) {
                $docInfo = DocumentManager::get_document_data_by_id($fileid, $this->docData['courseCode'], false, $this->docData['sessionId']);
                if (false === $docInfo) {
                    $result['error'] = 'File not found';

                    return $result;
                }
                $filePath = $docInfo['absolute_path'];
            } else {
                $result['error'] = 'Bad Request';

                return $result;
            }
            list($isAllowToEdit, $isMyDir, $isGroupAccess, $isReadonly) = $this->getPermissionsByDocInfo($docInfo);

            if ($isReadonly) {
                $result['error'] = $this->trackResult;

                return $result;
            }
            if (($new_data = file_get_contents($downloadUri)) === false) {
                $result['error'] = $this->trackResult;

                return $result;
            }

            if ($isAllowToEdit || $isMyDir || $isGroupAccess) {
                $groupInfo = GroupManager::get_group_properties($this->docData['groupId']);

                if ($fp = @fopen($filePath, 'w')) {
                    fputs($fp, $new_data);
                    fclose($fp);
                    api_item_property_update($this->docData['courseInfo'],
                        TOOL_DOCUMENT,
                        $fileid,
                        'DocumentUpdated',
                        $this->docData['userId'],
                        $groupInfo,
                        null,
                        null,
                        null,
                        $this->docData['sessionId']);
                    update_existing_document($this->docData['courseInfo'],
                        $fileid,
                        filesize($filePath),
                        false);
                    $this->trackResult = 0;
                    $result['error'] = $this->trackResult;

                    return $result;
                }
            }
        } catch (UnexpectedValueException $e) {
            $result['error'] = 'Bad Request';

            return $result;
        }
    }

    public function processTrackerStatusEditingAndClosed($callback, string $fileid)
    {
        $this->trackResult = 0;
        $result['error'] = $this->trackResult;

        return $result;
    }

    /**
     * Method checks access rights to document and returns permissions.
     */
    public function getPermissionsByDocInfo(array $docInfo)
    {
        $isAllowToEdit = api_is_allowed_to_edit(true, true);
        $isMyDir = DocumentManager::is_my_shared_folder($this->docData['userId'], $docInfo['absolute_parent_path'], $this->docData['sessionId']);

        $isGroupAccess = false;
        if (!empty($groupId)) {
            $courseInfo = api_get_course_info($this->docData['courseCode']);
            Session::write('_real_cid', $courseInfo['real_id']);
            $groupProperties = GroupManager::get_group_properties($this->docData['groupId']);
            $docInfoGroup = api_get_item_property_info($courseInfo['real_id'], 'document', $docInfo['id'], $this->docData['sessionId']);
            $isGroupAccess = GroupManager::allowUploadEditDocument($this->docData['userId'], $this->docData['courseCode'], $groupProperties, $docInfoGroup);
        }

        $isReadonly = $docInfo['readonly'];

        return [$isAllowToEdit, $isMyDir, $isGroupAccess, $isReadonly];
    }
}
