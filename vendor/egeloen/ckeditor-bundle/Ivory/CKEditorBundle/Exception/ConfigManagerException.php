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
 * Config manager exception.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class ConfigManagerException extends Exception
{
    /**
     * Gets the "CONFIG DOES NOT EXIST" exception.
     *
     * @param string $name The invalid CKEditor config name.
     *
     * @return \Ivory\CKEditorBundle\Exception\ConfigManagerException The "CONFIG DOES NOT EXIST" exception.
     */
    public static function configDoesNotExist($name)
    {
        return new static(sprintf('The CKEditor config "%s" does not exist.', $name));
    }
}
