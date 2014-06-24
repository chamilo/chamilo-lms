<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\Model\Adapter;

use Sonata\CoreBundle\Model\Adapter\AdapterChain;

class AdapterChainTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptyAdapter()
    {
        $adapter = new AdapterChain();

        $this->assertNull($adapter->getNormalizedIdentifier(new \stdClass()));
        $this->assertNull($adapter->getUrlsafeIdentifier(new \stdClass()));
    }

    public function testUrlSafeIdentifier()
    {
        $adapter = new AdapterChain();

        $adapter->addAdapter($fake1 = $this->getMock('Sonata\CoreBundle\Model\Adapter\AdapterInterface'));
        $fake1->expects($this->once())->method('getUrlsafeIdentifier')->will($this->returnValue(null));

        $adapter->addAdapter($fake2 = $this->getMock('Sonata\CoreBundle\Model\Adapter\AdapterInterface'));

        $fake2->expects($this->once())->method('getUrlsafeIdentifier')->will($this->returnValue("voila"));

        $this->assertEquals("voila", $adapter->getUrlsafeIdentifier(new \stdClass()));
    }

    public function testNormalizedIdentifier()
    {
        $adapter = new AdapterChain();

        $adapter->addAdapter($fake1 = $this->getMock('Sonata\CoreBundle\Model\Adapter\AdapterInterface'));
        $fake1->expects($this->once())->method('getNormalizedIdentifier')->will($this->returnValue(null));

        $adapter->addAdapter($fake2 = $this->getMock('Sonata\CoreBundle\Model\Adapter\AdapterInterface'));

        $fake2->expects($this->once())->method('getNormalizedIdentifier')->will($this->returnValue("voila"));

        $this->assertEquals("voila", $adapter->getNormalizedIdentifier(new \stdClass()));
    }
}