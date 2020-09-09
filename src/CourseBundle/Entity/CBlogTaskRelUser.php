<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * CBlogTaskRelUser.
 *
 * @ORM\Table(
 *  name="c_blog_task_rel_user",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="user", columns={"user_id"}),
 *      @ORM\Index(name="task", columns={"task_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CBlogTaskRelUser
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
     * @var int
     *
     * @ORM\Column(name="blog_id", type="integer")
     */
    protected $blogId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="target_date", type="date", nullable=false)
     */
    protected $targetDate;

    /**
     * @var User
     * @ORM\ManyToOne (
     *    targetEntity="Chamilo\CoreBundle\Entity\User",
     *    inversedBy="cBlogTaskRelUsers"
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
     * @ORM\Column(name="task_id", type="integer")
     */
    protected $taskId;

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
     * Set targetDate.
     *
     * @param \DateTime $targetDate
     *
     * @return CBlogTaskRelUser
     */
    public function setTargetDate($targetDate)
    {
        $this->targetDate = $targetDate;

        return $this;
    }

    /**
     * Get targetDate.
     *
     * @return \DateTime
     */
    public function getTargetDate()
    {
        return $this->targetDate;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CBlogTaskRelUser
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
     * Set blogId.
     *
     * @param int $blogId
     *
     * @return CBlogTaskRelUser
     */
    public function setBlogId($blogId)
    {
        $this->blogId = $blogId;

        return $this;
    }

    /**
     * Get blogId.
     *
     * @return int
     */
    public function getBlogId()
    {
        return $this->blogId;
    }

    /**
     * Set taskId.
     *
     * @param int $taskId
     *
     * @return CBlogTaskRelUser
     */
    public function setTaskId($taskId)
    {
        $this->taskId = $taskId;

        return $this;
    }

    /**
     * Get taskId.
     *
     * @return int
     */
    public function getTaskId()
    {
        return $this->taskId;
    }
}
