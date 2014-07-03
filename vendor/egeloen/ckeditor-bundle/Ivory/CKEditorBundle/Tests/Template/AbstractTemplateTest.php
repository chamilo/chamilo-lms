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

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract template test.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
abstract class AbstractTemplateTest extends \PHPUnit_Framework_TestCase
{
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

        $this->assetsHelperMock
            ->expects($this->any())
            ->method('getUrl')
            ->will($this->returnArgument(0));

        $this->assetsVersionTrimerHelperMock = $this->getMock('Ivory\CKEditorBundle\Helper\AssetsVersionTrimerHelper');

        $this->assetsVersionTrimerHelperMock
            ->expects($this->any())
            ->method('trim')
            ->will($this->returnArgument(0));

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
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->routerMock);
        unset($this->assetsVersionTrimerHelperMock);
        unset($this->assetsHelperMock);
        unset($this->containerMock);
    }

    public function testRenderWithSimpleWidget()
    {
        $output = $this->renderTemplate(
            array(
                'form'      => $this->getMock('Symfony\Component\Form\FormView'),
                'id'        => 'id',
                'value'     => '<p>value</p>',
                'enable'    => true,
                'base_path' => 'base_path',
                'js_path'   => 'js_path',
                'config'    => array(),
                'plugins'   => array(),
                'styles'    => array(),
                'templates' => array(),
            )
        );

        $expected = <<<EOF
<textarea >&lt;p&gt;value&lt;/p&gt;</textarea>
<script type="text/javascript">
var CKEDITOR_BASEPATH = "base_path";
</script>
<script type="text/javascript" src="js_path"></script>
<script type="text/javascript">
if (CKEDITOR.instances["id"]) {
delete CKEDITOR.instances["id"];
}
CKEDITOR.replace("id", []);
</script>

EOF;

        $this->assertSame($this->normalizeOutput($expected), $this->normalizeOutput($output));
    }

    public function testRenderWithFullWidget()
    {
        $output = $this->renderTemplate(
            array(
                'form'      => $this->getMock('Symfony\Component\Form\FormView'),
                'id'        => 'id',
                'value'     => '<p>value</p>',
                'enable'    => true,
                'base_path' => 'base_path',
                'js_path'   => 'js_path',
                'config'    => array('foo' => 'bar'),
                'plugins'   => array(
                    'foo' => array('path' => 'path', 'filename' => 'filename'),
                ),
                'styles'    => array(
                    'default' => array(
                        array('name' => 'Blue Title', 'element' => 'h2', 'styles' => array('color' => 'Blue')),
                    ),
                ),
                'templates' => array(
                    'foo' => array(
                        'imagesPath' => 'path',
                        'templates'  => array(
                            array(
                                'title' => 'My Template',
                                'html'  => '<h1>Template</h1>',
                            ),
                        ),
                    )
                ),
            )
        );

        $expected = <<<EOF
<textarea >&lt;p&gt;value&lt;/p&gt;</textarea>
<script type="text/javascript">
var CKEDITOR_BASEPATH = "base_path";
</script>
<script type="text/javascript" src="js_path"></script>
<script type="text/javascript">
if (CKEDITOR.instances["id"]) {
delete CKEDITOR.instances["id"];
}
CKEDITOR.plugins.addExternal("foo", "path", "filename");
if (CKEDITOR.stylesSet.get("default") === null) { CKEDITOR.stylesSet.add("default", [{"name":"Blue Title","element":"h2","styles":{"color":"Blue"}}]); }
CKEDITOR.addTemplates("foo", {"imagesPath":"path","templates":[{"title":"My Template","html":"<h1>Template<\/h1>"}]});
CKEDITOR.replace("id", {"foo":"bar"});
</script>

EOF;

        $this->assertSame($this->normalizeOutput($expected), $this->normalizeOutput($output));
    }

    public function testRenderWithDisableWidget()
    {
        $output = $this->renderTemplate(
            array(
                'form'   => $this->getMock('Symfony\Component\Form\FormView'),
                'id'     => 'id',
                'value'  => 'value',
                'enable' => false,
            )
        );

        $expected = <<<EOF
<textarea >value</textarea>

EOF;

        $this->assertSame($this->normalizeOutput($expected), $this->normalizeOutput($output));
    }

    public function testRenderWithMultipleWidgets()
    {
        $context = array(
            'form'      => $this->getMock('Symfony\Component\Form\FormView'),
            'id'        => 'id',
            'value'     => '<p>value</p>',
            'enable'    => true,
            'base_path' => 'base_path',
            'js_path'   => 'js_path',
            'config'    => array(),
            'plugins'   => array(),
            'styles'    => array(),
            'templates' => array(),
        );

        $this->renderTemplate($context);
        $output = $this->renderTemplate($context);

        $expected = <<<EOF
<textarea >&lt;p&gt;value&lt;/p&gt;</textarea>
<script type="text/javascript">
if (CKEDITOR.instances["id"]) {
delete CKEDITOR.instances["id"];
}
CKEDITOR.replace("id", []);
</script>

EOF;

        $this->assertSame($this->normalizeOutput($expected), $this->normalizeOutput($output));
    }

    /**
     * Renders a template.
     *
     * @param array $context The template context.
     *
     * @return string The template output.
     */
    abstract protected function renderTemplate(array $context = array());

    /**
     * Normalizes the output by removing the heading whitespaces.
     *
     * @param string $output The output.
     *
     * @return string The normalized output.
     */
    protected function normalizeOutput($output)
    {
        return str_replace(PHP_EOL, '', str_replace(' ', '', $output));
    }
}
