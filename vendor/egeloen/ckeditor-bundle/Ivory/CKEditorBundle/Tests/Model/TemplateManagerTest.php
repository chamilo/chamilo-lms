<?php

/*
 * This file is part of the Ivory CKEditor package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\CKEditorBundle\Tests\Model;

use Ivory\CKEditorBundle\Model\TemplateManager;

/**
 * Template manager test.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class TemplateManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Ivory\CKEditorBundle\Model\TemplateManager */
    protected $templateManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->templateManager = new TemplateManager();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->templateManager);
    }

    public function testDefaultState()
    {
        $this->assertFalse($this->templateManager->hasTemplates());
        $this->assertSame(array(), $this->templateManager->getTemplates());
    }

    public function testInitialState()
    {
        $templates = array(
            'default' => array(
                'imagesPath' => '/my/path',
                'templates'  => array(
                    array(
                        'title' => 'My Template',
                        'html'  => '<h1>Template</h1><p>Type your text here.</p>',
                    ),
                ),
            ),
        );

        $this->templateManager = new TemplateManager($templates);

        $this->assertTrue($this->templateManager->hasTemplates());
        $this->assertTrue($this->templateManager->hasTemplate('default'));
        $this->assertSame($templates['default'], $this->templateManager->getTemplate('default'));
    }

    public function testTemplates()
    {
        $templates = array(
            'default' => array(
                'imagesPath' => '/my/path',
                'templates'  => array(
                    array(
                        'title' => 'My Template',
                        'html'  => '<h1>Template</h1><p>Type your text here.</p>',
                    ),
                ),
            ),
        );

        $this->templateManager->setTemplates($templates);

        $this->assertTrue($this->templateManager->hasTemplates());
        $this->assertTrue($this->templateManager->hasTemplate('default'));
        $this->assertSame($templates, $this->templateManager->getTemplates());
    }

    /**
     * @expectedException \Ivory\CKEditorBundle\Exception\TemplateManagerException
     * @expectedExceptionMessage The CKEditor template "foo" does not exist.
     */
    public function testGetTemplateWithInvalidValue()
    {
        $this->templateManager->getTemplate('foo');
    }
}
