<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Notification.
 *
 * @ORM\Table(
 *     name="notification",
 *     indexes={
 *         @ORM\Index(name="mail_notify_sent_index", columns={"sent_at"}),
 *         @ORM\Index(
 *             name="mail_notify_freq_index",
 *             columns={"sent_at", "send_freq", "created_at"}
 *         )
 *     }
 * )
 * @ORM\Entity
 */
class Notification
{
    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * @ORM\Column(name="dest_user_id", type="integer", nullable=false)
     */
    protected int $destUserId;

    /**
     * @ORM\Column(name="dest_mail", type="string", length=255, nullable=true)
     */
    protected ?string $destMail = null;

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    protected ?string $title = null;

    /**
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected ?string $content = null;

    /**
     * @ORM\Column(name="send_freq", type="smallint", nullable=true)
     */
    protected ?int $sendFreq = null;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected DateTime $createdAt;

    /**
     * @ORM\Column(name="sent_at", type="datetime", nullable=true)
     */
    protected ?DateTime $sentAt = null;

    /**
     * Set destUserId.
     *
     * @return Notification
     */
    public function setDestUserId(int $destUserId)
    {
        $this->destUserId = $destUserId;

        return $this;
    }

    /**
     * Get destUserId.
     *
     * @return int
     */
    public function getDestUserId()
    {
        return $this->destUserId;
    }

    /**
     * Set destMail.
     *
     * @return Notification
     */
    public function setDestMail(string $destMail)
    {
        $this->destMail = $destMail;

        return $this;
    }

    /**
     * Get destMail.
     *
     * @return string
     */
    public function getDestMail()
    {
        return $this->destMail;
    }

    /**
     * Set title.
     *
     * @return Notification
     */
    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set content.
     *
     * @return Notification
     */
    public function setContent(string $content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set sendFreq.
     *
     * @return Notification
     */
    public function setSendFreq(int $sendFreq)
    {
        $this->sendFreq = $sendFreq;

        return $this;
    }

    /**
     * Get sendFreq.
     *
     * @return int
     */
    public function getSendFreq()
    {
        return $this->sendFreq;
    }

    /**
     * Set createdAt.
     *
     * @return Notification
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set sentAt.
     *
     * @return Notification
     */
    public function setSentAt(DateTime $sentAt)
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    /**
     * Get sentAt.
     *
     * @return DateTime
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
