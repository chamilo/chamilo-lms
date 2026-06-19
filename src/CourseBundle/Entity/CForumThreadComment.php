<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Stores legacy comments attached to forum threads and user qualification flows.
 */
#[ORM\Table(name: 'message_comment')]
#[ORM\Index(name: 'idx_message_comment_forum_thread', columns: ['forum_id', 'thread_id'])]
#[ORM\Index(name: 'idx_message_comment_receiver', columns: ['receiver_id'])]
#[ORM\Index(name: 'idx_message_comment_sender', columns: ['sender_id'])]
#[ORM\Entity]
class CForumThreadComment
{
    #[ORM\Column(name: 'id', type: 'bigint')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?string $id = null;

    #[ORM\Column(name: 'sender_id', type: 'bigint', nullable: false)]
    protected string $senderId;

    #[ORM\Column(name: 'receiver_id', type: 'bigint', nullable: false)]
    protected string $receiverId;

    #[ORM\Column(name: 'forum_id', type: 'bigint', nullable: false)]
    protected string $forumId;

    #[ORM\Column(name: 'thread_id', type: 'bigint', nullable: false)]
    protected string $threadId;

    #[ORM\Column(name: 'comment', type: 'blob', nullable: false)]
    protected mixed $comment;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getSenderId(): string
    {
        return $this->senderId;
    }

    public function setSenderId(string|int $senderId): self
    {
        $this->senderId = (string) $senderId;

        return $this;
    }

    public function getReceiverId(): string
    {
        return $this->receiverId;
    }

    public function setReceiverId(string|int $receiverId): self
    {
        $this->receiverId = (string) $receiverId;

        return $this;
    }

    public function getForumId(): string
    {
        return $this->forumId;
    }

    public function setForumId(string|int $forumId): self
    {
        $this->forumId = (string) $forumId;

        return $this;
    }

    public function getThreadId(): string
    {
        return $this->threadId;
    }

    public function setThreadId(string|int $threadId): self
    {
        $this->threadId = (string) $threadId;

        return $this;
    }

    public function getComment(): mixed
    {
        return $this->comment;
    }

    public function setComment(mixed $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
}
