<?php

/*
 * This file is part of the Ivory CKEditor package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\CKEditorBundle\Exception;

/**
 * Plugin manager exception.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class PluginManagerException extends Exception
{
    /**
     * Gets the "PLUGIN DOES NOT EXIST" exception.
     *
     * @param string $name The invalid CKEditor plugin name.
     *
     * @return \Ivory\CKEditorBundle\Exception\PluginManagerException The "PLUGIN DOES NOT EXIST" exception.
     */
    public static function pluginDoesNotExist($name)
    {
        return new static(sprintf('The CKEditor plugin "%s" does not exist.', $name));
    }
}
