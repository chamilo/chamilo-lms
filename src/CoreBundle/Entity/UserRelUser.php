<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Associations between users (friends).
 *
 * @ORM\Table(name="user_rel_user", indexes={
 *     @ORM\Index(name="idx_user_rel_user__user", columns={"user_id"}),
 *     @ORM\Index(name="idx_user_rel_user__friend_user", columns={"friend_user_id"}),
 *     @ORM\Index(name="idx_user_rel_user__user_friend_user", columns={"user_id", "friend_user_id"})
 * })
 * @ORM\Entity
 */
class UserRelUser
{
    use UserTrait;

    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="userRelUsers")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="friend_user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $friend;

    /**
     * @ORM\Column(name="relation_type", type="integer", nullable=false)
     */
    protected int $relationType;

    /**
     * @ORM\Column(name="last_edit", type="datetime", nullable=true)
     */
    protected ?DateTime $lastEdit = null;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getFriend(): User
    {
        return $this->friend;
    }

    public function setFriend(User $friend): self
    {
        $this->friend = $friend;

        return $this;
    }

    public function setRelationType(int $relationType): self
    {
        $this->relationType = $relationType;

        return $this;
    }

    public function getRelationType(): ?int
    {
        return $this->relationType;
    }

    public function setLastEdit(DateTime $lastEdit): self
    {
        $this->lastEdit = $lastEdit;

        return $this;
    }

    /**
     * Get lastEdit.
     *
     * @return DateTime
     */
    public function getLastEdit()
    {
        return $this->lastEdit;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
