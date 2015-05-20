<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\Driver;

/**
 * Class CourseDriver
 *
 * @package Chamilo\CoreBundle\Component\Editor\Driver
 *
 */
class CourseDriver extends Driver implements DriverInterface
{
    public $name = 'CourseDriver';

    /**
     * Setups the folder
     */
    public function setup()
    {
        $userId = api_get_user_id();
        $userInfo = api_get_user_info();
        $sessionId = api_get_session_id();

        $courseInfo = $this->connector->course;

        if (!empty($courseInfo)) {

            $coursePath = api_get_path(SYS_COURSE_PATH);
            $courseDir = $courseInfo['directory'] . '/document';
            $baseDir = $coursePath . $courseDir;

            // Creates shared folder

            if (!file_exists($baseDir . '/shared_folder')) {
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
            if (!file_exists($baseDir . '/shared_folder/sf_user_' . $userId)) {
                $title = $userInfo['complete_name'];
                $folderName = '/shared_folder/sf_user_' . $userId;
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

            return array(
                'driver' => 'CourseDriver',
                'path' => $this->getCourseDocumentSysPath(),
                'URL' => $this->getCourseDocumentRelativeWebPath(),
                'accessControl' => array($this, 'access'),
                'alias' => $alias,
                'attributes' => array(
                    // Hide shared_folder
                    array(
                        'pattern' => '/shared_folder/',
                        'read' => false,
                        'write' => false,
                        'hidden' => true,
                        'locked' => false
                    ),
                )
            );
        }

        return array();
    }

    /**
     * This is the absolute document course path like
     * /var/www/portal/data/courses/XXX/document/
     * @return string
     */
    public function getCourseDocumentSysPath()
    {
        $url = null;
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
     *
     * @return string
     */
    public function getCourseDirectory()
    {
        return $this->connector->course['directory'];
    }

    /**
     * {@inheritdoc}
     */
    public function upload($fp, $dst, $name, $tmpname)
    {
        $this->setConnectorFromPlugin();

        if ($this->allow()) {

            // upload file by elfinder.
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
                    intval($result['size']),
                    $result['name']
                );
            }
            //error_log(print_r($this->error(),1));

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

        if ($this->allow()) {

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
        return
            isset($this->connector->course) &&
            !empty($this->connector->course) &&
            !api_is_anonymous()
        ;
    }
}
