<?php

/**
 * This file is part of the PHPExiftool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool;

use Doctrine\Common\Collections\ArrayCollection;
use PHPExiftool\Exception\EmptyCollectionException;
use PHPExiftool\Exception\LogicException;
use PHPExiftool\Exception\RuntimeException;
use Psr\Log\LoggerInterface;

/**
 *
 * Exiftool Reader, inspired by Symfony2 Finder.
 *
 * It scans files and directories, and provide an iterator on the FileEntities
 * generated based on the results.
 *
 * Example usage:
 *
 *      $Reader = new Reader();
 *
 *      $Reader->in('/path/to/directory')
 *              ->exclude('tests')
 *              ->extensions(array('jpg', 'xml));
 *
 *      //Throws an exception if no file found
 *      $first = $Reader->first();
 *
 *      //Returns null if no file found
 *      $first = $Reader->getOneOrNull();
 *
 *      foreach($Reader as $entity)
 *      {
 *          //Do your logic with FileEntity
 *      }
 *
 *
 * @todo implement match conditions (-if EXPR) (name or metadata tag)
 * @todo implement match filter
 * @todo implement sort
 * @todo implement -l
 *
 * @author Romain Neutron <imprec@gmail.com>
 */
class Reader implements \IteratorAggregate
{
    protected $files = array();
    protected $dirs = array();
    protected $excludeDirs = array();
    protected $extensions = array();
    protected $extensionsToggle = null;
    protected $followSymLinks = false;
    protected $recursive = true;
    protected $ignoreDotFile = false;
    protected $sort = array();
    protected $parser;
    protected $exiftool;
    protected $timeout = 60;

    /**
     *
     * @var ArrayCollection
     */
    protected $collection;
    protected $readers = array();

    /**
     *  Constructor
     */
    public function __construct(Exiftool $exiftool, RDFParser $parser)
    {
        $this->exiftool = $exiftool;
        $this->parser = $parser;
    }

    public function __destruct()
    {
        $this->parser = null;
        $this->collection = null;
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function reset()
    {
        $this->files
            = $this->dirs
            = $this->excludeDirs
            = $this->extensions
            = $this->sort
            = $this->readers = array();

        $this->recursive = true;
        $this->ignoreDotFile = $this->followSymLinks = false;
        $this->extensionsToggle = null;

        return $this;
    }

    /**
     * Implements \IteratorAggregate Interface
     *
     * @return \Iterator
     */
    public function getIterator()
    {
        return $this->all()->getIterator();
    }

    /**
     * Add files to scan
     *
     * Example usage:
     *
     *      // Will scan 3 files : dc00.jpg in CWD and absolute
     *      // paths /tmp/image.jpg and /tmp/raw.CR2
     *      $Reader ->files('dc00.jpg')
     *              ->files(array('/tmp/image.jpg', '/tmp/raw.CR2'))
     *
     * @param  string|array $files The files
     * @return Reader
     */
    public function files($files)
    {
        $this->resetResults();
        $this->files = array_merge($this->files, (array) $files);

        return $this;
    }

    /**
     * Add dirs to scan
     *
     * Example usage:
     *
     *      // Will scan 3 dirs : documents in CWD and absolute
     *      // paths /usr and /var
     *      $Reader ->in('documents')
     *              ->in(array('/tmp', '/var'))
     *
     * @param  string|array $dirs The directories
     * @return Reader
     */
    public function in($dirs)
    {
        $this->resetResults();
        $this->dirs = array_merge($this->dirs, (array) $dirs);

        return $this;
    }

    /**
     * Append a reader to this one.
     * Finale result will be the sum of the current reader and all appended ones.
     *
     * @param  Reader $reader The reader to append
     * @return Reader
     */
    public function append(Reader $reader)
    {
        $this->resetResults();
        $this->readers[] = $reader;

        return $this;
    }

    /**
     * Sort results with one or many criteria
     *
     * Example usage:
     *
     *      // Will sort by directory then filename
     *      $Reader ->in('documents')
     *              ->sort(array('directory', 'filename'))
     *
     *      // Will sort by filename
     *      $Reader ->in('documents')
     *              ->sort('filename')
     *
     * @param  string|array $by
     * @return Reader
     */
    public function sort($by)
    {
        static $availableSorts = array(
        'directory', 'filename', 'createdate', 'modifydate', 'filesize'
        );

        foreach ((array) $by as $sort) {

            if ( ! in_array($sort, $availableSorts)) {
                continue;
            }
            $this->sort[] = $sort;
        }

        return $this;
    }

    /**
     * Exclude directories from scan
     *
     * Warning: only first depth directories can be excluded
     * Imagine a directory structure like below, With a scan in "root", only
     * sub1 or sub2 can be excluded, not subsub.
     *
     *      root
     *      ├── sub1
     *      └── sub2
     *          └── subsub
     *
     * Example usage:
     *
     *      // Will scan documents recursively, discarding documents/test
     *      $Reader ->in('documents')
     *              ->exclude(array('test'))
     *
     * @param  string|array $dirs The directories
     * @return Reader
     */
    public function exclude($dirs)
    {
        $this->resetResults();
        $this->excludeDirs = array_merge($this->excludeDirs, (array) $dirs);

        return $this;
    }

    /**
     * Restrict / Discard files based on extensions
     * Extensions are case insensitive
     *
     * @param  string|array   $extensions The list of extension
     * @param  Boolean        $restrict   Toggle restrict/discard method
     * @return Reader
     * @throws LogicException
     */
    public function extensions($extensions, $restrict = true)
    {
        $this->resetResults();

        if ( ! is_null($this->extensionsToggle)) {
            if ((boolean) $restrict !== $this->extensionsToggle) {
                throw new LogicException('You cannot restrict extensions AND exclude extension at the same time');
            }
        }

        $this->extensionsToggle = (boolean) $restrict;

        $this->extensions = array_merge($this->extensions, (array) $extensions);

        return $this;
    }

    /**
     * Toggle to enable follow Symbolic Links
     *
     * @return Reader
     */
    public function followSymLinks()
    {
        $this->resetResults();
        $this->followSymLinks = true;

        return $this;
    }

    /**
     * Ignore files starting with a dot (.)
     *
     * Folders starting with a dot are always exluded due to exiftool behaviour.
     * You should include them manually
     *
     * @return Reader
     */
    public function ignoreDotFiles()
    {
        $this->resetResults();
        $this->ignoreDotFile = true;

        return $this;
    }

    /**
     * Disable recursivity in directories scan.
     * If you only specify files, this toggle has no effect
     *
     * @return Reader
     */
    public function notRecursive()
    {
        $this->resetResults();
        $this->recursive = false;

        return $this;
    }

    /**
     * Return the first result. If no result available, null is returned
     *
     * @return FileEntity
     */
    public function getOneOrNull()
    {
        return count($this->all()) === 0 ? null : $this->all()->first();
    }

    /**
     * Return the first result. If no result available, throws an exception
     *
     * @return FileEntity
     * @throws EmptyCollectionException
     */
    public function first()
    {
        if (count($this->all()) === 0) {
            throw new EmptyCollectionException('Collection is empty');
        }

        return $this->all()->first();
    }

    /**
     * Perform the scan and returns all the results
     *
     * @return ArrayCollection
     */
    public function all()
    {
        if (! $this->collection) {
            $this->collection = $this->buildQueryAndExecute();
        }

        if ($this->readers) {
            $elements = $this->collection->toArray();

            $this->collection = null;

            foreach ($this->readers as $reader) {
                $elements = array_merge($elements, $reader->all()->toArray());
            }

            $this->collection = new ArrayCollection($elements);
        }

        return $this->collection;
    }

    public static function create(LoggerInterface $logger)
    {
        return new static(new Exiftool($logger), new RDFParser());
    }

    /**
     * Reset any computed result
     *
     * @return Reader
     */
    protected function resetResults()
    {
        $this->collection = null;

        return $this;
    }

    /**
     * Build the command returns an ArrayCollection of FileEntity
     *
     * @return ArrayCollection
     */
    protected function buildQueryAndExecute()
    {
        $result = '';

        try {
            $result = trim($this->exiftool->executeCommand($this->buildQuery(), $this->timeout));
        } catch (RuntimeException $e) {
            /**
             * In case no file found, an exit code 1 is returned
             */
            if (! $this->ignoreDotFile) {
                throw $e;
            }
        }

        if ($result === '') {
            return new ArrayCollection();
        }

        $this->parser->open($result);

        return $this->parser->ParseEntities();
    }

    /**
     * Compute raw exclude rules to simple ones, based on exclude dirs and search dirs
     *
     * @param  string           $rawExcludeDirs
     * @param  string           $rawDirs
     * @return array
     * @throws RuntimeException
     */
    protected function computeExcludeDirs($rawExcludeDirs, $rawSearchDirs)
    {
        $excludeDirs = array();

        foreach ($rawExcludeDirs as $excludeDir) {
            $found = false;
            /**
             * is this a relative path ?
             */
            foreach ($rawSearchDirs as $dir) {
                $currentPrefix = realpath($dir) . DIRECTORY_SEPARATOR;

                $supposedExcluded = str_replace($currentPrefix, '', realpath($currentPrefix . $excludeDir));

                if (! $supposedExcluded) {
                    continue;
                }

                if (strpos($supposedExcluded, DIRECTORY_SEPARATOR) === false) {
                    $excludeDirs[] = $supposedExcluded;
                    $found = true;
                    break;
                }
            }

            if ($found) {
                continue;
            }

            /**
             * is this an absolute path ?
             */
            $supposedExcluded = realpath($excludeDir);

            if ($supposedExcluded) {
                foreach ($rawSearchDirs as $dir) {
                    $searchDir = realpath($dir) . DIRECTORY_SEPARATOR;

                    $supposedRelative = str_replace($searchDir, '', $supposedExcluded);

                    if (strpos($supposedRelative, DIRECTORY_SEPARATOR) !== false) {
                        continue;
                    }

                    if (strpos($supposedExcluded, $searchDir) !== 0) {
                        continue;
                    }

                    if ( ! trim($supposedRelative)) {
                        continue;
                    }

                    $excludeDirs[] = $supposedRelative;
                    $found = true;
                    break;
                }
            }


            if (! $found) {
                throw new RuntimeException(sprintf("Invalid exclude dir %s ; Exclude dir is limited to the name of a directory at first depth", $excludeDir));
            }
        }

        return $excludeDirs;
    }

    /**
     * Build query from criterias
     *
     * @return string
     *
     * @throws LogicException
     */
    protected function buildQuery()
    {
        if (! $this->dirs && ! $this->files) {
            throw new LogicException('You have not set any files or directory');
        }

        $command = '-n -q -b -X -charset UTF8';

        if ($this->recursive) {
            $command .= ' -r';
        }

        if (!empty($this->extensions)) {
            if (! $this->extensionsToggle) {
                $extensionPrefix = ' --ext';
            } else {
                $extensionPrefix = ' -ext';
            }

            foreach ($this->extensions as $extension) {
                $command .= $extensionPrefix . ' ' . escapeshellarg($extension);
            }
        }

        if (! $this->followSymLinks) {
            $command .= ' -i SYMLINKS';
        }

        if ($this->ignoreDotFile) {
            $command .= " -if '\$filename !~ /^\./'";
        }

        foreach ($this->sort as $sort) {
            $command .= ' -fileOrder ' . $sort;
        }

        foreach ($this->computeExcludeDirs($this->excludeDirs, $this->dirs) as $excludedDir) {
            $command .= ' -i ' . escapeshellarg($excludedDir);
        }

        foreach ($this->dirs as $dir) {
            $command .= ' ' . escapeshellarg(realpath($dir));
        }

        foreach ($this->files as $file) {
            $command .= ' ' . escapeshellarg(realpath($file));
        }

        return $command;
    }
}
