<?php

/*
 * This file is part of the Ivory CKEditor package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\CKEditorBundle\Tests\Template;

use Ivory\CKEditorBundle\Helper\CKEditorHelper;
use Ivory\CKEditorBundle\Twig\CKEditorExtension;

/**
 * Twig template test.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class TwigTemplateTest extends AbstractTemplateTest
{
    /** @var \Twig_Environment */
    protected $twig;

    /** @var \Twig_Template */
    protected $template;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->twig = new \Twig_Environment(new \Twig_Loader_Filesystem(array(__DIR__.'/../../Resources/views/Form')));
        $this->twig->addExtension(new CKEditorExtension(new CKEditorHelper($this->containerMock)));

        $this->template = $this->twig->loadTemplate('ckeditor_widget.html.twig');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        unset($this->template);
        unset($this->twig);
    }

    /**
     * {@inheritdoc}
     */
    protected function renderTemplate(array $context = array())
    {
        return $this->template->render($context);
    }
}
