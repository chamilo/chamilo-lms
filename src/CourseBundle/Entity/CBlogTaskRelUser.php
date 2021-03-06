<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * CBlogTaskRelUser.
 *
 * @ORM\Table(
 *     name="c_blog_task_rel_user",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="user", columns={"user_id"}),
 *         @ORM\Index(name="task", columns={"task_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CBlogTaskRelUser
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
     * @ORM\Column(name="blog_id", type="integer")
     */
    protected int $blogId;

    /**
     * @ORM\Column(name="target_date", type="date", nullable=false)
     */
    protected DateTime $targetDate;

    /**
     * @ORM\Column(name="user_id", type="integer")
     */
    protected int $userId;

    /**
     * @ORM\Column(name="task_id", type="integer")
     */
    protected int $taskId;

    /**
     * Set targetDate.
     *
     * @return CBlogTaskRelUser
     */
    public function setTargetDate(DateTime $targetDate)
    {
        $this->targetDate = $targetDate;

        return $this;
    }

    /**
     * Get targetDate.
     *
     * @return DateTime
     */
    public function getTargetDate()
    {
        return $this->targetDate;
    }

    /**
     * Set cId.
     *
     * @return CBlogTaskRelUser
     */
    public function setCId(int $cId)
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
     * @return CBlogTaskRelUser
     */
    public function setBlogId(int $blogId)
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
     * Set userId.
     *
     * @return CBlogTaskRelUser
     */
    public function setUserId(int $userId)
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
     * Set taskId.
     *
     * @return CBlogTaskRelUser
     */
    public function setTaskId(int $taskId)
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
