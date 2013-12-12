<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Component\Editor\Driver;

use ChamiloLMS\Component\Editor\Finder;

/**
 * Class CourseDriver
 * @package ChamiloLMS\Component\Editor\Driver
 */
class CourseDriver extends Driver
{
    public $name = 'CourseDriver';

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        if (!empty($this->connector->course)) {

            return array(
                'driver' => 'CourseDriver',
                'path' => $this->getCourseDocumentSysPath(),
                'URL' => $this->getCourseDocumentWebPath(),
                'startPath'  => '/',
                'accessControl' => array($this, 'access'),
                'alias' => $this->connector->translator->trans('CourseDocument'),
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
    }

    /**
     * This is the absolute document course path like
     * /var/www/portal/data/courses/XXX/document/
     * @return string
     */
    public function getCourseDocumentSysPath()
    {
        $url = null;
        if (isset($this->connector->course)) {
            $directory = $this->connector->course->getDirectory();
            $dataPath = $this->connector->paths['sys_data_path'];
            $url = $dataPath.'courses/'.$directory.'/document/';
        }
        return $url;
    }

    /**
     * @return string
     */
    public function getCourseDocumentWebPath()
    {
        $url = null;
        if (isset($this->connector->course)) {
            $directory = $this->connector->course->getDirectory();
            $url = api_get_path(REL_COURSE_PATH).$directory.'/document/';
        }
        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function upload($fp, $dst, $name, $tmpname)
    {
        $this->setConnectorFromPlugin();

        // upload file by elfinder.
        $result = parent::upload($fp, $dst, $name, $tmpname);

        $name = $result['name'];
        $filtered = \URLify::filter($result['name'], 80);

        if (strcmp($name, $filtered) != 0) {
            /*$arg = array('target' => $file['hash'], 'name' => $filtered);
            $elFinder->exec('rename', $arg);*/
            $this->rename($result['hash'], $filtered);
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
            \FileManager::add_document(
                $this->connector->course,
                $realPath,
                'file',
                intval($result['size']),
                $result['name']
            );
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function rm($hash)
    {
        // elfinder does not delete the file
        //parent::rm($hash);
        $this->setConnectorFromPlugin();

        $path = $this->decode($hash);
        $stat = $this->stat($path);
        $stat['realpath'] = $path;
        $this->removed[] = $stat;

        $realFilePath = $path;
        $coursePath = $this->getCourseDocumentSysPath();
        $filePath = str_replace($coursePath, '', $realFilePath);
        \DocumentManager::delete_document($this->connector->course, $filePath, $coursePath);
        return true;
    }
}
