<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * CAnnouncementAttachment.
 *
 * @ORM\Table(name="c_announcement_attachment")
 * @ORM\Entity
 */
class CAnnouncementAttachment extends AbstractResource implements ResourceInterface
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="path", type="string", length=255, nullable=false)
     */
    protected string $path;

    /**
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected ?string $comment = null;

    /**
     * @ORM\Column(name="size", type="integer", nullable=false)
     */
    protected int $size;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CAnnouncement", cascade={"persist"})
     * @ORM\JoinColumn(name="announcement_id", referencedColumnName="iid", onDelete="CASCADE" )
     */
    protected CAnnouncement $announcement;

    /**
     * @ORM\Column(name="filename", type="string", length=255, nullable=false)
     */
    protected string $filename;

    public function __toString(): string
    {
        return $this->getFilename();
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    public function getAnnouncement(): CAnnouncement
    {
        return $this->announcement;
    }

    public function setAnnouncement(CAnnouncement $announcement): self
    {
        $this->announcement = $announcement;

        return $this;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getFilename();
    }

    public function setResourceName(string $name): self
    {
        return $this->setFilename($name);
    }
}
