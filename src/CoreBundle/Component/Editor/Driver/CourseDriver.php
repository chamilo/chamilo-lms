<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\Driver;

use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class CourseDriver.
 */
class CourseDriver extends Driver implements DriverInterface
{
    public $name = 'CourseDriver';
    public $visibleFiles = [];

    /**
     * Setups the folder.
     */
    public function setup()
    {
        $userId = api_get_user_id();
        $userInfo = api_get_user_info();
        $sessionId = api_get_session_id();
        $course = $this->connector->course;

        if (!empty($course)) {
            $coursePath = api_get_path(SYS_COURSE_PATH);
            $courseDir = $course->getDirectory().'/document';
            $baseDir = $coursePath.$courseDir;
            $courseInfo = $this->getCourseInfoArray();

            // Creates shared folder
            if (!file_exists($baseDir.'/shared_folder')) {
                $title = get_lang('Folders of users');
                $folderName = '/shared_folder';
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
        if ($this->connector->course && $this->allow()) {
            $courseCode = $this->connector->course->getCode();
            $alias = $courseCode.' '.get_lang('Documents');
            $userId = $this->connector->user->getId();

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

            $courseInfo = $this->getCourseInfoArray();

            $foldersToHide = \DocumentManager::get_all_document_folders(
                $courseInfo,
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

    public function getCourseDirectory(): string
    {
        return $this->connector->course->getDirectory();
    }

    /**
     * {@inheritdoc}
     */
    public function upload($fp, $dst, $name, $tmpname, $hashes = [])
    {
        // Needed to load course information in elfinder
        $this->setConnectorFromPlugin();

        if ($this->allowToEdit()) {
            $directoryParentId = isset($_REQUEST['directory_parent_id']) ? $_REQUEST['directory_parent_id'] : 0;
            $currentDirectory = '';
            if (empty($directoryParentId)) {
                $currentDirectory = isset($_REQUEST['curdirpath']) ? $_REQUEST['curdirpath'] : '';
            } else {
                $documentData = \DocumentManager::get_document_data_by_id($directoryParentId, api_get_course_id());
                if ($documentData) {
                    $currentDirectory = $documentData['path'];
                }
            }

            if (!empty($_FILES)) {
                $request = Container::getRequest();
                $fileList = $request->files->get('upload');
                /** @var UploadedFile $file */
                foreach ($fileList as $file) {
                    $fileInfo = $file->getFileInfo();
                    $item = [
                        'files' => [
                            'tmp_name' => $fileInfo->getPathname(),
                            'error' => $file->getError(),
                            'size' => $file->getSize(),
                        ],
                    ];
                    \DocumentManager::upload_document(
                        $item,
                        $currentDirectory,
                        '',
                        '', // comment
                        false,
                        'rename',
                        false,
                        false,
                        'files',
                        true,
                        $directoryParentId,
                        $file
                    );
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function rm($hash)
    {
        $this->setConnectorFromPlugin();

        if ($this->allowToEdit()) {
            $path = $this->decode($hash);
            $stat = $this->stat($path);
            $stat['realpath'] = $path;
            $this->removed[] = $stat;

            $realFilePath = $path;
            $coursePath = $this->getCourseDocumentSysPath();
            $filePath = str_replace($coursePath, '/', $realFilePath);
            $courseInfo = $this->getCourseInfoArray();

            \DocumentManager::delete_document(
                $courseInfo,
                $filePath,
                $coursePath
            );

            return true;
        }

        return false;
    }

    public function getCourseInfoArray(): array
    {
        return [
            'real_id' => $this->connector->course->getId(),
            'code' => $this->connector->course->getCode(),
        ];
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

        $block = api_get_configuration_value('block_editor_file_manager_for_students');
        if ($block && !api_is_allowed_to_edit()) {
            return false;
        }

        if (isset($this->connector->course) && !empty($this->connector->course)) {
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

        if (false === $this->allowToEdit()) {
            return false;
        }

        $result = parent::mkdir($path, $name);

        if ($result && isset($result['hash'])) {
            $realPathRoot = $this->getCourseDocumentSysPath();
            $realPath = $this->realpath($result['hash']);

            // Removing course path
            $newPath = str_replace($realPathRoot, '/', $realPath);
            $documentId = \DocumentManager::addDocument(
                $this->getCourseInfoArray(),
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

    public function mount(array $opts)
    {
    }

    protected function getParents($path)
    {
        $parents = [];

        while ($path) {
            if ($file = $this->stat($path)) {
                array_unshift($parents, $path);
                $path = isset($file['phash']) ? $this->decode($file['phash']) : false;
            }
        }

        if (count($parents)) {
            array_pop($parents);
        }

        return $parents;
    }

    protected function _path($path)
    {
        if (false == ($file = $this->stat($path))) {
            return '';
        }

        $parentsIds = $this->getParents($path);
        $path = '';
        foreach ($parentsIds as $id) {
            $dir = $this->stat($id);
            $path .= $dir['name'].$this->separator;
        }

        return $path.$file['name'];
    }

    /*
     * @param string $attr
     * @param string $path
     * @param $data
     * @param CourseDriver $volume
     */
    /*public function access($attr, $path, $data, $volume)
    {
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
