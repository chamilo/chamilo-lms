<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Repository\CForumThreadFeedbackRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'c_forum_thread_feedback')]
#[ORM\Index(name: 'idx_cft_feedback_thread_user', columns: ['thread_id', 'user_id'])]
#[ORM\Index(name: 'idx_cft_feedback_author', columns: ['author_id'])]
#[ORM\Index(name: 'idx_cft_feedback_qualification', columns: ['qualification_id'])]
#[ORM\UniqueConstraint(name: 'uniq_cft_feedback_legacy_comment', columns: ['legacy_comment_id'])]
#[ORM\Entity(repositoryClass: CForumThreadFeedbackRepository::class)]
class CForumThreadFeedback
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[ORM\ManyToOne(targetEntity: CForumThread::class)]
    #[ORM\JoinColumn(name: 'thread_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    protected CForumThread $thread;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $user = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $author = null;

    #[ORM\ManyToOne(targetEntity: CForumThreadQualify::class)]
    #[ORM\JoinColumn(name: 'qualification_id', referencedColumnName: 'iid', nullable: true, onDelete: 'SET NULL')]
    protected ?CForumThreadQualify $qualification = null;

    #[ORM\Column(name: 'legacy_comment_id', type: 'integer', nullable: true)]
    protected ?int $legacyCommentId = null;

    #[ORM\Column(name: 'feedback', type: 'text', nullable: true)]
    protected ?string $feedback = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    protected DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
    protected DateTimeInterface $updatedAt;

    public function __construct()
    {
        $now = new DateTime();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getThread(): CForumThread
    {
        return $this->thread;
    }

    public function setThread(CForumThread $thread): self
    {
        $this->thread = $thread;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getQualification(): ?CForumThreadQualify
    {
        return $this->qualification;
    }

    public function setQualification(?CForumThreadQualify $qualification): self
    {
        $this->qualification = $qualification;

        return $this;
    }

    public function getLegacyCommentId(): ?int
    {
        return $this->legacyCommentId;
    }

    public function setLegacyCommentId(?int $legacyCommentId): self
    {
        $this->legacyCommentId = $legacyCommentId;

        return $this;
    }

    public function getFeedback(): ?string
    {
        return $this->feedback;
    }

    public function setFeedback(?string $feedback): self
    {
        $this->feedback = $feedback;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
