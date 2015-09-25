<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Tests\Model;

use Sonata\BlockBundle\Model\Block;

class BlockTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTtl()
    {
        $block = new Block;

        $this->assertFalse($block->hasChildren());

        $child1 = $this->getMockBuilder('Sonata\BlockBundle\Model\Block')->getMock();
        $child1->expects($this->once())->method('getTtl')->will($this->returnValue(100));

        $child2 = $this->getMockBuilder('Sonata\BlockBundle\Model\Block')->getMock();
        $child2->expects($this->once())->method('getTtl')->will($this->returnValue(50));

        $child3 = $this->getMockBuilder('Sonata\BlockBundle\Model\Block')->getMock();
        $child3->expects($this->once())->method('getTtl')->will($this->returnValue(65));

        $block->addChildren($child1);
        $block->addChildren($child2);
        $block->addChildren($child3);

        $this->assertEquals(50, $block->getTtl());

        $this->assertTrue($block->hasChildren());
    }

    public function testSetterGetter()
    {
        $time = new \DateTime;
        $parent = $this->getMockBuilder('Sonata\BlockBundle\Model\Block')->getMock();

        $block = new Block;

        $block->setName('my.block.name');
        $block->setCreatedAt($time);
        $block->setUpdatedAt($time);
        $block->setEnabled(true);
        $block->setPosition(1);
        $block->setType('foo.bar');
        $block->setParent($parent);

        $this->assertEquals('my.block.name', $block->getName());
        $this->assertEquals($time, $block->getCreatedAt());
        $this->assertEquals($time, $block->getUpdatedAt());
        $this->assertTrue($block->getEnabled());
        $this->assertEquals(1, $block->getPosition());
        $this->assertEquals('foo.bar', $block->getType());
        $this->assertEquals($parent, $block->getParent());

    }

    public function testSetting()
    {
        $block = new Block();
        $block->setSetting('foo', 'bar');
        $this->assertEquals('void', $block->getSetting('fake', 'void'));
        $this->assertNull($block->getSetting('fake'));
        $this->assertEquals('bar', $block->getSetting('foo'));
    }
}
