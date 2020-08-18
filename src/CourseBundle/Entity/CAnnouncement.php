<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceToCourseInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * CAnnouncement.
 *
 * @ORM\Table(name="c_announcement")
 * @ORM\Entity
 */
class CAnnouncement extends AbstractResource implements ResourceInterface, ResourceToCourseInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="text", nullable=true)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected $content;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="date", nullable=true)
     */
    protected $endDate;

    /**
     * @var int
     *
     * @ORM\Column(name="display_order", type="integer", nullable=false)
     */
    protected $displayOrder;

    /**
     * @var bool
     *
     * @ORM\Column(name="email_sent", type="boolean", nullable=true)
     */
    protected $emailSent;

    /**
     * @var CAnnouncementAttachment[]
     *
     * @ORM\OneToMany(targetEntity="CAnnouncementAttachment", mappedBy="announcement", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $attachments;

    public function __toString(): string
    {
        return $this->getTitle();
    }

    /**
     * @return CAnnouncementAttachment[]
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @param CAnnouncementAttachment[] $attachments
     */
    public function setAttachments(array $attachments): self
    {
        $this->attachments = $attachments;

        return $this;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return CAnnouncement
     */
    public function setTitle($title)
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
        return (string) $this->title;
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return CAnnouncement
     */
    public function setContent($content)
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
     * Set endDate.
     *
     * @param \DateTime $endDate
     *
     * @return CAnnouncement
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate.
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set displayOrder.
     *
     * @param int $displayOrder
     *
     * @return CAnnouncement
     */
    public function setDisplayOrder($displayOrder)
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    /**
     * Get displayOrder.
     *
     * @return int
     */
    public function getDisplayOrder()
    {
        return $this->displayOrder;
    }

    /**
     * Set emailSent.
     *
     * @param bool $emailSent
     *
     * @return CAnnouncement
     */
    public function setEmailSent($emailSent)
    {
        $this->emailSent = $emailSent;

        return $this;
    }

    /**
     * Get emailSent.
     *
     * @return bool
     */
    public function getEmailSent()
    {
        return $this->emailSent;
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    /**
     * Resource identifier.
     */
    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }
}
