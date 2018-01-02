<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserRelTag
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
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="tag_id", type="integer", nullable=false)
     */
    private $tagId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set userId
     *
     * @param integer $userId
     * @return UserRelTag
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
     * Set tagId
     *
     * @param integer $tagId
     * @return UserRelTag
     */
    public function setTagId($tagId)
    {
        $this->tagId = $tagId;

        return $this;
    }

    /**
     * Get tagId
     *
     * @return integer
     */
    public function getTagId()
    {
        return $this->tagId;
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
