<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCPermissionTask
 *
 * @Table(name="c_permission_task")
 * @Entity
 */
class EntityCPermissionTask
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="task_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $taskId;

    /**
     * @var string
     *
     * @Column(name="tool", type="string", length=250, precision=0, scale=0, nullable=false, unique=false)
     */
    private $tool;

    /**
     * @var string
     *
     * @Column(name="action", type="string", length=250, precision=0, scale=0, nullable=false, unique=false)
     */
    private $action;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCPermissionTask
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer 
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return EntityCPermissionTask
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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

    /**
     * Set taskId
     *
     * @param integer $taskId
     * @return EntityCPermissionTask
     */
    public function setTaskId($taskId)
    {
        $this->taskId = $taskId;

        return $this;
    }

    /**
     * Get taskId
     *
     * @return integer 
     */
    public function getTaskId()
    {
        return $this->taskId;
    }

    /**
     * Set tool
     *
     * @param string $tool
     * @return EntityCPermissionTask
     */
    public function setTool($tool)
    {
        $this->tool = $tool;

        return $this;
    }

    /**
     * Get tool
     *
     * @return string 
     */
    public function getTool()
    {
        return $this->tool;
    }

    /**
     * Set action
     *
     * @param string $action
     * @return EntityCPermissionTask
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return string 
     */
    public function getAction()
    {
        return $this->action;
    }
}
