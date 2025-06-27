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
use DocumentManager as ChamiloDocumentManager;
use Onlyoffice\DocsIntegrationSdk\Manager\Document\DocumentManager;

class OnlyofficeDocumentManager extends DocumentManager
{
    private $docInfo;

    public function __construct($settingsManager, array $docInfo, $formats = null, $systemLangCode = 'en')
    {
        $formats = new OnlyofficeFormatsManager();
        parent::__construct($settingsManager, $formats, $systemLangCode);
        $this->docInfo = $docInfo;
    }

    public function getDocumentKey(string $fileId, $courseCode, bool $embedded = false)
    {
        if (!isset($this->docInfo['absolute_path'])) {
            return null;
        }
        $mtime = filemtime($this->docInfo['absolute_path']);
        $key = $mtime.$courseCode.$fileId;

        return self::generateRevisionId($key);
    }

    public function getDocumentName(string $fileId = '')
    {
        return $this->docInfo['title'];
    }

    public static function getLangMapping()
    {
    }

    public function getFileUrl(string $fileId)
    {
        $data = [
            'type' => 'download',
            'courseId' => api_get_course_int_id(),
            'userId' => api_get_user_id(),
            'docId' => $fileId,
            'sessionId' => api_get_session_id(),
        ];

        if (!empty($this->getGroupId())) {
            $data['groupId'] = $this->getGroupId();
        }

        if (isset($this->docInfo['path']) && str_contains($this->docInfo['path'], 'exercises/')) {
            $data['doctype'] = 'exercise';
            $data['docPath'] = urlencode($this->docInfo['path']);
        }

        $jwtManager = new OnlyofficeJwtManager($this->settingsManager);
        $hashUrl = $jwtManager->getHash($data);

        return api_get_path(WEB_PLUGIN_PATH).$this->settingsManager->plugin->getPluginName().'/callback.php?hash='.$hashUrl;
    }

    public function getGroupId()
    {
        $groupId = isset($_GET['groupId']) && !empty($_GET['groupId']) ? $_GET['groupId'] : null;

        return $groupId;
    }

    public function getCallbackUrl(string $fileId)
    {
        $data = [
            'type' => 'track',
            'courseId' => api_get_course_int_id(),
            'userId' => api_get_user_id(),
            'docId' => $fileId,
            'sessionId' => api_get_session_id(),
        ];

        if (!empty($this->getGroupId())) {
            $data['groupId'] = $this->getGroupId();
        }

        if (isset($this->docInfo['path']) && str_contains($this->docInfo['path'], 'exercises/')) {
            $data['doctype'] = 'exercise';
            $data['docPath'] = urlencode($this->docInfo['path']);
        }

        $jwtManager = new OnlyofficeJwtManager($this->settingsManager);
        $hashUrl = $jwtManager->getHash($data);

        return api_get_path(WEB_PLUGIN_PATH).'onlyoffice/callback.php?hash='.$hashUrl;
    }

    public function getGobackUrl(string $fileId): string
    {
        if (!empty($this->docInfo)) {
            if (isset($this->docInfo['path']) && str_contains($this->docInfo['path'], 'exercises/')) {
                return api_get_path(WEB_CODE_PATH).'exercise/exercise_submit.php'
                    .'?cidReq='.Security::remove_XSS(api_get_course_id())
                    .'&id_session='.Security::remove_XSS(api_get_session_id())
                    .'&gidReq='.Security::remove_XSS($this->getGroupId())
                    .'&exerciseId='.Security::remove_XSS($this->docInfo['exercise_id']);
            }

            return self::getUrlToLocation(api_get_course_id(), api_get_session_id(), $this->getGroupId(), $this->docInfo['parent_id'], $this->docInfo['path'] ?? '');
        }

        return '';
    }

    /**
     * Return location file in Chamilo documents or exercises.
     */
    public static function getUrlToLocation($courseCode, $sessionId, $groupId, $folderId, $filePath = ''): string
    {
        if (!empty($filePath) && str_contains($filePath, 'exercises/')) {
            return api_get_path(WEB_CODE_PATH).'exercise/exercise_submit.php'
                .'?cidReq='.Security::remove_XSS($courseCode)
                .'&id_session='.Security::remove_XSS($sessionId)
                .'&gidReq='.Security::remove_XSS($groupId)
                .'&exerciseId='.Security::remove_XSS($folderId);
        }

        return api_get_path(WEB_CODE_PATH).'document/document.php'
            .'?cidReq='.Security::remove_XSS($courseCode)
            .'&id_session='.Security::remove_XSS($sessionId)
            .'&gidReq='.Security::remove_XSS($groupId)
            .'&id='.Security::remove_XSS($folderId);
    }

    public function getCreateUrl(string $fileId)
    {
    }

    /**
     * Get the value of docInfo.
     */
    public function getDocInfo($elem = null)
    {
        if (empty($elem)) {
            return $this->docInfo;
        } else {
            if (isset($this->docInfo[$elem])) {
                return $this->docInfo[$elem];
            }

            return [];
        }
    }

    /**
     * Set the value of docInfo.
     */
    public function setDocInfo($docInfo)
    {
        $this->docInfo = $docInfo;
    }

    /**
     * Return file extension by file type.
     */
    public static function getDocExtByType(string $type): string
    {
        if ('text' === $type) {
            return 'docx';
        }
        if ('spreadsheet' === $type) {
            return 'xlsx';
        }
        if ('presentation' === $type) {
            return 'pptx';
        }
        if ('formTemplate' === $type) {
            return 'pdf';
        }

        return '';
    }

    /**
     * Create new file.
     */
    public static function createFile(
        string $basename,
        string $fileExt,
        int $folderId,
        int $userId,
        int $sessionId,
        int $courseId,
        int $groupId,
        string $templatePath = ''): array
    {
        $courseInfo = api_get_course_info_by_id($courseId);
        $courseCode = $courseInfo['code'];
        $groupInfo = GroupManager::get_group_properties($groupId);

        $fileTitle = Security::remove_XSS($basename).'.'.$fileExt;

        $fileNameSuffix = ChamiloDocumentManager::getDocumentSuffix($courseInfo, $sessionId, $groupId);
        // Try to avoid directories browsing (remove .., slashes and backslashes)
        $patterns = ['#\.\./#', '#\.\.#', '#/#', '#\\\#'];
        $replacements = ['', '', '', ''];
        $fileName = preg_replace($patterns, $replacements, $basename).$fileNameSuffix.'.'.$fileExt;

        if (empty($templatePath)) {
            $templatePath = TemplateManager::getEmptyTemplate($fileExt);
        }

        $folderPath = '';
        $fileRelatedPath = '/';
        if (!empty($folderId)) {
            $document_data = ChamiloDocumentManager::get_document_data_by_id(
                $folderId,
                $courseCode,
                true,
                $sessionId
            );
            $folderPath = $document_data['absolute_path'];
            $fileRelatedPath = $fileRelatedPath.substr($document_data['absolute_path_from_document'], 10).'/'.$fileName;
        } else {
            $folderPath = api_get_path(SYS_COURSE_PATH).api_get_course_path($courseCode).'/document';
            if (!empty($groupId)) {
                $folderPath = $folderPath.'/'.$groupInfo['directory'];
                $fileRelatedPath = $groupInfo['directory'].'/';
            }
            $fileRelatedPath = $fileRelatedPath.$fileName;
        }
        $filePath = $folderPath.'/'.$fileName;

        if (file_exists($filePath)) {
            return ['error' => 'fileIsExist'];
        }

        if ($fp = @fopen($filePath, 'w')) {
            $content = file_get_contents($templatePath);
            fputs($fp, $content);
            fclose($fp);

            chmod($filePath, api_get_permissions_for_new_files());

            $documentId = add_document(
                $courseInfo,
                $fileRelatedPath,
                'file',
                filesize($filePath),
                $fileTitle,
                null,
                false
            );
            if ($documentId) {
                api_item_property_update(
                    $courseInfo,
                    TOOL_DOCUMENT,
                    $documentId,
                    'DocumentAdded',
                    $userId,
                    $groupInfo,
                    null,
                    null,
                    null,
                    $sessionId
                );
            } else {
                return ['error' => 'impossibleCreateFile'];
            }
        }

        return ['documentId' => $documentId];
    }
}
