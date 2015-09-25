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

use Sonata\BlockBundle\Block\BlockLoaderChain;

class BlockLoaderChainTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException \Sonata\BlockBundle\Exception\BlockNotFoundException
     */
    public function testBlockNotFoundException()
    {
        $loader = new BlockLoaderChain(array());
        $loader->load('foo');
    }

    public function testLoaderWithSupportedLoader()
    {
        $block = $this->getMock('Sonata\BlockBundle\Model\BlockInterface');

        $loader = $this->getMock('Sonata\BlockBundle\Block\BlockLoaderInterface');
        $loader->expects($this->once())->method('support')->will($this->returnValue(true));
        $loader->expects($this->once())->method('load')->will($this->returnValue($block));

        $loaderChain = new BlockLoaderChain(array($loader));

        $this->assertTrue($loaderChain->support('foo'));

        $this->assertEquals($block, $loaderChain->load('foo'));
    }

    /**
     * @expectedException \Sonata\BlockBundle\Exception\BlockNotFoundException
     */
    public function testLoaderWithUnSupportedLoader()
    {
        $loader = $this->getMock('Sonata\BlockBundle\Block\BlockLoaderInterface');
        $loader->expects($this->once())->method('support')->will($this->returnValue(false));
        $loader->expects($this->never())->method('load');

        $loaderChain = new BlockLoaderChain(array($loader));

        $this->assertTrue($loaderChain->support('foo'));

        $loaderChain->load('foo');
    }
}
