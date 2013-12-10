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
        if ($this->connector->course) {

            return array(
                'driver' => 'CourseDriver',
                'path' => $this->getCourseDocumentSysPath(),
                'startPath'  => '/',
                'URL' => $this->getCourseDocumentWebPath(),
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
     * @param array $file
     * @param array $args
     * @param Finder $elFinder
     */
    public function afterUpload($file, $args, $elFinder)
    {
        if ($file) {

            $name = $file['name'];
            $filtered = \URLify::filter($file['name'], 80);

            if (strcmp($name, $filtered) != 0) {
                $arg = array('target' => $file['hash'], 'name' => $filtered);
                $elFinder->exec('rename', $arg);
            }

            $realPath = $elFinder->realpath($file['hash']);

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
                    intval($file['size']),
                    $file['name']
                );
            }
        }
    }

    /**
     * @return string
     */
    public function getCourseDocumentSysPath()
    {
        $directory = $this->connector->course->getDirectory();
        $dataPath = $this->connector->paths['sys_data_path'];
        $url =  $dataPath.'courses/'.$directory.'/document/';
        return $url;
    }

    /**
     * @return string
     */
    public function getCourseDocumentWebPath()
    {
        $directory = $this->connector->course->getDirectory();
        $url =  api_get_path(REL_COURSE_PATH).$directory.'/document/';
        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function upload($fp, $dst, $name, $tmpname)
    {
        $result = parent::upload($fp, $dst, $name, $tmpname);
        return $result;
    }

    /**
     * @param array $file
     * @param array $args
     * @param Finder $elFinder
     */
    public function afterRm($file, $args, $elFinder)
    {
        $realFilePath = $file['realpath'];
        $coursePath = $this->connector->paths['sys_data_path'].'courses/'.$this->connector->course->getDirectory().'/document';

        $filePath = str_replace($coursePath, '', $realFilePath);
        \DocumentManager::delete_document($this->connector->course, $filePath, $coursePath);
    }

    /**
     * {@inheritdoc}
     */
    public function rm($hash)
    {
        // elfinder does not delete the file
        //parent::rm($hash);
        $path = $this->decode($hash);
        $stat = $this->stat($path);
        $stat['realpath'] = $path;
        $this->removed[] = $stat;
        return true;
    }
}
