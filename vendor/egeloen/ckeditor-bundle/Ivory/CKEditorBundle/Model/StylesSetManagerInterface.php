<?php

/*
 * This file is part of the Ivory CKEditor package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\CKEditorBundle\Model;

/**
 * Ivory CKEditor styles set manager.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
interface StylesSetManagerInterface
{
    /**
     * Checks if the CKEditor styles sets exists.
     *
     * @return boolean TRUE if the CKEditor styles sets exists else FALSE.
     */
    public function hasStylesSets();

    /**
     * Gets the CKEditor styles sets.
     *
     * @return array The CKEditor styles sets.
     */
    public function getStylesSets();

    /**
     * Sets the CKEditor styles sets.
     *
     * @param array $stylesSets The CKEditor styles sets.
     *
     * @return null No return value.
     */
    public function setStylesSets(array $stylesSets);

    /**
     * Checks if a specific CKEditor styles set exists.
     *
     * @param string $name The CKEditor styles set name.
     *
     * @return boolean TRUE if the CKEditor styles set exists else FALSE.
     */
    public function hasStylesSet($name);

    /**
     * Gets a specific CKEditor styles set.
     *
     * @param string $name The CKEditor styles set name.
     *
     * @return array The CKEditor styles set.
     */
    public function getStylesSet($name);

    /**
     * Sets a CKEditor styles set.
     *
     * @param string $name      The CKEditor styles set name.
     * @param array  $stylesSet The CKEditor styles set.
     *
     * @return null No return value.
     */
    public function setStylesSet($name, array $stylesSet);
}
