<?php
/**
 * NavbarMessageListDemoListener.php
 * avanzu-admin
 * Date: 23.02.14
 */

namespace Chamilo\AdminThemeBundle\EventListener;


use Chamilo\AdminThemeBundle\Event\MessageListEvent;
use Chamilo\AdminThemeBundle\Model\MessageModel;
use Chamilo\AdminThemeBundle\Model\UserModel;

class NavbarMessageListDemoListener {

    public function onListMessages(MessageListEvent $event) {

        foreach($this->getMessages() as $msg) {
            $event->addMessage($msg);
        }
    }

    protected function getMessages() {
        return array(
            new MessageModel(new UserModel('Karl kettenkit'),'Dude! do something!', new \DateTime('-3 days')),
            new MessageModel(new UserModel('Jack Trockendoc'),'This is some subject', new \DateTime('-10 month')),
        );
    }

}
