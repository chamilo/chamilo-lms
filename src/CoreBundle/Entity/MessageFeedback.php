<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * Class MessageFeedback.
 *
 * @package Chamilo\CoreBundle\Entity
 *
 * @ORM\Table(
 *     name="message_feedback",
 *     indexes={
 *         @Index(name="idx_message_feedback_uid_mid", columns={"message_id", "user_id"})
 *     }
 * )
 * @ORM\Entity()
 */
class MessageFeedback
{
    #[ORM\Column(name: "id", type: "bigint")]
    #[ORM\Id()]
    #[ORM\GeneratedValue()]
    private int $id;

    #[ORM\ManyToOne(targetEntity: "Chamilo\CoreBundle\Entity\Message", inversedBy: "likes")]
    #[ORM\JoinColumn(name: "message_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private Message $message;

    #[ORM\ManyToOne(targetEntity: "Chamilo\CoreBundle\Entity\User")]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private User $user;

    #[ORM\Column(name: "liked", type: "boolean", options: ["default" => false])]
    private bool $liked = false;

    #[ORM\Column(name: "disliked", type: "boolean", options: ["default" => false])]
    private bool $disliked = false;

    #[ORM\Column(name: "updated_at", type: "datetime", nullable: false)]
    private \DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function setMessage(Message $message): self
    {
        $this->message = $message;

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

    public function isLiked(): bool
    {
        return $this->liked;
    }

    public function setLiked(bool $liked): self
    {
        $this->liked = $liked;

        return $this;
    }

    public function isDisliked(): bool
    {
        return $this->disliked;
    }

    public function setDisliked(bool $disliked): self
    {
        $this->disliked = $disliked;

        return $this;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
