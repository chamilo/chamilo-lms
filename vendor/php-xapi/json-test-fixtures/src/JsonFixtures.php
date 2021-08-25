<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Fixtures\Json;

/**
 * JSON encoded fixtures.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
abstract class JsonFixtures
{
    const DIRECTORY = null;

    /**
     * Loads a JSON encoded fixture from the file system
     *
     * @param string $file The fixture to load
     *
     * @return string The JSON encoded fixture
     */
    protected static function load($file)
    {
        if (null !== static::DIRECTORY) {
            return file_get_contents(__DIR__.'/../data/'.static::DIRECTORY.'/'.$file.'.json');
        }

        return file_get_contents(__DIR__.'/../data/'.$file.'.json');
    }
}
