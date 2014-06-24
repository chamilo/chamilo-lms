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
 * Styles set manager exception.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class StylesSetManagerException extends Exception
{
    /**
     * Gets the "STYLES SET DOES NOT EXIST" exception.
     *
     * @param string $name The invalid CKEditor styles set name.
     *
     * @return \Ivory\CKEditorBundle\Exception\StylesSetManagerException The "STYLES SET DOES NOT EXIST" exception.
     */
    public static function stylesSetDoesNotExist($name)
    {
        return new static(sprintf('The CKEditor styles set "%s" does not exist.', $name));
    }
}
