<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\CourseHomeNotify;

use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class NotificationRelUser.
 *
 * @package Chamilo\PluginBundle\Entity\CourseHomeNotify
 *
 * @ORM\Table(name="course_home_notify_notification_rel_user")
 * @ORM\Entity()
 */
class NotificationRelUser
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    private $id = 0;
    /**
     * @var Notification
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\PluginBundle\Entity\CourseHomeNotify\Notification")
     * @ORM\JoinColumn(name="notification_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $notification;
    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return NotificationRelUser
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Notification
     */
    public function getNotification()
    {
        return $this->notification;
    }

    /**
     * @return NotificationRelUser
     */
    public function setNotification(Notification $notification)
    {
        $this->notification = $notification;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return NotificationRelUser
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }
}
