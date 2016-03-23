<?php

/*
 * This file is part of PHP-SwfTools.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwfTools\Processor;

use SwfTools\Binary\DriverContainer;

abstract class File
{
    protected $container;

    /**
     * Build the File processor given the configuration
     *
     * @param DriverContainer $container
     */
    public function __construct(DriverContainer $container)
    {
        $this->container = $container;
    }

    /**
     * Change the extension of a pathname
     *
     * @example static::changePathnameExtension('/my/path/to/image.png', 'jpg') returns
     * '/my/path/to/image.jpg'
     *
     * @param string $pathname
     * @param string $extension
     *
     * @return string
     */
    protected static function changePathnameExtension($pathname, $extension)
    {
        return dirname($pathname) . '/' . pathinfo($pathname, PATHINFO_FILENAME) . '.' . $extension;
    }
}
