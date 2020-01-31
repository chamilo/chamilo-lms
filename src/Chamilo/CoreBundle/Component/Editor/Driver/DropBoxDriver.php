<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\Driver;

use Chamilo\CoreBundle\Component\Editor\Connector;
use Chamilo\CoreBundle\Entity\CDropboxFile;

/**
 * Class DropBoxDriver.
 *
 * @todo finish implementation
 *
 * @package Chamilo\CoreBundle\Component\Editor\Driver
 */
class DropBoxDriver extends \elFinderVolumeMySQL implements DriverInterface
{
    /** @var string */
    public $name = 'DropBoxDriver';

    /** @var Connector */
    public $connector;

    /**
     * DropBoxDriver constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Gets driver name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets driver name.
     *
     * @param string
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set connector.
     */
    public function setConnector(Connector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * @return array
     */
    public function getAppPluginOptions()
    {
        return $this->getOptionsPlugin('chamilo');
    }

    /**
     * @return Connector
     */
    public function setConnectorFromPlugin()
    {
        $options = $this->getAppPluginOptions();
        $this->setConnector($options['connector']);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        if ($this->connector->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            /** @var \Chamilo\CoreBundle\Entity\Repository\UserRepository $repository */
            /*$repository = $this->connector->entityManager->getRepository('Chamilo\UserBundle\Entity\User');
            $courses = $repository->getCourses($this->connector->user);*/

            //if (!empty($courses)) {
            $userId = $this->connector->user->getUserId();
            if (!empty($userId)) {
                return [
                    'driver' => 'DropBoxDriver',
                    'path' => '1',
                    'alias' => 'dropbox',
                    'tmpPath' => $this->connector->paths['path.temp'],
                    //'alias' => $courseInfo['code'].' personal documents',
                    //'URL' => $this->getCourseDocumentRelativeWebPath().$path,
                    'accessControl' => 'access',
                ];
            }
            //}
        }
    }

    /**
     * Close connection.
     *
     * @author Dmitry (dio) Levashov
     */
    public function umount()
    {
        return true;
    }

    protected function init()
    {
        $this->updateCache($this->options['path'], $this->_stat($this->options['path']));

        return true;
    }

    /**
     * Set tmp path.
     *
     * @author Dmitry (dio) Levashov
     */
    protected function configure()
    {
        parent::configure();

        if (($tmp = $this->options['tmpPath'])) {
            if (!file_exists($tmp)) {
                if (@mkdir($tmp)) {
                    @chmod($tmp, $this->options['tmbPathMode']);
                }
            }

            $this->tmpPath = is_dir($tmp) && is_writable($tmp) ? $tmp : false;
        }

        if (!$this->tmpPath && $this->tmbPath && $this->tmbPathWritable) {
            $this->tmpPath = $this->tmbPath;
        }

        $this->mimeDetect = 'internal';
    }

    /* FS API */

    /**
     * Cache dir contents.
     *
     * @param string $path dir path
     *
     * @author Dmitry Levashov
     */
    protected function cacheDir($path)
    {
        $this->setConnectorFromPlugin();
        $posts = $this->connector->user->getDropBoxReceivedFiles();
        $this->dirsCache[$path] = [];

        if (!empty($posts)) {
            foreach ($posts as $post) {
                /** @var CDropboxFile $file */
                $file = $post->getFile();

                $data = $this->transformFileInStat($file);
                $id = $data['id'];
                if (($stat = $this->updateCache($id, $data)) && empty($stat['hidden'])) {
                    $this->dirsCache[$path][] = $id;
                }
            }

            return $this->dirsCache[$path];
        }

        return $this->dirsCache[$path];
    }

    /* file stat */

    /**
     * Return stat for given path.
     * Stat contains following fields:
     * - (int)    size    file size in b. required
     * - (int)    ts      file modification time in unix time. required
     * - (string) mime    mimetype. required for folders, others - optionally
     * - (bool)   read    read permissions. required
     * - (bool)   write   write permissions. required
     * - (bool)   locked  is object locked. optionally
     * - (bool)   hidden  is object hidden. optionally
     * - (string) alias   for symlinks - link target path relative to root path. optionally
     * - (string) target  for symlinks - link target path. optionally.
     *
     * If file does not exists - returns empty array or false.
     *
     * @param string $path file path
     *
     * @return array|false
     *
     * @author Dmitry (dio) Levashov
     */
    protected function _stat($path)
    {
        $this->setConnectorFromPlugin();

        $userId = $this->connector->user->getUserId();
        $criteria = [];
        $criteria['uploaderId'] = $userId;

        if ($path != 1) {
            $criteria['filename'] = $path;
            $criteria = ['filename' => $path];
        } else {
            return $this->returnDirectory();
        }

        $file = $this->connector->entityManager->getRepository('Chamilo\CoreBundle\Entity\CDropboxFile')->findOneBy($criteria);

        if ($file) {
            $stat = $this->transformFileInStat($file);

            return $stat;
        }

        return [];
    }

    /**
     * Return array of parents paths (ids).
     *
     * @param int $path file path (id)
     *
     * @return array
     *
     * @author Dmitry (dio) Levashov
     */
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

    /**
     * Return correct file path for LOAD_FILE method.
     *
     * @param string $path file path (id)
     *
     * @return string
     *
     * @author Troex Nevelin
     */
    protected function loadFilePath($path)
    {
        $realPath = realpath($path);
        if (DIRECTORY_SEPARATOR == '\\') { // windows
            $realPath = str_replace('\\', '\\\\', $realPath);
        }

        return $this->db->real_escape_string($realPath);
    }

    /**
     * Recursive files search.
     *
     * @param string $path  dir path
     * @param string $q     search string
     * @param array  $mimes
     *
     * @return array
     *
     * @author Dmitry (dio) Levashov
     */
    protected function doSearch($path, $q, $mimes)
    {
        return [];
    }

    /* paths/urls */

    /**
     * Return parent directory path.
     *
     * @param string $path file path
     *
     * @return string
     *
     * @author Dmitry (dio) Levashov
     */
    protected function _dirname($path)
    {
        return ($stat = $this->stat($path)) ? ($stat['phash'] ? $this->decode($stat['phash']) : $this->root) : false;
    }

    /**
     * Return file name.
     *
     * @param string $path file path
     *
     * @return string
     *
     * @author Dmitry (dio) Levashov
     */
    protected function _basename($path)
    {
        return ($stat = $this->stat($path)) ? $stat['name'] : false;
    }

    /**
     * Return normalized path, this works the same as os.path.normpath() in Python.
     *
     * @param string $path path
     *
     * @return string
     *
     * @author Troex Nevelin
     */
    protected function _normpath($path)
    {
        return $path;
    }

    /**
     * Return file path related to root dir.
     *
     * @param string $path file path
     *
     * @return string
     *
     * @author Dmitry (dio) Levashov
     */
    protected function _relpath($path)
    {
        return $path;
    }

    /**
     * Convert path related to root dir into real path.
     *
     * @param string $path file path
     *
     * @return string
     *
     * @author Dmitry (dio) Levashov
     */
    protected function _abspath($path)
    {
        return $path;
    }

    /**
     * Return fake path started from root dir.
     *
     * @param string $path file path
     *
     * @return string
     *
     * @author Dmitry (dio) Levashov
     */
    protected function _path($path)
    {
        if (($file = $this->stat($path)) == false) {
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

    /**
     * Return true if $path is children of $parent.
     *
     * @param string $path   path to check
     * @param string $parent parent path
     *
     * @return bool
     *
     * @author Dmitry (dio) Levashov
     */
    protected function _inpath($path, $parent)
    {
        return $path == $parent
            ? true
            : in_array($parent, $this->getParents($path));
    }

    /**
     * Return true if path is dir and has at least one childs directory.
     *
     * @param string $path dir path
     *
     * @return bool
     *
     * @author Dmitry (dio) Levashov
     */
    protected function _subdirs($path)
    {
        return ($stat = $this->stat($path)) && isset($stat['dirs']) ? $stat['dirs'] : false;
    }

    /**
     * Return object width and height
     * Usualy used for images, but can be realize for video etc...
     *
     * @param string $path file path
     * @param string $mime file mime type
     *
     * @return string
     *
     * @author Dmitry (dio) Levashov
     */
    protected function _dimensions($path, $mime)
    {
        return ($stat = $this->stat($path)) && isset($stat['width']) && isset($stat['height']) ? $stat['width'].'x'.$stat['height'] : '';
    }

    /* file/dir content */

    /**
     * Return files list in directory.
     *
     * @param string $path dir path
     *
     * @return array
     *
     * @author Dmitry (dio) Levashov
     */
    protected function _scandir($path)
    {
        return isset($this->dirsCache[$path])
            ? $this->dirsCache[$path]
            : $this->cacheDir($path);
    }

    /**
     * Open file and return file pointer.
     *
     * @param string $path file path
     * @param string $mode open file mode (ignored in this driver)
     *
     * @return resource|false
     *
     * @author Dmitry (dio) Levashov
     */
    protected function _fopen($path, $mode = 'rb')
    {
        $fp = $this->tmbPath
            ? @fopen($this->tmpname($path), 'w+')
            : @tmpfile();

        if ($fp) {
            if (($res = $this->query('SELECT content FROM '.$this->tbf.' WHERE id="'.$path.'"'))
                && ($r = $res->fetch_assoc())) {
                fwrite($fp, $r['content']);
                rewind($fp);

                return $fp;
            } else {
                $this->_fclose($fp, $path);
            }
        }

        return false;
    }

    /**
     * Close opened file.
     *
     * @param resource $fp file pointer
     *
     * @return bool
     *
     * @author Dmitry (dio) Levashov
     */
    protected function _fclose($fp, $path = '')
    {
        @fclose($fp);
        if ($path) {
            @unlink($this->tmpname($path));
        }
    }

    /*  file/dir manipulations */

    /**
     * Create dir and return created dir path or false on failed.
     *
     * @param string $path parent dir path
     * @param string $name new directory name
     *
     * @return string|bool
     *
     * @author Dmitry (dio) Levashov
     */
    protected function _mkdir($path, $name)
    {
        return $this->make($path, $name, 'directory') ? $this->_joinPath($path, $name) : false;
    }

    /**
     * {@inheritdoc}
     */
    protected function _mkfile($path, $name)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function _symlink($target, $path, $name)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function _copy($source, $targetDir, $name)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function _move($source, $targetDir, $name)
    {
        return false;
    }

    /**
     * Remove file.
     *
     * @param string $path file path
     *
     * @return bool
     *
     * @author Dmitry (dio) Levashov
     */
    protected function _unlink($path)
    {
        return false;

        return $this->query(sprintf('DELETE FROM %s WHERE id=%d AND mime!="directory" LIMIT 1', $this->tbf, $path)) && $this->db->affected_rows;
    }

    /**
     * Remove dir.
     *
     * @param string $path dir path
     *
     * @return bool
     *
     * @author Dmitry (dio) Levashov
     */
    protected function _rmdir($path)
    {
        return false;

        return $this->query(sprintf('DELETE FROM %s WHERE id=%d AND mime="directory" LIMIT 1', $this->tbf, $path)) && $this->db->affected_rows;
    }

    /**
     * undocumented function.
     *
     * @author Dmitry Levashov
     */
    protected function _setContent($path, $fp)
    {
        rewind($fp);
        $fstat = fstat($fp);
        $size = $fstat['size'];
    }

    /**
     * {@inheritdoc}
     */
    protected function _save($fp, $dir, $name, $stat)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function _getContents($path)
    {
        return false;
        //return ($res = $this->query(sprintf('SELECT content FROM %s WHERE id=%d', $this->tbf, $path))) && ($r = $res->fetch_assoc()) ? $r['content'] : false;
    }

    /**
     * Write a string to a file.
     *
     * @param string $path    file path
     * @param string $content new file content
     *
     * @return bool
     *
     * @author Dmitry (dio) Levashov
     */
    protected function _filePutContents($path, $content)
    {
        return false;
        //return $this->query(sprintf('UPDATE %s SET content="%s", size=%d, mtime=%d WHERE id=%d LIMIT 1', $this->tbf, $this->db->real_escape_string($content), strlen($content), time(), $path));
    }

    /**
     * {@inheritdoc}
     */
    protected function _checkArchivers()
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function _unpack($path, $arc)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function _findSymlinks($path)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function _extract($path, $arc)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function _archive($dir, $files, $name, $arc)
    {
        return false;
    }

    /**
     * @return array
     */
    private function returnDirectory()
    {
        return [
            //'id' => $file->getId().$file->getCId(),
            'name' => 'Dropbox',
            //'ts' => $file->getUploadDate(),
            'mime' => 'directory',
            'read' => true,
            'write' => true,
            'locked' => false,
            'hidden' => false,
            'dirs' => 0,
        ];
    }

    /**
     * @return array
     */
    private function transformFileInStat(CDropboxFile $file)
    {
        $stat = [
            'id' => $file->getId().$file->getCId(),
            'name' => $file->getFilename(),
            'ts' => $file->getUploadDate(),
            'mime' => 'directory',
            'read' => true,
            'write' => false,
            'locked' => false,
            'hidden' => false,
            'width' => 100,
            'height' => 100,
            'dirs' => 0,
        ];

        return $stat;

        /*
        if ($stat['parent_id']) {
            $stat['phash'] = $this->encode($stat['parent_id']);
        }
        if ($stat['mime'] == 'directory') {
            unset($stat['width']);
            unset($stat['height']);
        } else {
            unset($stat['dirs']);
        }
        unset($stat['id']);
        unset($stat['parent_id']);
        */
    }
}
