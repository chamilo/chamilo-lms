<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\User;
use Cocur\Slugify\Slugify;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * CStudentPublicationComment.
 *
 * @ORM\Table(
 *     name="c_student_publication_comment",
 *     indexes={
 *     }
 * )
 * @ORM\Entity
 */
class CStudentPublicationComment extends AbstractResource implements ResourceInterface
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\ManyToOne(targetEntity="CStudentPublication", inversedBy="comments")
     * @ORM\JoinColumn(name="work_id", referencedColumnName="iid", onDelete="CASCADE")
     */
    protected CStudentPublication $publication;

    /**
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected ?string $comment = null;

    /**
     * @ORM\Column(name="file", type="string", length=255, nullable=true)
     */
    protected ?string $file = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\Column(name="sent_at", type="datetime", nullable=false)
     */
    protected DateTime $sentAt;

    public function __construct()
    {
        $this->sentAt = new DateTime();
    }

    public function __toString(): string
    {
        return (string) $this->getIid();
    }

    public function getIid(): int
    {
        return $this->iid;
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

    public function setFile(string $file): self
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file.
     *
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function setSentAt(DateTime $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getSentAt(): DateTime
    {
        return $this->sentAt;
    }

    public function getPublication(): CStudentPublication
    {
        return $this->publication;
    }

    public function setPublication(CStudentPublication $publication): self
    {
        $this->publication = $publication;

        return $this;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        $text = strip_tags($this->getComment());
        $slugify = new Slugify();
        $text = $slugify->slugify($text);

        return (string) substr($text, 0, 40);
    }

    public function setResourceName(string $name): self
    {
        return $this->setComment($name);
    }
}
