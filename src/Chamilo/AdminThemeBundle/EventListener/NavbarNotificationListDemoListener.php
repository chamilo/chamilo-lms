<?php
/**
 * NavbarNotificationListDemoListener.php
 * avanzu-admin
 * Date: 23.02.14
 */

namespace Chamilo\AdminThemeBundle\EventListener;


use Chamilo\AdminThemeBundle\Event\NotificationListEvent;
use Chamilo\AdminThemeBundle\Model\NotificationModel;

class NavbarNotificationListDemoListener {


    public function onListNotifications(NotificationListEvent $event) {

        foreach($this->getNotifications() as $notify){
            $event->addNotification($notify);
        }

    }

    protected function getNotifications() {
        return array(
            new NotificationModel('some notification'),
            new NotificationModel('some more notices', 'success'),
        );
    }

}
