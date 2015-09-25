<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Tests;

use Sonata\BlockBundle\Event\BlockEvent;

class BlockEventTest extends \PHPUnit_Framework_TestCase
{

    public function testBlockEvent()
    {
        $blockEvent = new BlockEvent();

        $this->assertEmpty($blockEvent->getSettings());

        $blockEvent->addBlock($this->getMock('Sonata\BlockBundle\Model\BlockInterface'));

        $this->assertCount(1, $blockEvent->getBlocks());

        $blockEvent->addBlock($this->getMock('Sonata\BlockBundle\Model\BlockInterface'));
        $this->assertCount(2, $blockEvent->getBlocks());


        $this->assertNull($blockEvent->getSetting('fake'));
        $this->assertEquals(1, $blockEvent->getSetting('fake', 1));

    }
}