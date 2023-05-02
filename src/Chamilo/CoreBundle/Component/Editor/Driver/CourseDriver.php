<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\Driver;

use CourseHome;

/**
 * Class CourseDriver.
 *
 * @package Chamilo\CoreBundle\Component\Editor\Driver
 */
class CourseDriver extends Driver implements DriverInterface
{
    public $name = 'CourseDriver';
    public $visibleFiles = [];
    private $coursePath;

    /**
     * Setups the folder.
     */
    public function setup()
    {
        $userId = api_get_user_id();
        $userInfo = api_get_user_info();
        $sessionId = api_get_session_id();
        $courseInfo = $this->connector->course;

        if (!empty($courseInfo)) {
            $coursePath = api_get_path(SYS_COURSE_PATH);
            $courseDir = $courseInfo['directory'].'/document';
            $baseDir = $coursePath.$courseDir;
            $this->coursePath = $baseDir;

            // Creates shared folder
            if (!file_exists($baseDir.'/shared_folder')) {
                $title = get_lang('UserFolders');
                $folderName = '/shared_folder';
                //$groupId = 0;
                $visibility = 0;
                create_unexisting_directory(
                    $courseInfo,
                    $userId,
                    $sessionId,
                    0,
                    null,
                    $baseDir,
                    $folderName,
                    $title,
                    $visibility
                );
            }

            // Creates user-course folder
            if (!file_exists($baseDir.'/shared_folder/sf_user_'.$userId)) {
                $title = $userInfo['complete_name'];
                $folderName = '/shared_folder/sf_user_'.$userId;
                $visibility = 1;
                create_unexisting_directory(
                    $courseInfo,
                    $userId,
                    $sessionId,
                    0,
                    null,
                    $baseDir,
                    $folderName,
                    $title,
                    $visibility
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        if ($this->allow()) {
            //$translator = $this->connector->translator;
            //$code = $this->connector->course->getCode();
            $courseCode = $this->connector->course['code'];
            $alias = $courseCode.' '.get_lang('Documents');
            $userId = api_get_user_id();
            $config = [
                'driver' => 'CourseDriver',
                'path' => $this->getCourseDocumentSysPath(),
                'URL' => $this->getCourseDocumentRelativeWebPath(),
                'accessControl' => [$this, 'access'],
                'alias' => $alias,
                'attributes' => [
                    // Hide shared_folder
                    [
                        'pattern' => '/shared_folder/',
                        'read' => false,
                        'write' => false,
                        'hidden' => true,
                        'locked' => false,
                    ],
                    [
                        'pattern' => '/^\/index.html$/',
                        'read' => false,
                        'write' => false,
                        'hidden' => true,
                        'locked' => false,
                    ],
                ],
            ];

            // admin/teachers can create dirs from ckeditor
            if ($this->allowToEdit()) {
                $config['attributes'][] = [
                    'pattern' => '/^\/learning_path$/', // block delete learning_path
                    'read' => true,
                    'write' => false,
                    'hidden' => false,
                    'locked' => true,
                ];
                $config['attributes'][] = [
                    'pattern' => '/learning_path\/(.*)/', // allow edit/delete inside learning_path
                    'read' => true,
                    'write' => true,
                    'hidden' => false,
                    'locked' => false,
                ];

                $defaultDisabled = $this->connector->getDefaultDriverSettings()['disabled'];
                $defaultDisabled = array_flip($defaultDisabled);
                unset($defaultDisabled['mkdir']);
                $defaultDisabled = array_flip($defaultDisabled);
                $config['disabled'] = $defaultDisabled;
            } else {
                $protectedFolders = \DocumentManager::getProtectedFolderFromStudent();
                foreach ($protectedFolders as $folder) {
                    $config['attributes'][] = [
                        'pattern' => $folder.'/',
                        'read' => false,
                        'write' => false,
                        'hidden' => true,
                        'locked' => false,
                    ];
                }
            }

            $foldersToHide = \DocumentManager::get_all_document_folders(
                $this->connector->course,
                null,
                false,
                true
            );

            // Teachers can see all files and folders see #1425
            if ($this->allowToEdit()) {
                $foldersToHide = [];
            }

            if (!empty($foldersToHide)) {
                foreach ($foldersToHide as $folder) {
                    $config['attributes'][] = [
                        'pattern' => '!'.$folder.'!',
                        'read' => false,
                        'write' => false,
                        'hidden' => true,
                        'locked' => false,
                    ];
                }
            }

            // Hide all groups folders
            $config['attributes'][] = [
                'pattern' => '!_groupdocs_!',
                'read' => false,
                'write' => false,
                'hidden' => true,
                'locked' => false,
            ];

            // Allow only the groups I have access
            $allGroups = \GroupManager::getAllGroupPerUserSubscription($userId);
            if (!empty($allGroups)) {
                foreach ($allGroups as $groupInfo) {
                    $groupId = $groupInfo['iid'];
                    if (\GroupManager::user_has_access(
                        $userId,
                        $groupId,
                        \GroupManager::GROUP_TOOL_DOCUMENTS
                    )) {
                        $config['attributes'][] = [
                            'pattern' => '!'.$groupInfo['secret_directory'].'!',
                            'read' => true,
                            'write' => false,
                            'hidden' => false,
                            'locked' => false,
                        ];
                    }
                }
            }

            return $config;
        }

        return [];
    }

    /**
     * This is the absolute document course path like
     * /var/www/portal/data/courses/XXX/document/.
     *
     * @return string
     */
    public function getCourseDocumentSysPath()
    {
        $url = '';
        if ($this->allow()) {
            $directory = $this->getCourseDirectory();
            $coursePath = $this->connector->paths['sys_course_path'];
            $url = $coursePath.$directory.'/document/';
        }

        return $url;
    }

    /**
     * @return string
     */
    public function getCourseDocumentRelativeWebPath()
    {
        $url = null;
        if ($this->allow()) {
            $directory = $this->getCourseDirectory();
            $url = api_get_path(REL_COURSE_PATH).$directory.'/document/';
        }

        return $url;
    }

    /**
     * @return string
     */
    public function getCourseDocumentWebPath()
    {
        $url = null;
        if ($this->allow()) {
            $directory = $this->getCourseDirectory();
            $url = api_get_path(WEB_COURSE_PATH).$directory.'/document/';
        }

        return $url;
    }

    /**
     * @return string
     */
    public function getCourseDirectory()
    {
        return $this->connector->course['directory'];
    }

    /**
     * {@inheritdoc}
     */
    public function upload($fp, $dst, $name, $tmpname, $hashes = [])
    {
        // Needed to load course information in elfinder
        $this->setConnectorFromPlugin();

        if ($this->allowToEdit()) {
            // upload file by elfinder.
            $size = filesize($tmpname);

            // check the max filesize.
            $maxFileSize = getIniMaxFileSizeInBytes();
            if ($maxFileSize > 0 && $size > $maxFileSize) {
                $errorUploadMessage = get_lang('FileSizeIsTooBig').' '.get_lang('MaxFileSize').' : '.getIniMaxFileSizeInBytes(true);
                $this->addError($errorUploadMessage);

                return false;
            }

            $maxSpace = \DocumentManager::get_course_quota($this->connector->course['code']);
            // Check if there is enough space to save the file.
            if (!\DocumentManager::enough_space($size, $maxSpace)) {
                return false;
            }

            $result = parent::upload($fp, $dst, $name, $tmpname);
            $name = $result['name'];
            $filtered = \URLify::filter($result['name'], 80, '', true);

            if (strcmp($name, $filtered) != 0) {
                $result = $this->customRename($result['hash'], $filtered);
            }

            $realPath = $this->realpath($result['hash']);
            if (!empty($realPath)) {
                // Getting file info
                //$info = $elFinder->exec('file', array('target' => $file['hash']));
                /** @var elFinderVolumeLocalFileSystem $volume */
                //$volume = $info['volume'];
                //$root = $volume->root();
                //var/www/chamilogits/data/courses/NEWONE/document
                $realPathRoot = $this->getCourseDocumentSysPath();

                // Removing course path
                $realPath = str_replace($realPathRoot, '/', $realPath);
                add_document(
                    $this->connector->course,
                    $realPath,
                    'file',
                    (int) $result['size'],
                    $result['name']
                );
            }

            return $result;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function rm($hash)
    {
        // elfinder does not delete the file
        //parent::rm($hash);
        $this->setConnectorFromPlugin();

        if ($this->allowToEdit()) {
            $path = $this->decode($hash);
            $stat = $this->stat($path);
            $stat['realpath'] = $path;
            $this->removed[] = $stat;

            $realFilePath = $path;
            $coursePath = $this->getCourseDocumentSysPath();
            $filePath = str_replace($coursePath, '/', $realFilePath);

            \DocumentManager::delete_document(
                $this->connector->course,
                $filePath,
                $coursePath
            );

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function allow()
    {
        //if ($this->connector->security->isGranted('ROLE_ADMIN')) {
        if (api_is_anonymous()) {
            return false;
        }

        $isAllowedToEdit = api_is_allowed_to_edit();

        $block = api_get_configuration_value('block_editor_file_manager_for_students');
        if ($block && !$isAllowedToEdit) {
            return false;
        }

        if (isset($this->connector->course) && !empty($this->connector->course)) {
            $isDocumentsToolVisible = CourseHome::getToolVisibility(
                TOOL_DOCUMENT,
                api_get_course_int_id(),
                api_get_session_id()
            );

            if (!$isDocumentsToolVisible && !$isAllowedToEdit) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Allow to upload/delete folder or files.
     *
     * @return bool
     */
    public function allowToEdit()
    {
        $allow = $this->allow();

        return $allow && api_is_allowed_to_edit(null, true);
    }

    /**
     * {@inheritdoc}
     */
    public function mkdir($path, $name)
    {
        // Needed to load course information in elfinder
        $this->setConnectorFromPlugin();

        if ($this->allowToEdit() === false) {
            return false;
        }

        $name = api_replace_dangerous_char($name);
        $result = parent::mkdir($path, $name);

        if ($result && isset($result['hash'])) {
            $_course = $this->connector->course;
            $realPathRoot = $this->getCourseDocumentSysPath();
            $realPath = $this->realpath($result['hash']);

            // Removing course path
            $newPath = str_replace($realPathRoot, '/', $realPath);
            $documentId = add_document(
                $_course,
                $newPath,
                'folder',
                0,
                $name,
                null,
                0,
                true,
                api_get_group_id(),
                api_get_session_id(),
                api_get_user_id()
            );

            if (empty($documentId)) {
                $this->rm($result['hash']);

                return false;
            }

            return $result;
        }

        return false;
    }

    /**
     * @param string $attr
     * @param string $path
     * @param $data
     * @param CourseDriver $volume
     */
    /*public function access($attr, $path, $data, $volume)
    {
        error_log($path);
        return true;
        if ($path == $this->coursePath) {

            return true;
        }

        $allowToEdit = $this->allowToEdit();
        if ($allowToEdit) {
            return true;
        }

        $path = str_replace($this->coursePath, '', $path);
        $documentId = \DocumentManager::get_document_id($this->connector->course, $path);

        if ($documentId) {

            $result = \DocumentManager::is_visible_by_id(
                $documentId,
                $this->connector->course,
                api_get_session_id(),
                api_get_user_id()
            );
            return false;
        }

        return false;
    }*/
}
