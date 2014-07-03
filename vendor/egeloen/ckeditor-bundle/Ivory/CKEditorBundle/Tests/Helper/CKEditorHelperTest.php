<?php

/*
 * This file is part of the Ivory CKEditor package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\CKEditorBundle\Tests\Helper;

use Ivory\CKEditorBundle\Helper\CKEditorHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * CKEditor helper test.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class CKEditorHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Ivory\CKEditorBundle\Helper\CKEditorHelper */
    protected $helper;

    /** @var \Symfony\Component\DependencyInjection\ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $containerMock;

    /** @var \Symfony\Component\Templating\Helper\CoreAssetsHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $assetsHelperMock;

    /** @var \Ivory\CKEditorBundle\Helper\AssetsVersionTrimerHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $assetsVersionTrimerHelperMock;

    /** @var \Symfony\Component\Routing\RouterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $routerMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->assetsHelperMock = $this->getMockBuilder('Symfony\Component\Templating\Helper\CoreAssetsHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assetsVersionTrimerHelperMock = $this->getMock('Ivory\CKEditorBundle\Helper\AssetsVersionTrimerHelper');
        $this->routerMock = $this->getMock('Symfony\Component\Routing\RouterInterface');

        $this->containerMock = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->containerMock
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap(array(
                array(
                    'templating.helper.assets',
                    ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                    $this->assetsHelperMock,
                ),
                array(
                    'ivory_ck_editor.helper.assets_version_trimer',
                    ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                    $this->assetsVersionTrimerHelperMock,
                ),
                array(
                    'router',
                    ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                    $this->routerMock
                ),
            )));

        $this->helper = new CKEditorHelper($this->containerMock);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->helper);
        unset($this->containerMock);
        unset($this->routerMock);
        unset($this->assetsVersionTrimerHelperMock);
        unset($this->assetsHelperMock);
    }

    /**
     * Gets the valid filebrowsers keys.
     *
     * @return array The valid filebrowsers keys.
     */
    public static function filebrowserProvider()
    {
        return array(
            array('Browse'),
            array('FlashBrowse'),
            array('ImageBrowse'),
            array('ImageBrowseLink'),
            array('Upload'),
            array('FlashUpload'),
            array('ImageUpload'),
        );
    }

    public function testRenderBasePath()
    {
        $this->assetsHelperMock
            ->expects($this->once())
            ->method('getUrl')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue('bar'));

        $this->assetsVersionTrimerHelperMock
            ->expects($this->once())
            ->method('trim')
            ->with($this->equalTo('bar'))
            ->will($this->returnValue('baz'));

        $this->assertSame('baz', $this->helper->renderBasePath('foo'));
    }

    public function testRenderJsPath()
    {
        $this->assetsHelperMock
            ->expects($this->once())
            ->method('getUrl')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue('bar'));

        $this->assertSame('bar', $this->helper->renderJsPath('foo'));
    }

    public function testRenderReplaceWithStringContentsCss()
    {
        $this->assetsHelperMock
            ->expects($this->once())
            ->method('getUrl')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue('bar'));

        $this->assetsVersionTrimerHelperMock
            ->expects($this->once())
            ->method('trim')
            ->with($this->equalTo('bar'))
            ->will($this->returnValue('baz'));

        $this->assertSame(
            'CKEDITOR.replace("foo", {"contentsCss":["baz"]});',
            $this->helper->renderReplace('foo', array('contentsCss' => 'foo'))
        );
    }

    public function testRenderReplaceWithArrayContentsCss()
    {
        $this->assetsHelperMock
            ->expects($this->at(0))
            ->method('getUrl')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue('foo1'));

        $this->assetsVersionTrimerHelperMock
            ->expects($this->at(0))
            ->method('trim')
            ->with($this->equalTo('foo1'))
            ->will($this->returnValue('baz1'));

        $this->assetsHelperMock
            ->expects($this->at(1))
            ->method('getUrl')
            ->with($this->equalTo('bar'))
            ->will($this->returnValue('bar1'));

        $this->assetsVersionTrimerHelperMock
            ->expects($this->at(1))
            ->method('trim')
            ->with($this->equalTo('bar1'))
            ->will($this->returnValue('baz2'));

        $this->assertSame(
            'CKEDITOR.replace("foo", {"contentsCss":["baz1","baz2"]});',
            $this->helper->renderReplace('foo', array('contentsCss' => array('foo', 'bar')))
        );
    }

    /**
     * @dataProvider filebrowserProvider
     */
    public function testRenderReplaceWithFileBrowser($filebrowser)
    {
        $this->routerMock
            ->expects($this->once())
            ->method('generate')
            ->with(
                $this->equalTo('browse_route'),
                $this->equalTo(array('foo' => 'bar')),
                $this->equalTo(true)
            )
            ->will($this->returnValue('browse_url'));

        $this->assertSame(
            sprintf('CKEDITOR.replace("foo", {"filebrowser%sUrl":"browse_url"});', $filebrowser),
            $this->helper->renderReplace('foo', array(
                'filebrowser'.$filebrowser.'Route'           => 'browse_route',
                'filebrowser'.$filebrowser.'RouteParameters' => array('foo' => 'bar'),
                'filebrowser'.$filebrowser.'RouteAbsolute'   => true,
            ))
        );
    }

    /**
     * @dataProvider filebrowserProvider
     */
    public function testRenderReplaceWithFileBrowserHandler($filebrowser)
    {
        $this->routerMock
            ->expects($this->once())
            ->method('generate')
            ->with(
                $this->equalTo('browse_route'),
                $this->equalTo(array('foo' => 'bar')),
                $this->equalTo(true)
            )
            ->will($this->returnValue('browse_url'));

        $this->assertSame(
            sprintf('CKEDITOR.replace("foo", {"filebrowser%sUrl":"browse_url"});', $filebrowser),
            $this->helper->renderReplace('foo', array(
                'filebrowser'.$filebrowser.'Handler' => function (RouterInterface $router) {
                    return $router->generate('browse_route', array('foo' => 'bar'), true);
                },
            ))
        );
    }

    public function testRenderReplaceWithProtectedSource()
    {
        $this->assertSame(
            'CKEDITOR.replace("foo", {"protectedSource":[/<\?[\s\S]*?\?>/g,/<%[\s\S]*?%>/g]});',
            $this->helper->renderReplace('foo', array(
                'protectedSource' => array(
                    '/<\?[\s\S]*?\?>/g',
                    '/<%[\s\S]*?%>/g',
                )
            ))
        );
    }

    public function testRenderReplaceWithStylesheetParserSkipSelectors()
    {
        $this->assertSame(
            'CKEDITOR.replace("foo", {"stylesheetParser_skipSelectors":/(^body\.|^caption\.|\.high|^\.)/i});',
            $this->helper->renderReplace('foo', array(
                'stylesheetParser_skipSelectors' => '/(^body\.|^caption\.|\.high|^\.)/i',
            ))
        );
    }

    public function testRenderReplaceWithStylesheetParserValidSelectors()
    {
        $this->assertSame(
            'CKEDITOR.replace("foo", {"stylesheetParser_validSelectors":/\^(p|span)\.\w+/});',
            $this->helper->renderReplace('foo', array(
                'stylesheetParser_validSelectors' => '/\^(p|span)\.\w+/',
            ))
        );
    }

    public function testRenderReplaceWithCKEditorConstants()
    {
        $this->assertSame(
            'CKEDITOR.replace("foo", {"config":{"enterMode":CKEDITOR.ENTER_BR,"shiftEnterMode":CKEDITOR.ENTER_BR}});',
            $this->helper->renderReplace('foo', array(
                'config' => array(
                    'enterMode'      => 'CKEDITOR.ENTER_BR',
                    'shiftEnterMode' => 'CKEDITOR.ENTER_BR',
                ),
            ))
        );
    }

    public function testRenderDestroy()
    {
        $this->assertSame(
            'if (CKEDITOR.instances["foo"]) { delete CKEDITOR.instances["foo"]; }',
            $this->helper->renderDestroy('foo')
        );
    }

    public function testRenderPlugin()
    {
        $this->assetsHelperMock
            ->expects($this->once())
            ->method('getUrl')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue('bar'));

        $this->assetsVersionTrimerHelperMock
            ->expects($this->once())
            ->method('trim')
            ->with($this->equalTo('bar'))
            ->will($this->returnValue('baz'));

        $this->assertSame(
            'CKEDITOR.plugins.addExternal("foo", "baz", "bat");',
            $this->helper->renderPlugin('foo', array('path' => 'foo', 'filename' => 'bat'))
        );
    }

    public function testRenderStylesSet()
    {
        $this->assertSame(
            'if (CKEDITOR.stylesSet.get("foo") === null) { CKEDITOR.stylesSet.add("foo", {"foo":"bar"}); }',
            $this->helper->renderStylesSet('foo', array('foo' => 'bar'))
        );
    }

    public function testRenderTemplate()
    {
        $this->assetsHelperMock
            ->expects($this->once())
            ->method('getUrl')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue('bar'));

        $this->assetsVersionTrimerHelperMock
            ->expects($this->once())
            ->method('trim')
            ->with($this->equalTo('bar'))
            ->will($this->returnValue('baz'));

        $this->assertSame(
            'CKEDITOR.addTemplates("foo", {"imagesPath":"baz","filename":"bat"});',
            $this->helper->renderTemplate('foo', array('imagesPath' => 'foo', 'filename' => 'bat'))
        );
    }

    public function testName()
    {
        $this->assertSame('ivory_ckeditor', $this->helper->getName());
    }
}
