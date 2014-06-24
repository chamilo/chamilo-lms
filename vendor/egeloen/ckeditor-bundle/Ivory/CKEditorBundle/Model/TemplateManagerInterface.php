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
 * Ivory CKEditor template manager.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
interface TemplateManagerInterface
{
    /**
     * Checks if the CKEditor templates exists.
     *
     * @return boolean TRUE if the CKEditor templates exists else FALSE.
     */
    public function hasTemplates();

    /**
     * Gets the CKEditor templates.
     *
     * @return array The CKEditor templates.
     */
    public function getTemplates();

    /**
     * Sets the CKEditor templates.
     *
     * @param array $templates The CKEditor templates.
     *
     * @return null No return value.
     */
    public function setTemplates(array $templates);

    /**
     * Checks if a specific CKEditor template exists.
     *
     * @param string $name The CKEditor template name.
     *
     * @return boolean TRUE if the CKEditor template exists else FALSE.
     */
    public function hasTemplate($name);

    /**
     * Gets a specific CKEditor template.
     *
     * @param string $name The CKEditor name.
     *
     * @return array The CKEditor template.
     */
    public function getTemplate($name);

    /**
     * Sets a CKEditor template.
     *
     * @param string $name     The CKEditor template name.
     * @param array  $template The CKEditor template.
     *
     * @return null No return value.
     */
    public function setTemplate($name, array $template);
}
