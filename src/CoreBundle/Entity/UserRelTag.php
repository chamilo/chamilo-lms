<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserRelTag.
 *
 * @ORM\Table(
 *  name="user_rel_tag",
 *  indexes={
 *      @ORM\Index(name="idx_urt_uid", columns={"user_id"}),
 *      @ORM\Index(name="idx_urt_tid", columns={"tag_id"})
 *  }
 * )
 * @ORM\Entity
 */
class UserRelTag
{
    /**
     * @var User
     * @ORM\ManyToOne (
     *    targetEntity="Chamilo\CoreBundle\Entity\User",
     *    inversedBy="userRelTag"
     * )
     * @ORM\JoinColumn(
     *    name="user_id",
     *    referencedColumnName="id",
     *    onDelete="CASCADE"
     * )
     */
    protected $user;

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }
    /**
     * @var int
     *
     * @ORM\Column(name="tag_id", type="integer", nullable=false)
     */
    protected $tagId;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * Set tagId.
     *
     * @param int $tagId
     *
     * @return UserRelTag
     */
    public function setTagId($tagId)
    {
        $this->tagId = $tagId;

        return $this;
    }

    /**
     * Get tagId.
     *
     * @return int
     */
    public function getTagId()
    {
        return $this->tagId;
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
