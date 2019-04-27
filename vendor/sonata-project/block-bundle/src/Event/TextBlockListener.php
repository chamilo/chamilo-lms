<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Event;

use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\BlockBundle\Model\Block;
use Symfony\Component\EventDispatcher\Event;

/**
 * This class is just a demo of how to define a Listener to generate a valid block instance used
 * to render a block from an event call using the sonata_block_render_event template helper.
 *
 * For instance, you can add an element on the top to each admin form with the following code:
 *
 *     text.listener:
 *        class: Sonata\BlockBundle\Event\TextBlockListener
 *        tags:
 *          - { name: kernel.event_listener, event: 'sonata.block.event.sonata.admin.edit.form.top', method: onBlock}
 */
class TextBlockListener
{
    /**
     * @param BlockEvent $event
     */
    public function onBlock(BlockEvent $event)
    {
        $content = 'This block is coming from inline event from the template';
        if ($event->getSetting('admin') instanceof AdminInterface && 'edit' == $event->getSetting('action')) {
            $admin = $event->getSetting('admin');

            $content = sprintf("<p class='well'>The admin subject is <strong>%s</strong></p>", $admin->toString($admin->getSubject()));
        }

        $block = new Block();
        $block->setId(uniqid());
        $block->setSettings([
            'content' => $event->getSetting('content', $content),
        ]);
        $block->setType('sonata.block.service.text');

        $event->addBlock($block);
    }
}
