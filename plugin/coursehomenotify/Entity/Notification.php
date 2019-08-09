<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\CourseHomeNotify;

use Chamilo\CoreBundle\Entity\Course;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Notification.
 *
 * @package Chamilo\PluginBundle\Entity\CourseHomeNotify
 *
 * @ORM\Table(name="course_home_notify_notification")
 * @ORM\Entity()
 */
class Notification
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
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     */
    private $content;
    /**
     * @var string
     *
     * @ORM\Column(name="expiration_link", type="string")
     */
    private $expirationLink;
    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string")
     */
    private $hash;
    /**
     * @var Course
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $course;

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
     * @return Notification
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return Notification
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getExpirationLink()
    {
        return $this->expirationLink;
    }

    /**
     * @param string $expirationLink
     *
     * @return Notification
     */
    public function setExpirationLink($expirationLink)
    {
        $this->expirationLink = $expirationLink;

        return $this;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     *
     * @return Notification
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @param Course $course
     *
     * @return Notification
     */
    public function setCourse($course)
    {
        $this->course = $course;

        return $this;
    }
}
