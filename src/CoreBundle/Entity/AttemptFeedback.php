<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'attempt_feedback')]
#[ORM\Entity]
class AttemptFeedback
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    protected Uuid $id;

    #[Assert\NotNull]
    #[ORM\ManyToOne(targetEntity: TrackEAttempt::class, inversedBy: 'attemptFeedbacks')]
    #[ORM\JoinColumn(name: 'attempt_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected TrackEAttempt $attempt;

    #[Assert\NotNull]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\ManyToOne(targetEntity: ResourceNode::class)]
    protected ?ResourceNode $resourceNode = null;

    #[ORM\Column(name: 'comment', type: 'text', nullable: false)]
    protected string $comment;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->comment = '';
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getAttempt(): TrackEAttempt
    {
        return $this->attempt;
    }

    public function setAttempt(TrackEAttempt $attempt): self
    {
        $this->attempt = $attempt;

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

    public function getResourceNode(): ?ResourceNode
    {
        return $this->resourceNode;
    }

    public function setResourceNode(?ResourceNode $resourceNode): self
    {
        $this->resourceNode = $resourceNode;

        return $this;
    }

    public function clearResourceNode(): self
    {
        // Detach the resource node reference when feedback file is removed.
        $this->resourceNode = null;

        return $this;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
}
