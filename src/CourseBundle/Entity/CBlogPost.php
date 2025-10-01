<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\State\CBlogAssignAuthorProcessor;
use Chamilo\CourseBundle\Repository\CBlogPostRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            security: "is_granted('ROLE_USER')",
            processor: CBlogAssignAuthorProcessor::class
        ),
        new Patch(security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_TEACHER') or (object.getAuthor() != null and object.getAuthor() === user)"),
        new Delete(security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_TEACHER') or (object.getAuthor() != null and object.getAuthor() === user)"),
    ],
    normalizationContext: ['groups' => ['blog_post:read']],
    denormalizationContext: ['groups' => ['blog_post:write']],
    paginationEnabled: true
)]
#[ORM\Entity(repositoryClass: CBlogPostRepository::class)]
#[ORM\Table(name: 'c_blog_post')]
#[ORM\HasLifecycleCallbacks]
class CBlogPost
{
    #[Groups(['blog_post:read'])]
    #[ORM\Id, ORM\Column(type: 'integer'), ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Groups(['blog_post:read','blog_post:write'])]
    #[ORM\Column(type: 'string', length: 250)]
    protected string $title;

    #[Groups(['blog_post:read','blog_post:write'])]
    #[ORM\Column(name: 'full_text', type: 'text')]
    protected string $fullText;

    #[Groups(['blog_post:read'])]
    #[ORM\Column(name: 'date_creation', type: 'datetime')]
    protected DateTime $dateCreation;

    #[Groups(['blog_post:read'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?User $author = null;

    #[Groups(['blog_post:read','blog_post:write'])]
    #[ORM\ManyToOne(targetEntity: CBlog::class)]
    #[ORM\JoinColumn(name: 'blog_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected ?CBlog $blog = null;

    #[Groups(['blog_post:read'])]
    #[ORM\OneToMany(mappedBy: 'post', targetEntity: CBlogAttachment::class, cascade: ['persist', 'remove'])]
    protected Collection $attachments;

    public function __construct()
    {
        $this->attachments = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function prePersistSetDate(): void
    {
        if (!isset($this->dateCreation)) {
            $this->dateCreation = new DateTime();
        }
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getFullText(): string
    {
        return $this->fullText;
    }

    public function setFullText(string $fullText): self
    {
        $this->fullText = $fullText;

        return $this;
    }

    public function getDateCreation(): DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(DateTime $dateCreation): self
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author ?? null;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function getBlog(): ?CBlog
    {
        return $this->blog;
    }

    public function setBlog(?CBlog $blog): self
    {
        $this->blog = $blog;

        return $this;
    }

    /** @return Collection<int, CBlogAttachment> */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(CBlogAttachment $attachment): self
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setPost($this);
            if ($this->blog && $attachment->getBlog() !== $this->blog) {
                $attachment->setBlog($this->blog);
            }
        }

        return $this;
    }

    public function removeAttachment(CBlogAttachment $attachment): self
    {
        if ($this->attachments->removeElement($attachment)) {
            if ($attachment->getPost() === $this) {
                $attachment->setPost(null);
            }
        }

        return $this;
    }

    #[Groups(['blog_post:read'])]
    public function getAuthorInfo(): array
    {
        $u = $this->getAuthor();
        if (!$u) {
            return ['id' => null, 'name' => '—'];
        }
        $name = method_exists($u, 'getFullName') ? $u->getFullName()
            : (method_exists($u, 'getUsername') ? $u->getUsername() : 'User');

        return [
            'id'   => method_exists($u, 'getId') ? $u->getId() : null,
            'name' => $name,
        ];
    }
}
