<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\Event;

use Chamilo\ThemeBundle\Model\NotificationInterface;

/**
 * Class NotificationListEvent.
 *
 * @package Chamilo\ThemeBundle\Event
 */
class NotificationListEvent extends ThemeEvent
{
    /**
     * @var array
     */
    protected $notifications = [];

    protected $total = 0;

    /**
     * @return array
     */
    public function getNotifications()
    {
        return $this->notifications;
    }

    /**
     * @return $this
     */
    public function addNotification(NotificationInterface $notificationInterface)
    {
        $this->notifications[] = $notificationInterface;

        return $this;
    }

    /**
     * @param int $total
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->total == 0 ? sizeof($this->notifications) : $this->total;
    }
}
