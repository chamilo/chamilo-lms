<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Zippy\Resource;

class Resource
{
    private $original;
    private $target;

    /**
     * Constructor
     *
     * @param String $original
     * @param String $target
     */
    public function __construct($original, $target)
    {
        $this->original = $original;
        $this->target = $target;
    }

    /**
     * Returns the target
     *
     * @return String
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Returns the original path
     *
     * @return String
     */
    public function getOriginal()
    {
        return $this->original;
    }

    /**
     * Returns wheter the resource can be processed in place given a context or not.
     *
     * For example :
     *   - /path/to/file1 can be processed to file1 in /path/to context
     *   - /path/to/subdir/file2 can be processed to subdir/file2 in /path/to context
     *
     * @param String $context
     *
     * @return Boolean
     */
    public function canBeProcessedInPlace($context)
    {
        if (!is_string($this->original)) {
            return false;
        }

        $data = parse_url($this->original);

        if (!isset($data['path'])) {
            return false;
        }

        return sprintf('%s/%s', rtrim($context, '/'), $this->target) === $data['path'];
    }
}
