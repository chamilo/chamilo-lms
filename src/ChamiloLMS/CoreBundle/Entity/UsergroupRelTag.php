<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UsergroupRelTag
 *
 * @ORM\Table(name="usergroup_rel_tag", indexes={@ORM\Index(name="usergroup_rel_tag_usergroup_id", columns={"usergroup_id"}), @ORM\Index(name="usergroup_rel_tag_tag_id", columns={"tag_id"})})
 * @ORM\Entity
 */
class UsergroupRelTag
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="tag_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $tagId;

    /**
     * @var integer
     *
     * @ORM\Column(name="usergroup_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $usergroupId;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set tagId
     *
     * @param integer $tagId
     * @return UsergroupRelTag
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
     * Set usergroupId
     *
     * @param integer $usergroupId
     * @return UsergroupRelTag
     */
    public function setUsergroupId($usergroupId)
    {
        $this->usergroupId = $usergroupId;

        return $this;
    }

    /**
     * Get usergroupId
     *
     * @return integer
     */
    public function getUsergroupId()
    {
        return $this->usergroupId;
    }
}
