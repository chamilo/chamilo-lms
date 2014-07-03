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
 * Depencency injection exception.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class DependencyInjectionException extends Exception
{
    /**
     * Gets the "INVALID DEFAULT CONFIG" exception.
     *
     * @param string $name The default config name.
     *
     * @return \Ivory\CKEditorBundle\Exception\DependencyInjectionException The "INVALID DEFAULT CONFIG" exception.
     */
    public static function invalidDefaultConfig($name)
    {
        return new static(sprintf('The default config "%s" does not exist.', $name));
    }

    /**
     * Gets the "INVALID TOOLBAR" exception.
     *
     * @param string $toolbar The invalid toolbar.
     *
     * @return \Ivory\CKEditorBundle\Exception\DependencyInjectionException The "INVALID TOOLBAR" exception.
     */
    public static function invalidToolbar($toolbar)
    {
        return new static(sprintf('The toolbar "%s" does not exist.', $toolbar));
    }

    /**
     * Gets the "INVALID TOOLBAR ITEM" exception.
     *
     * @param string $item The invalid toolbar item.
     *
     * @return \Ivory\CKEditorBundle\Exception\DependencyInjectionException The "INVALID TOOLBAR ITEM" exception.
     */
    public static function invalidToolbarItem($item)
    {
        return new static(sprintf('The toolbar item "%s" does not exist.', $item));
    }
}
