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

    public function testLoader()
    {
        $loader = $this->getMock('Sonata\BlockBundle\Block\BlockLoaderInterface');
        $loader->expects($this->once())->method('support')->will($this->returnValue(true));
        $loader->expects($this->once())->method('load');

        $loaderChain = new BlockLoaderChain(array($loader));

        $this->assertTrue($loaderChain->support('foo'));

        $loaderChain->load('foo');
    }
}
