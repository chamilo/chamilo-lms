<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CForumMailcue.
 *
 * @ORM\Table(
 *     name="c_forum_mailcue",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="thread", columns={"thread_id"}),
 *         @ORM\Index(name="user", columns={"user_id"}),
 *         @ORM\Index(name="post", columns={"post_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CForumMailcue
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="thread_id", type="integer")
     */
    protected int $threadId;

    /**
     * @ORM\Column(name="user_id", type="integer")
     */
    protected int $userId;

    /**
     * @ORM\Column(name="post_id", type="integer")
     */
    protected int $postId;

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CForumMailcue
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set threadId.
     *
     * @param int $threadId
     *
     * @return CForumMailcue
     */
    public function setThreadId($threadId)
    {
        $this->threadId = $threadId;

        return $this;
    }

    /**
     * Get threadId.
     *
     * @return int
     */
    public function getThreadId()
    {
        return $this->threadId;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return CForumMailcue
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set postId.
     *
     * @param int $postId
     *
     * @return CForumMailcue
     */
    public function setPostId($postId)
    {
        $this->postId = $postId;

        return $this;
    }

    /**
     * Get postId.
     *
     * @return int
     */
    public function getPostId()
    {
        return $this->postId;
    }
}
