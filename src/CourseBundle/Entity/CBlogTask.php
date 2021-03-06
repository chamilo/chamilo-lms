<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CBlogTask.
 *
 * @ORM\Table(
 *     name="c_blog_task",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CBlogTask
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="task_id", type="integer")
     */
    protected int $taskId;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="blog_id", type="integer", nullable=false)
     */
    protected int $blogId;

    /**
     * @ORM\Column(name="title", type="string", length=250, nullable=false)
     */
    protected string $title;

    /**
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    protected string $description;

    /**
     * @ORM\Column(name="color", type="string", length=10, nullable=false)
     */
    protected string $color;

    /**
     * @ORM\Column(name="system_task", type="boolean", nullable=false)
     */
    protected bool $systemTask;

    /**
     * Set blogId.
     *
     * @return CBlogTask
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
     * Set title.
     *
     * @return CBlogTask
     */
    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description.
     *
     * @return CBlogTask
     */
    public function setDescription(string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set color.
     *
     * @return CBlogTask
     */
    public function setColor(string $color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color.
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set systemTask.
     *
     * @return CBlogTask
     */
    public function setSystemTask(bool $systemTask)
    {
        $this->systemTask = $systemTask;

        return $this;
    }

    /**
     * Get systemTask.
     *
     * @return bool
     */
    public function getSystemTask()
    {
        return $this->systemTask;
    }

    /**
     * Set taskId.
     *
     * @return CBlogTask
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

    /**
     * Set cId.
     *
     * @return CBlogTask
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
}
