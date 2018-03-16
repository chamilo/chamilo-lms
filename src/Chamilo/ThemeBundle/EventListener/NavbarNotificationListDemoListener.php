<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\EventListener;

use Chamilo\ThemeBundle\Event\NotificationListEvent;
use Chamilo\ThemeBundle\Model\NotificationModel;

/**
 * Class NavbarNotificationListDemoListener.
 *
 * @package Chamilo\ThemeBundle\EventListener
 */
class NavbarNotificationListDemoListener
{
    public function onListNotifications(NotificationListEvent $event)
    {
        foreach ($this->getNotifications() as $notify) {
            $event->addNotification($notify);
        }
    }

    protected function getNotifications()
    {
        return [
            new NotificationModel('some notification'),
            new NotificationModel('some more notices', 'success'),
        ];
    }
}
