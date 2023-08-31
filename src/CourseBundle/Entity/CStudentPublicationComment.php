<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Repository\CStudentPublicationCommentRepository;
use Cocur\Slugify\Slugify;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Stringable;

#[ORM\Table(name: 'c_student_publication_comment')]
#[ORM\Entity(repositoryClass: CStudentPublicationCommentRepository::class)]
class CStudentPublicationComment extends AbstractResource implements ResourceInterface, Stringable
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;

    #[ORM\ManyToOne(targetEntity: CStudentPublication::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'work_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected CStudentPublication $publication;

    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    protected ?string $comment = null;

    #[ORM\Column(name: 'file', type: 'string', length: 255, nullable: true)]
    protected ?string $file = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\Column(name: 'sent_at', type: 'datetime', nullable: false)]
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

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(string $file): self
    {
        $this->file = $file;

        return $this;
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

    public function getSentAt(): DateTime
    {
        return $this->sentAt;
    }

    public function setSentAt(DateTime $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
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

        $text = Slugify::create()->slugify($text);

        return substr($text, 0, 40);
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function setResourceName(string $name): self
    {
        return $this->setComment($name);
    }
}
