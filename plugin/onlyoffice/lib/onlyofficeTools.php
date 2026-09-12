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
class OnlyofficeTools
{
    /**
     * Sub-directory, relative to the course directory, where the plugin is
     * allowed to address a file by its raw path: the documents attached to an
     * exercise and the answers submitted through ONLYOFFICE.
     */
    public const EXERCISE_PATH = 'exercises/onlyoffice';

    /**
     * Return button-link to onlyoffice editor for file.
     */
    public static function getButtonEdit(array $document_data): string
    {
        $plugin = OnlyofficePlugin::create();

        $appSettings = new OnlyofficeAppsettings($plugin);
        $documentManager = new OnlyofficeDocumentManager($appSettings, []);
        $isEnable = 'true' === $plugin->get('enable_onlyoffice_plugin');
        if (!$isEnable) {
            return '';
        }

        $urlToEdit = api_get_path(WEB_PLUGIN_PATH).'onlyoffice/editor.php';

        $extension = strtolower(pathinfo($document_data['title'], PATHINFO_EXTENSION));

        $canEdit = null !== $documentManager->getFormatInfo($extension) ? $documentManager->getFormatInfo($extension)->isEditable() : false;
        $canView = null !== $documentManager->getFormatInfo($extension) ? $documentManager->getFormatInfo($extension)->isViewable() : false;

        $groupId = api_get_group_id();
        if (!empty($groupId)) {
            $urlToEdit = $urlToEdit.'?groupId='.$groupId.'&';
        } else {
            $urlToEdit = $urlToEdit.'?';
        }

        $documentId = $document_data['id'];
        $urlToEdit = $urlToEdit.'docId='.$documentId;

        if ($canEdit || $canView) {
            $tooltip = $plugin->get_lang('openByOnlyoffice');
            if ('pdf' === $extension) {
                $tooltip = $plugin->get_lang('fillInFormInOnlyoffice');
            }

            return Display::url(
                Display::return_icon(
                    '../../plugin/onlyoffice/resources/onlyoffice_edit.png',
                    $tooltip
                ),
                $urlToEdit
            );
        }

        return '';
    }

    /**
     * Return button-link to onlyoffice editor for view file.
     */
    public static function getButtonView(array $document_data): string
    {
        $plugin = OnlyofficePlugin::create();
        $appSettings = new OnlyofficeAppsettings($plugin);
        $documentManager = new OnlyofficeDocumentManager($appSettings, []);

        $isEnable = 'true' === $plugin->get('enable_onlyoffice_plugin');
        if (!$isEnable) {
            return '';
        }

        $urlToEdit = api_get_path(WEB_PLUGIN_PATH).'onlyoffice/editor.php';

        $sessionId = api_get_session_id();
        $courseInfo = api_get_course_info();
        $documentId = $document_data['id'];
        $userId = api_get_user_id();

        $docInfo = DocumentManager::get_document_data_by_id($documentId, $courseInfo['code'], false, $sessionId);

        $extension = strtolower(pathinfo($document_data['title'], PATHINFO_EXTENSION));
        $canView = null !== $documentManager->getFormatInfo($extension) ? $documentManager->getFormatInfo($extension)->isViewable() : false;

        $isGroupAccess = false;
        $groupId = api_get_group_id();
        if (!empty($groupId)) {
            $groupProperties = GroupManager::get_group_properties($groupId);
            $docInfoGroup = api_get_item_property_info(api_get_course_int_id(), 'document', $documentId, $sessionId);
            $isGroupAccess = GroupManager::allowUploadEditDocument($userId, $courseInfo['code'], $groupProperties, $docInfoGroup);

            $urlToEdit = $urlToEdit.'?groupId='.$groupId.'&';
        } else {
            $urlToEdit = $urlToEdit.'?';
        }

        $isAllowToEdit = api_is_allowed_to_edit(true, true);
        $isMyDir = DocumentManager::is_my_shared_folder($userId, $docInfo['absolute_parent_path'], $sessionId);

        $accessRights = $isAllowToEdit || $isMyDir || $isGroupAccess;

        $urlToEdit = $urlToEdit.'docId='.$documentId;

        if ($canView && !$accessRights) {
            return Display::url(Display::return_icon('../../plugin/onlyoffice/resources/onlyoffice_view.png', $plugin->get_lang('openByOnlyoffice')), $urlToEdit, ['style' => 'float:right; margin-right:8px']);
        }

        return '';
    }

    /**
     * Return button-link to onlyoffice create new.
     */
    public static function getButtonCreateNew(): string
    {
        $plugin = OnlyofficePlugin::create();

        $isEnable = 'true' === $plugin->get('enable_onlyoffice_plugin');
        if (!$isEnable) {
            return '';
        }

        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();
        $groupId = api_get_group_id();
        $userId = api_get_user_id();

        $urlToCreate = api_get_path(WEB_PLUGIN_PATH).'onlyoffice/create.php'
                                                        .'?folderId='.(empty($_GET['id']) ? '0' : (int) $_GET['id'])
                                                        .'&courseId='.$courseId
                                                        .'&groupId='.$groupId
                                                        .'&sessionId='.$sessionId
                                                        .'&userId='.$userId;

        return Display::url(
            Display::return_icon(
                '../../plugin/onlyoffice/resources/onlyoffice_create.png',
                $plugin->get_lang('createNew')
            ),
            $urlToCreate
        );
    }

    /**
     * Return path to OnlyOffice viewer for a given file.
     */
    public static function getPathToView($fileReference, bool $showHeaders = true, ?int $exeId = null, ?int $questionId = null, bool $isReadOnly = false): string
    {
        $plugin = OnlyofficePlugin::create();
        $appSettings = new OnlyofficeAppsettings($plugin);
        $documentManager = new OnlyofficeDocumentManager($appSettings, []);

        $isEnable = 'true' === $plugin->get('enable_onlyoffice_plugin');
        if (!$isEnable) {
            return '';
        }

        $urlToEdit = api_get_path(WEB_PLUGIN_PATH).'onlyoffice/editor.php';
        $queryString = $_SERVER['QUERY_STRING'];
        $isExercise = str_contains($queryString, 'exerciseId=');

        if (is_numeric($fileReference)) {
            $documentId = (int) $fileReference;
            $courseInfo = api_get_course_info();
            $sessionId = api_get_session_id();
            $userId = api_get_user_id();

            $docInfo = DocumentManager::get_document_data_by_id($documentId, $courseInfo['code'], false, $sessionId);
            if (!$docInfo) {
                return '';
            }

            $extension = strtolower(pathinfo($docInfo['path'], PATHINFO_EXTENSION));
            $canView = null !== $documentManager->getFormatInfo($extension) ? $documentManager->getFormatInfo($extension)->isViewable() : false;

            $isGroupAccess = false;
            $groupId = api_get_group_id();
            if (!empty($groupId)) {
                $groupProperties = GroupManager::get_group_properties($groupId);
                $docInfoGroup = api_get_item_property_info(api_get_course_int_id(), 'document', $documentId, $sessionId);
                $isGroupAccess = GroupManager::allowUploadEditDocument($userId, $courseInfo['code'], $groupProperties, $docInfoGroup);

                $urlToEdit .= '?'.api_get_cidreq().'&';
            } else {
                $urlToEdit .= '?'.api_get_cidreq().'&';
            }

            $isMyDir = DocumentManager::is_my_shared_folder($userId, $docInfo['absolute_parent_path'], $sessionId);
            $accessRights = $isMyDir || $isGroupAccess;

            $urlToEdit .= 'docId='.$documentId;
            if (false === $showHeaders) {
                $urlToEdit .= '&nh=1';
            }

            if ($canView && !$accessRights) {
                return $urlToEdit;
            }
        } else {
            $urlToEdit .= '?'.$queryString.'&doc='.urlencode($fileReference);
            if ($isExercise) {
                $urlToEdit .= '&type=exercise';
                if ($exeId) {
                    $urlToEdit .= '&exeId='.$exeId;
                }

                if ($questionId) {
                    $urlToEdit .= '&questionId='.$questionId;
                }
            }
            if (false === $showHeaders) {
                $urlToEdit .= '&nh=1';
            }

            if (true === $isReadOnly) {
                $urlToEdit .= '&readOnly=1';
            }

            return $urlToEdit;
        }

        return '';
    }

    /**
     * Resolve a course-relative document path and check that it stays inside the
     * ONLYOFFICE directory of the given course.
     *
     * Raw paths reach the plugin from the request (editor) and from the signed
     * hash (callback), so they must never be used to address a file as they are.
     *
     * @return string|null the canonical course-relative path, or null when the path is not acceptable
     */
    public static function getSafeExercisePath(?string $docPath, ?array $courseInfo): ?string
    {
        if (empty($docPath) || empty($courseInfo['directory'])) {
            return null;
        }

        $docPath = str_replace('\\', '/', $docPath);
        if (false !== strpos($docPath, "\0")) {
            return null;
        }

        $coursesDir = realpath(api_get_path(SYS_COURSE_PATH));
        if (false === $coursesDir) {
            return null;
        }

        $baseDir = realpath($coursesDir.'/'.$courseInfo['directory'].'/'.self::EXERCISE_PATH);
        $realPath = realpath($coursesDir.'/'.ltrim($docPath, '/'));

        if (false === $baseDir || false === $realPath || !is_file($realPath)) {
            return null;
        }

        if (0 !== strpos($realPath, $baseDir.DIRECTORY_SEPARATOR)) {
            return null;
        }

        return substr($realPath, strlen($coursesDir) + 1);
    }

    /**
     * Check that the current user may address a file by its raw path.
     *
     * The answers are stored in a directory named after the user who submitted
     * them, so they are only available to their author and to the course
     * managers.
     */
    public static function isAllowedToUseExercisePath(string $docPath): bool
    {
        if (!preg_match('#/(\d+)/response_\d+\.[^/]+$#', $docPath, $matches)) {
            return true;
        }

        if ((int) $matches[1] === (int) api_get_user_id()) {
            return true;
        }

        return api_is_allowed_to_edit(true, true);
    }

    /**
     * Check that a file uses a format handled by ONLYOFFICE, so that the plugin
     * never creates or overwrites, for instance, a PHP script.
     */
    public static function isSupportedFormat(string $fileName): bool
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!preg_match('/^[a-z0-9]{1,10}$/', $extension)) {
            return false;
        }

        $formatsManager = new OnlyofficeFormatsManager();

        foreach ($formatsManager->getFormatsList() as $name => $format) {
            if ($extension === (string) $name || $extension === $format->getName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check that a URL received in a callback body points to the configured
     * document server.
     *
     * The document server is the only legitimate source of a new revision, and
     * restricting the scheme also prevents the content from being read through a
     * PHP stream wrapper (data://, file://, php://...).
     */
    public static function isAllowedDocumentServerUrl($url, OnlyofficeAppsettings $appSettings): bool
    {
        if (!is_string($url) || empty($url)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (empty($host)) {
            return false;
        }

        $allowedHosts = [];
        $serverUrls = [
            $appSettings->getDocumentServerUrl(),
            $appSettings->getDocumentServerInternalUrl(),
            $appSettings->getStorageUrl(),
        ];

        foreach ($serverUrls as $serverUrl) {
            $serverHost = strtolower((string) parse_url((string) $serverUrl, PHP_URL_HOST));
            if (!empty($serverHost)) {
                $allowedHosts[] = $serverHost;
            }
        }

        // The document server may be configured with a relative address: there is
        // then no host to compare with and only the scheme can be enforced.
        if (empty($allowedHosts)) {
            return true;
        }

        return in_array($host, $allowedHosts, true);
    }
}
