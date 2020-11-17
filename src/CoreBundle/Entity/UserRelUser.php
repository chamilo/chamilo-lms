<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
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
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="userRelUsers")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var int
     *
     * @ORM\Column(name="friend_user_id", type="integer", nullable=false)
     */
    protected $friendUserId;

    /**
     * @var int
     *
     * @ORM\Column(name="relation_type", type="integer", nullable=false)
     */
    protected $relationType;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_edit", type="datetime", nullable=true)
     */
    protected $lastEdit;

    /**
     * Set friendUserId.
     *
     * @param int $friendUserId
     *
     * @return UserRelUser
     */
    public function setFriendUserId($friendUserId)
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
     * @param int $relationType
     *
     * @return UserRelUser
     */
    public function setRelationType($relationType)
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
     * @param \DateTime $lastEdit
     *
     * @return UserRelUser
     */
    public function setLastEdit($lastEdit)
    {
        $this->lastEdit = $lastEdit;

        return $this;
    }

    /**
     * Get lastEdit.
     *
     * @return \DateTime
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
