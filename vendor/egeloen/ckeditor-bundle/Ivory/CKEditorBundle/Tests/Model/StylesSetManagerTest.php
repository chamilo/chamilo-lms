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

use Ivory\CKEditorBundle\Model\StylesSetManager;

/**
 * Styles set manager test.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class StylesSetManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Ivory\CKEditorBundle\Model\StylesSetManager */
    private $stylesSetManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->stylesSetManager = new StylesSetManager();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->stylesSetManager);
    }

    public function testDefaultState()
    {
        $this->assertFalse($this->stylesSetManager->hasStylesSets());
        $this->assertSame(array(), $this->stylesSetManager->getStylesSets());
    }

    public function testInitialState()
    {
        $stylesSets = array(
            'default' => array(
                array('name' => 'Blue Title', 'element' => 'h2', 'styles' => array('color' => 'Blue')),
                array('name' => 'CSS Style', 'element' => 'span', 'attributes' => array('class' => 'my_style')),
            ),
        );

        $this->stylesSetManager = new StylesSetManager($stylesSets);

        $this->assertTrue($this->stylesSetManager->hasStylesSets());
        $this->assertTrue($this->stylesSetManager->hasStylesSet('default'));
        $this->assertSame($stylesSets['default'], $this->stylesSetManager->getStylesSet('default'));
    }

    public function testTemplates()
    {
        $stylesSets = array(
            'default' => array(
                array('name' => 'Blue Title', 'element' => 'h2', 'styles' => array('color' => 'Blue')),
                array('name' => 'CSS Style', 'element' => 'span', 'attributes' => array('class' => 'my_style')),
            ),
        );

        $this->stylesSetManager->setStylesSets($stylesSets);

        $this->assertTrue($this->stylesSetManager->hasStylesSets());
        $this->assertTrue($this->stylesSetManager->hasStylesSet('default'));
        $this->assertSame($stylesSets, $this->stylesSetManager->getStylesSets());
    }

    /**
     * @expectedException \Ivory\CKEditorBundle\Exception\StylesSetManagerException
     * @expectedExceptionMessage The CKEditor styles set "foo" does not exist.
     */
    public function testGetStylesSetWithInvalidValue()
    {
        $this->stylesSetManager->getStylesSet('foo');
    }
}
