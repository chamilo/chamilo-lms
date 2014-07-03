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

use Ivory\CKEditorBundle\Exception\TemplateManagerException;

/**
 * {@inheritdoc}
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class TemplateManager implements TemplateManagerInterface
{
    /** @var array */
    protected $templates = array();

    /**
     * Creates a plugin manager.
     *
     * @param array $templates The CKEditor templates.
     */
    public function __construct(array $templates = array())
    {
        $this->setTemplates($templates);
    }

    /**
     * {@inheritdoc}
     */
    public function hasTemplates()
    {
        return !empty($this->templates);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * {@inheritdoc}
     */
    public function setTemplates(array $templates)
    {
        foreach ($templates as $name => $template) {
            $this->setTemplate($name, $template);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasTemplate($name)
    {
        return isset($this->templates[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate($name)
    {
        if (!$this->hasTemplate($name)) {
            throw TemplateManagerException::templateDoesNotExist($name);
        }

        return $this->templates[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function setTemplate($name, array $template)
    {
        $this->templates[$name] = $template;
    }
}
