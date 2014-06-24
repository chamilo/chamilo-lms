<?php

/*
 * This file is part of the Ivory CKEditor package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\CKEditorBundle\Tests\Form\Type;

use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\Forms;

/**
 * CKEditor type test.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class CKEditorTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Symfony\Component\Form\FormFactoryInterface */
    protected $factory;

    /** @var \Ivory\CKEditorBundle\Form\Type\CKEditorType */
    protected $ckEditorType;

    /** @var \Ivory\CKEditorBundle\Model\ConfigManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManagerMock;

    /** @var \Ivory\CKEditorBundle\Model\PluginManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $pluginManagerMock;

    /** @var \Ivory\CKEditorBundle\Model\StylesSetManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $stylesSetManagerMock;

    /** @var \Ivory\CKEditorBundle\Model\TemplateManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $templateManagerMock;

    /**
     * {@inheritdooc}
     */
    protected function setUp()
    {
        $this->configManagerMock = $this->getMock('Ivory\CKEditorBundle\Model\ConfigManagerInterface');
        $this->pluginManagerMock = $this->getMock('Ivory\CKEditorBundle\Model\PluginManagerInterface');
        $this->stylesSetManagerMock = $this->getMock('Ivory\CKEditorBundle\Model\StylesSetManagerInterface');
        $this->templateManagerMock = $this->getMock('Ivory\CKEditorBundle\Model\TemplateManagerInterface');

        $this->ckEditorType = new CKEditorType(
            true,
            'bundles/ckeditor/',
            'bundles/ckeditor/ckeditor.js',
            $this->configManagerMock,
            $this->pluginManagerMock,
            $this->stylesSetManagerMock,
            $this->templateManagerMock
        );

        $this->factory = Forms::createFormFactoryBuilder()
            ->addType($this->ckEditorType)
            ->getFormFactory();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->configManagerMock);
        unset($this->pluginManagerMock);
        unset($this->stylesSetManagerMock);
        unset($this->templateManagerMock);
        unset($this->ckEditorType);
        unset($this->factory);
    }

    public function testInitialState()
    {
        $this->assertTrue($this->ckEditorType->isEnable());
        $this->assertSame('bundles/ckeditor/', $this->ckEditorType->getBasePath());
        $this->assertSame('bundles/ckeditor/ckeditor.js', $this->ckEditorType->getJsPath());
        $this->assertSame($this->configManagerMock, $this->ckEditorType->getConfigManager());
        $this->assertSame($this->pluginManagerMock, $this->ckEditorType->getPluginManager());
        $this->assertSame($this->stylesSetManagerMock, $this->ckEditorType->getStylesSetManager());
        $this->assertSame($this->templateManagerMock, $this->ckEditorType->getTemplateManager());
    }

    public function testBaseAndJsPathWithConfiguredValues()
    {
        $form = $this->factory->create('ckeditor');
        $view = $form->createView();

        $this->assertArrayHasKey('base_path', $view->vars);
        $this->assertSame('bundles/ckeditor/', $view->vars['base_path']);

        $this->assertArrayHasKey('js_path', $view->vars);
        $this->assertSame('bundles/ckeditor/ckeditor.js', $view->vars['js_path']);
    }

    public function testBaseAndJsPathWithConfiguredAndExplicitValues()
    {
        $form = $this->factory->create(
            'ckeditor',
            null,
            array('base_path' => 'foo/', 'js_path' => 'foo/ckeditor.js')
        );

        $view = $form->createView();

        $this->assertArrayHasKey('base_path', $view->vars);
        $this->assertSame('foo/', $view->vars['base_path']);

        $this->assertArrayHasKey('js_path', $view->vars);
        $this->assertSame('foo/ckeditor.js', $view->vars['js_path']);
    }

    public function testDefaultConfig()
    {
        $form = $this->factory->create('ckeditor');
        $view = $form->createView();

        $this->assertArrayHasKey('config', $view->vars);
        $this->assertEmpty(json_decode($view->vars['config'], true));
    }

    public function testConfigWithExplicitConfig()
    {
        $options = array(
            'config' => array(
                'toolbar' => array('foo' => 'bar'),
                'uiColor' => '#ffffff',
            ),
        );

        $this->configManagerMock
            ->expects($this->once())
            ->method('setConfig')
            ->with($this->anything(), $this->equalTo($options['config']));

        $this->configManagerMock
            ->expects($this->once())
            ->method('getConfig')
            ->with($this->anything())
            ->will($this->returnValue($options['config']));

        $form = $this->factory->create('ckeditor', null, $options);
        $view = $form->createView();

        $this->assertArrayHasKey('config', $view->vars);
        $this->assertSame($options['config'], $view->vars['config']);
    }

    public function testConfigWithConfiguredConfig()
    {
        $config = array(
            'toolbar' => 'default',
            'uiColor' => '#ffffff',
        );

        $this->configManagerMock
            ->expects($this->once())
            ->method('mergeConfig')
            ->with($this->equalTo('default'), $this->equalTo(array()));

        $this->configManagerMock
            ->expects($this->once())
            ->method('getConfig')
            ->with('default')
            ->will($this->returnValue($config));

        $form = $this->factory->create('ckeditor', null, array('config_name' => 'default'));
        $view = $form->createView();

        $this->assertArrayHasKey('config', $view->vars);
        $this->assertSame($config, $view->vars['config']);
    }

    public function testConfigWithDefaultConfiguredConfig()
    {
        $options = array(
            'toolbar' => array('foo' => 'bar'),
            'uiColor' => '#ffffff',
        );

        $this->configManagerMock
            ->expects($this->once())
            ->method('getDefaultConfig')
            ->will($this->returnValue('config'));

        $this->configManagerMock
            ->expects($this->once())
            ->method('mergeConfig')
            ->with($this->equalTo('config'), $this->equalTo(array()));

        $this->configManagerMock
            ->expects($this->once())
            ->method('getConfig')
            ->with('config')
            ->will($this->returnValue($options));

        $form = $this->factory->create('ckeditor');
        $view = $form->createView();

        $this->assertArrayHasKey('config', $view->vars);
        $this->assertSame($options, $view->vars['config']);
    }

    public function testConfigWithExplicitAndConfiguredConfig()
    {
        $configuredConfig = array(
            'toolbar' => 'default',
            'uiColor' => '#ffffff',
        );

        $explicitConfig = array('uiColor' => '#000000');

        $this->configManagerMock
            ->expects($this->once())
            ->method('mergeConfig')
            ->with($this->equalTo('default'), $this->equalTo($explicitConfig));

        $this->configManagerMock
            ->expects($this->once())
            ->method('getConfig')
            ->with('default')
            ->will($this->returnValue(array_merge($configuredConfig, $explicitConfig)));

        $form = $this->factory->create(
            'ckeditor',
            null,
            array('config_name' => 'default', 'config' => $explicitConfig)
        );

        $view = $form->createView();

        $this->assertArrayHasKey('config', $view->vars);
        $this->assertSame(array_merge($configuredConfig, $explicitConfig), $view->vars['config']);
    }

    public function testDefaultPlugins()
    {
        $form = $this->factory->create('ckeditor');
        $view = $form->createView();

        $this->assertArrayHasKey('plugins', $view->vars);
        $this->assertEmpty($view->vars['plugins']);
    }

    public function testPluginsWithExplicitPlugins()
    {
        $plugins = array(
            'wordcount' => array(
                'path'     => '/my/path',
                'filename' => 'plugin.js',
            ),
        );

        $this->pluginManagerMock
            ->expects($this->once())
            ->method('setPlugins')
            ->with($this->equalTo($plugins));

        $this->pluginManagerMock
            ->expects($this->once())
            ->method('getPlugins')
            ->will($this->returnValue($plugins));

        $form = $this->factory->create('ckeditor', null, array('plugins' => $plugins));

        $view = $form->createView();

        $this->assertArrayHasKey('plugins', $view->vars);
        $this->assertSame($plugins, $view->vars['plugins']);
    }

    public function testPluginsWithConfiguredPlugins()
    {
        $plugins = array(
            'wordcount' => array(
                'path'     => '/my/path',
                'filename' => 'plugin.js',
            ),
        );

        $this->pluginManagerMock
            ->expects($this->once())
            ->method('getPlugins')
            ->will($this->returnValue($plugins));

        $form = $this->factory->create('ckeditor');
        $view = $form->createView();

        $this->assertArrayHasKey('plugins', $view->vars);
        $this->assertSame($plugins, $view->vars['plugins']);
    }

    public function testPluginsWithConfiguredAndExplicitPlugins()
    {
        $configuredPlugins = array(
            'wordcount' => array(
                'path'     => '/my/explicit/path',
                'filename' => 'plugin.js',
            ),
        );

        $explicitPlugins = array(
            'autogrow' => array(
                'path'     => '/my/configured/path',
                'filename' => 'plugin.js',
            ),
        );

        $this->pluginManagerMock
            ->expects($this->once())
            ->method('setPlugins')
            ->with($this->equalTo($explicitPlugins));

        $this->pluginManagerMock
            ->expects($this->once())
            ->method('getPlugins')
            ->will($this->returnValue(array_merge($explicitPlugins, $configuredPlugins)));

        $form = $this->factory->create('ckeditor', null, array('plugins' => $explicitPlugins));
        $view = $form->createView();

        $this->assertArrayHasKey('plugins', $view->vars);
        $this->assertSame(array_merge($explicitPlugins, $configuredPlugins), $view->vars['plugins']);
    }

    public function testDefaultStylesSet()
    {
        $form = $this->factory->create('ckeditor');
        $view = $form->createView();

        $this->assertEmpty($view->vars['styles']);
    }

    public function testPluginsWithExplicitStylesSet()
    {
        $stylesSets = array(
            'default' => array(
                array('name' => 'Blue Title', 'element' => 'h2', 'styles' => array('color' => 'Blue')),
                array('name' => 'CSS Style', 'element' => 'span', 'attributes' => array('class' => 'my_style')),
            ),
        );

        $this->stylesSetManagerMock
            ->expects($this->once())
            ->method('setStylesSets')
            ->with($this->equalTo($stylesSets));

        $this->stylesSetManagerMock
            ->expects($this->once())
            ->method('getStylesSets')
            ->will($this->returnValue($stylesSets));

        $form = $this->factory->create('ckeditor', null, array('styles' => $stylesSets));

        $view = $form->createView();

        $this->assertSame($stylesSets, $view->vars['styles']);
    }

    public function testPluginsWithConfiguredStylesSets()
    {
        $stylesSets = array(
            'default' => array(
                array('name' => 'Blue Title', 'element' => 'h2', 'styles' => array('color' => 'Blue')),
                array('name' => 'CSS Style', 'element' => 'span', 'attributes' => array('class' => 'my_style')),
            ),
        );

        $this->stylesSetManagerMock
            ->expects($this->once())
            ->method('getStylesSets')
            ->will($this->returnValue($stylesSets));

        $form = $this->factory->create('ckeditor');
        $view = $form->createView();

        $this->assertSame($stylesSets, $view->vars['styles']);
    }

    public function testPluginsWithConfiguredAndExplicitStylesSets()
    {
        $configuredStylesSets = array(
            'foo' => array(
                array('name' => 'Blue Title', 'element' => 'h2', 'styles' => array('color' => 'Blue')),
            ),
        );

        $explicitStylesSets = array(
            'bar' => array(
                array('name' => 'CSS Style', 'element' => 'span', 'attributes' => array('class' => 'my_style')),
            ),
        );

        $this->stylesSetManagerMock
            ->expects($this->once())
            ->method('setStylesSets')
            ->with($this->equalTo($explicitStylesSets));

        $this->stylesSetManagerMock
            ->expects($this->once())
            ->method('getStylesSets')
            ->will($this->returnValue(array_merge($explicitStylesSets, $configuredStylesSets)));

        $form = $this->factory->create('ckeditor', null, array('styles' => $explicitStylesSets));
        $view = $form->createView();

        $this->assertSame(array_merge($explicitStylesSets, $configuredStylesSets), $view->vars['styles']);
    }

    public function testDefaultTemplates()
    {
        $form = $this->factory->create('ckeditor');
        $view = $form->createView();

        $this->assertEmpty($view->vars['templates']);
    }

    public function testTemplatesWithExplicitTemplates()
    {
        $templates = array(
            'default' => array(
                'imagesPath' => '/my/path',
                'templates'  => array(
                    array(
                        'title' => 'My Template',
                        'html'  => '<h1>Template</h1><p>Type your text here.</p>',
                    )
                ),
            ),
        );

        $this->templateManagerMock
            ->expects($this->once())
            ->method('setTemplates')
            ->with($this->equalTo($templates));

        $this->templateManagerMock
            ->expects($this->once())
            ->method('getTemplates')
            ->will($this->returnValue($templates));

        $form = $this->factory->create('ckeditor', null, array('templates' => $templates));

        $view = $form->createView();

        $this->assertSame($templates, $view->vars['templates']);
    }

    public function testTemplatesWithConfiguredTemplates()
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

        $this->templateManagerMock
            ->expects($this->once())
            ->method('getTemplates')
            ->will($this->returnValue($templates));

        $form = $this->factory->create('ckeditor');
        $view = $form->createView();

        $this->assertSame($templates, $view->vars['templates']);
    }

    public function testTemplatesWithConfiguredAndExplicitTemplates()
    {
        $configuredTemplates = array(
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

        $explicitTemplates = array(
            'extra' => array(
                'templates'  => array(
                    array(
                        'title' => 'My Extra Template',
                        'html'  => '<h2>Template</h2><p>Type your text here.</p>',
                    )
                ),
            ),
        );

        $this->templateManagerMock
            ->expects($this->once())
            ->method('setTemplates')
            ->with($this->equalTo($explicitTemplates));

        $this->templateManagerMock
            ->expects($this->once())
            ->method('getTemplates')
            ->will($this->returnValue(array_merge($explicitTemplates, $configuredTemplates)));

        $form = $this->factory->create('ckeditor', null, array('templates' => $explicitTemplates));
        $view = $form->createView();

        $this->assertSame(array_merge($explicitTemplates, $configuredTemplates), $view->vars['templates']);
    }

    public function testConfiguredDisable()
    {
        $this->ckEditorType->isEnable(false);

        $options = array(
            'config' => array(
                'toolbar' => array('foo' => 'bar'),
                'uiColor' => '#ffffff',
            ),
            'plugins' => array(
                'wordcount' => array(
                    'path'     => '/my/path',
                    'filename' => 'plugin.js',
                ),
            ),
        );

        $form = $this->factory->create('ckeditor', null, $options);
        $view = $form->createView();

        $this->assertArrayHasKey('enable', $view->vars);
        $this->assertFalse($view->vars['enable']);

        $this->assertArrayNotHasKey('config', $view->vars);
        $this->assertArrayNotHasKey('plugins', $view->vars);
    }

    public function testExplicitDisable()
    {
        $options = array(
            'enable' => false,
            'config' => array(
                'toolbar' => array('foo' => 'bar'),
                'uiColor' => '#ffffff',
            ),
            'plugins' => array(
                'wordcount' => array(
                    'path'     => '/my/path',
                    'filename' => 'plugin.js',
                ),
            ),
        );

        $form = $this->factory->create('ckeditor', null, $options);
        $view = $form->createView();

        $this->assertArrayHasKey('enable', $view->vars);
        $this->assertFalse($view->vars['enable']);

        $this->assertArrayNotHasKey('config', $view->vars);
        $this->assertArrayNotHasKey('plugins', $view->vars);
    }
}
