<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * UserRelUser.
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
     * @ORM\Column(name="friend_user_id", type="integer", nullable=false)
     */
    protected int $friendUserId;

    /**
     * @ORM\Column(name="relation_type", type="integer", nullable=false)
     */
    protected int $relationType;

    /**
     * @ORM\Column(name="last_edit", type="datetime", nullable=true)
     */
    protected ?DateTime $lastEdit = null;

    /**
     * Set friendUserId.
     *
     * @return UserRelUser
     */
    public function setFriendUserId(int $friendUserId)
    {
        $this->friendUserId = $friendUserId;

        return $this;
    }

    /**
     * Get friendUserId.
     *
     * @return int
     */
    public function getFriendUserId()
    {
        return $this->friendUserId;
    }

    /**
     * Set relationType.
     *
     * @return UserRelUser
     */
    public function setRelationType(int $relationType)
    {
        $this->relationType = $relationType;

        return $this;
    }

    /**
     * Get relationType.
     *
     * @return int
     */
    public function getRelationType()
    {
        return $this->relationType;
    }

    /**
     * Set lastEdit.
     *
     * @return UserRelUser
     */
    public function setLastEdit(DateTime $lastEdit)
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
