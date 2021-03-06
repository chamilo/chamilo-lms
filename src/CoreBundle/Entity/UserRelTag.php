<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * UserRelTag.
 *
 * @ORM\Table(
 *     name="user_rel_tag",
 *     indexes={
 *         @ORM\Index(name="idx_urt_uid", columns={"user_id"}),
 *         @ORM\Index(name="idx_urt_tid", columns={"tag_id"})
 *     }
 * )
 * @ORM\Entity
 */
class UserRelTag
{
    use UserTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="userRelTags")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\Column(name="tag_id", type="integer", nullable=false)
     */
    protected int $tagId;

    /**
     * Set tagId.
     *
     * @return UserRelTag
     */
    public function setTagId(int $tagId)
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
