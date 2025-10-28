<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Utils;

use InvalidArgumentException;
use RuntimeException;

use const DIRECTORY_SEPARATOR;

final class CcResourceLocation
{
    /**
     * Root directory.
     *
     * @var string
     */
    private $rootdir;

    /**
     * new directory.
     *
     * @var string
     */
    private $dir;

    /**
     * Full precalculated path.
     *
     * @var string
     */
    private $fullpath;

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
        if (false === $dir) {
            throw new RuntimeException('Unable to create directory!');
        }
        $this->rootdir = $rdir;
        $this->dir = $dir;
        $this->fullpath = $rdir.DIRECTORY_SEPARATOR.$dir;
    }

    /**
     * Newly created directory.
     *
     * @param mixed $endseparator
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
     * @param mixed $endseparator
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
     * @param mixed $endseparator
     *
     * @return string
     */
    public function rootdir($endseparator = false)
    {
        return $this->rootdir.($endseparator ? DIRECTORY_SEPARATOR : '');
    }
}
