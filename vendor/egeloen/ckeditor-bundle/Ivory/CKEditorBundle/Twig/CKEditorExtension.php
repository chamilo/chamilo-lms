<?php

/*
 * This file is part of the Ivory CKEditor package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\CKEditorBundle\Twig;

use Ivory\CKEditorBundle\Helper\CKEditorHelper;

/**
 * CKEditorExtension
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class CKEditorExtension extends \Twig_Extension
{
    /** @var \Ivory\CKEditorBundle\Helper\CKEditorHelper */
    protected $helper;

    /**
     * Creates a CKEditor extension.
     *
     * @param \Ivory\CKEditorBundle\Helper\CKEditorHelper $helper The CKEditor helper.
     */
    public function __construct(CKEditorHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        $options = array('is_safe' => array('html'));

        return array(
            new \Twig_SimpleFunction('ckeditor_is_loaded', array($this->helper, 'isLoaded'), $options),
            new \Twig_SimpleFunction('ckeditor_base_path', array($this->helper, 'renderBasePath'), $options),
            new \Twig_SimpleFunction('ckeditor_js_path', array($this->helper, 'renderJsPath'), $options),
            new \Twig_SimpleFunction('ckeditor_replace', array($this->helper, 'renderReplace'), $options),
            new \Twig_SimpleFunction('ckeditor_destroy', array($this->helper, 'renderDestroy'), $options),
            new \Twig_SimpleFunction('ckeditor_plugin', array($this->helper, 'renderPlugin'), $options),
            new \Twig_SimpleFunction('ckeditor_styles_set', array($this->helper, 'renderStylesSet'), $options),
            new \Twig_SimpleFunction('ckeditor_template', array($this->helper, 'renderTemplate'), $options),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->helper->getName();
    }
}
