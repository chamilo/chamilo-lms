<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * CForumNotification.
 *
 * @ORM\Table(
 *  name="c_forum_notification",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="thread", columns={"thread_id"}),
 *      @ORM\Index(name="post", columns={"post_id"}),
 *      @ORM\Index(name="user_id", columns={"user_id"}),
 *      @ORM\Index(name="forum_id", columns={"forum_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CForumNotification
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var User
     * @ORM\ManyToOne (
     *    targetEntity="Chamilo\CoreBundle\Entity\User",
     *    inversedBy="cForumNotifications"
     * )
     * @ORM\JoinColumn(
     *    name="user_id",
     *    referencedColumnName="id",
     *    onDelete="CASCADE"
     * )
     */
    protected $user;

    /**
     * @var int
     *
     * @ORM\Column(name="forum_id", type="integer")
     */
    protected $forumId;

    /**
     * @var int
     *
     * @ORM\Column(name="thread_id", type="integer")
     */
    protected $threadId;

    /**
     * @var int
     *
     * @ORM\Column(name="post_id", type="integer")
     */
    protected $postId;

    public function __construct()
    {
        $this->forumId = 0;
        $this->threadId = 0;
        $this->postId = 0;
    }

    /**
     * Get user.
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set user.
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CForumNotification
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
     * Set forumId.
     *
     * @param int $forumId
     *
     * @return CForumNotification
     */
    public function setForumId($forumId)
    {
        $this->forumId = $forumId;

        return $this;
    }

    /**
     * Get forumId.
     *
     * @return int
     */
    public function getForumId()
    {
        return $this->forumId;
    }

    /**
     * Set threadId.
     *
     * @param int $threadId
     *
     * @return CForumNotification
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
     * Set postId.
     *
     * @param int $postId
     *
     * @return CForumNotification
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
