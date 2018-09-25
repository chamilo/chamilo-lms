<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CPermissionTask.
 *
 * @ORM\Table(
 *  name="c_permission_task",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CPermissionTask
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
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="task_id", type="integer", nullable=false)
     */
    protected $taskId;

    /**
     * @var string
     *
     * @ORM\Column(name="tool", type="string", length=250, nullable=false)
     */
    protected $tool;

    /**
     * @var string
     *
     * @ORM\Column(name="action", type="string", length=250, nullable=false)
     */
    protected $action;

    /**
     * Set taskId.
     *
     * @param int $taskId
     *
     * @return CPermissionTask
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

    /**
     * Set tool.
     *
     * @param string $tool
     *
     * @return CPermissionTask
     */
    public function setTool($tool)
    {
        $this->tool = $tool;

        return $this;
    }

    /**
     * Get tool.
     *
     * @return string
     */
    public function getTool()
    {
        return $this->tool;
    }

    /**
     * Set action.
     *
     * @param string $action
     *
     * @return CPermissionTask
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action.
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CPermissionTask
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CPermissionTask
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
}
