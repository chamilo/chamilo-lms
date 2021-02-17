<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CAnnouncement.
 *
 * @ORM\Table(name="c_announcement")
 * @ORM\Entity
 */
class CAnnouncement extends AbstractResource implements ResourceInterface
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
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="title", type="text", nullable=true)
     */
    protected string $title;

    /**
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected ?string $content;

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
     * @var ArrayCollection|CAnnouncementAttachment[]
     *
     * @ORM\OneToMany(
     *     targetEntity="CAnnouncementAttachment",
     *     mappedBy="announcement", cascade={"persist", "remove"}, orphanRemoval=true
     * )
     */
    protected $attachments;

    public function __construct()
    {
        $this->content = '';
        $this->attachments = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

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
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set content.
     */
    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Set endDate.
     *
     * @param \DateTime $endDate
     */
    public function setEndDate($endDate): self
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
     */
    public function setDisplayOrder($displayOrder): self
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
     */
    public function setEmailSent($emailSent): self
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

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }
}
