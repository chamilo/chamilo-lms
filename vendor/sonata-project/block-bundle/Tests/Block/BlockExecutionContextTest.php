<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Tests\Block;

use Sonata\BlockBundle\Block\BlockContext;

class BlockContextTest extends \PHPUnit_Framework_TestCase
{
    public function testBasicFeature()
    {
        $block = $this->getMock('Sonata\BlockBundle\Model\BlockInterface');

        $blockContext = new BlockContext($block, array(
            'hello' => 'world'
        ));

        $this->assertEquals('world', $blockContext->getSetting('hello'));
        $this->assertEquals(array('hello' => 'world'), $blockContext->getSettings());

        $this->assertEquals($block, $blockContext->getBlock());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testInvalidParameter()
    {
        $block = $this->getMock('Sonata\BlockBundle\Model\BlockInterface');

        $blockContext = new BlockContext($block);

        $blockContext->getSetting('fake');
    }
}