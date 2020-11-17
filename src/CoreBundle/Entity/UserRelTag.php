<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
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
    use UserTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="userRelTags")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var int
     *
     * @ORM\Column(name="tag_id", type="integer", nullable=false)
     */
    protected $tagId;

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
