<?php
/**
 * (c) Copyright Ascensio System SIA 2024.
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
}
