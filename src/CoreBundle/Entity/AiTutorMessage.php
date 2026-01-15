<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ai_tutor_message')]
#[ORM\Index(columns: ['conversation_id', 'created_at'], name: 'idx_ai_tutor_msg_conv_created')]
#[ORM\HasLifecycleCallbacks]
class AiTutorMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AiTutorConversation::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(name: 'conversation_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected AiTutorConversation $conversation;

    #[ORM\Column(name: 'role', type: 'string', length: 20, nullable: false)]
    protected string $role;

    #[ORM\Column(name: 'content', type: 'text', nullable: false)]
    protected string $content;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    protected DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        // Keep conversation timestamps in sync when a new message is stored.
        if (isset($this->conversation)) {
            $this->conversation->touchLastMessageAt();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConversation(): AiTutorConversation
    {
        return $this->conversation;
    }

    public function setConversation(AiTutorConversation $conversation): self
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
