<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\EventListener;

use Chamilo\ThemeBundle\Event\MessageListEvent;
use Chamilo\ThemeBundle\Model\MessageModel;
use Chamilo\ThemeBundle\Model\UserModel;

/**
 * Class NavbarMessageListDemoListener.
 *
 * @package Chamilo\ThemeBundle\EventListener
 */
class NavbarMessageListDemoListener
{
    public function onListMessages(MessageListEvent $event)
    {
        foreach ($this->getMessages() as $msg) {
            $event->addMessage($msg);
        }
    }

    protected function getMessages()
    {
        return [
            new MessageModel(new UserModel('Karl kettenkit'), 'Dude! do something!', new \DateTime('-3 days')),
            new MessageModel(new UserModel('Jack Trockendoc'), 'This is some subject', new \DateTime('-10 month')),
        ];
    }
}
