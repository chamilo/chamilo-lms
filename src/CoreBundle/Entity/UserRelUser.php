<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserRelUser
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
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="friend_user_id", type="integer", nullable=false)
     */
    private $friendUserId;

    /**
     * @var integer
     *
     * @ORM\Column(name="relation_type", type="integer", nullable=false)
     */
    private $relationType;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_edit", type="datetime", nullable=true)
     */
    private $lastEdit;

    /**
     * Set userId
     *
     * @param integer $userId
     * @return UserRelUser
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set friendUserId
     *
     * @param integer $friendUserId
     * @return UserRelUser
     */
    public function setFriendUserId($friendUserId)
    {
        $this->friendUserId = $friendUserId;

        return $this;
    }

    /**
     * Get friendUserId
     *
     * @return integer
     */
    public function getFriendUserId()
    {
        return $this->friendUserId;
    }

    /**
     * Set relationType
     *
     * @param integer $relationType
     * @return UserRelUser
     */
    public function setRelationType($relationType)
    {
        $this->relationType = $relationType;

        return $this;
    }

    /**
     * Get relationType
     *
     * @return integer
     */
    public function getRelationType()
    {
        return $this->relationType;
    }

    /**
     * Set lastEdit
     *
     * @param \DateTime $lastEdit
     * @return UserRelUser
     */
    public function setLastEdit($lastEdit)
    {
        $this->lastEdit = $lastEdit;

        return $this;
    }

    /**
     * Get lastEdit
     *
     * @return \DateTime
     */
    public function getLastEdit()
    {
        return $this->lastEdit;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
