<?php
/* For licensing terms, see /license.txt */

final class CcResourceLocation
{
    /**
     * Root directory.
     *
     * @var string
     */
    private $rootdir = null;
    /**
     * new directory.
     *
     * @var string
     */
    private $dir = null;
    /**
     * Full precalculated path.
     *
     * @var string
     */
    private $fullpath = null;

    /**
     * ctor.
     *
     * @param string $rootdir - path to the containing directory
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function __construct($rootdir)
    {
        $rdir = realpath($rootdir);
        if (empty($rdir)) {
            throw new InvalidArgumentException('Invalid path!');
        }
        $dir = CcHelpers::randomdir($rdir, 'i_');
        if ($dir === false) {
            throw new RuntimeException('Unable to create directory!');
        }
        $this->rootdir = $rdir;
        $this->dir = $dir;
        $this->fullpath = $rdir.DIRECTORY_SEPARATOR.$dir;
    }

    /**
     * Newly created directory.
     *
     * @return string
     */
    public function dirname($endseparator = false)
    {
        return $this->dir.($endseparator ? '/' : '');
    }

    /**
     * Full path to the new directory.
     *
     * @return string
     */
    public function fullpath($endseparator = false)
    {
        return $this->fullpath.($endseparator ? DIRECTORY_SEPARATOR : '');
    }

    /**
     * Returns containing dir.
     *
     * @return string
     */
    public function rootdir($endseparator = false)
    {
        return $this->rootdir.($endseparator ? DIRECTORY_SEPARATOR : '');
    }
}
