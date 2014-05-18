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
use Sonata\BlockBundle\Event\TextBlockListener;

use Sonata\AdminBundle\Admin\AdminInterface;
class TextBlockListenerTest extends \PHPUnit_Framework_TestCase
{

    public function testEvent()
    {
        $event = new BlockEvent();

        $listener = new TextBlockListener();
        $listener->onBlock($event);

        $this->assertCount(1, $event->getBlocks());

        $blocks = $event->getBlocks();

        $this->assertEquals('This block is coming from inline event from the template', $blocks[0]->getSetting('content'));
    }

    public function testEventWithAdmin()
    {

        $admin = $this->getMock('Sonata\AdminBundle\Admin\AdminInterface');
        $admin->expects($this->once())->method('getSubject');
        $admin->expects($this->once())->method('toString')->will($this->returnValue('fake object'));

        $event = new BlockEvent(array(
            'admin'  => $admin,
            'action' => 'edit'
        ));

        $listener = new TextBlockListener();
        $listener->onBlock($event);

        $this->assertCount(1, $event->getBlocks());

        $blocks = $event->getBlocks();

        $this->assertEquals('<p class=\'well\'>The admin subject is <strong>fake object</strong></p>', $blocks[0]->getSetting('content'));
    }
}