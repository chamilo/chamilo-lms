<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCRolePermissions
 *
 * @Table(name="c_role_permissions")
 * @Entity
 */
class EntityCRolePermissions
{
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
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="role_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $roleId;

    /**
     * @var string
     *
     * @Column(name="tool", type="string", length=250, precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $tool;

    /**
     * @var string
     *
     * @Column(name="action", type="string", length=50, precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $action;

    /**
     * @var boolean
     *
     * @Column(name="default_perm", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $defaultPerm;


    /**
     * Set id
     *
     * @param integer $id
     * @return EntityCRolePermissions
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
     * Set cId
     *
     * @param integer $cId
     * @return EntityCRolePermissions
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
     * Set roleId
     *
     * @param integer $roleId
     * @return EntityCRolePermissions
     */
    public function setRoleId($roleId)
    {
        $this->roleId = $roleId;

        return $this;
    }

    /**
     * Get roleId
     *
     * @return integer 
     */
    public function getRoleId()
    {
        return $this->roleId;
    }

    /**
     * Set tool
     *
     * @param string $tool
     * @return EntityCRolePermissions
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
     * @return EntityCRolePermissions
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

    /**
     * Set defaultPerm
     *
     * @param boolean $defaultPerm
     * @return EntityCRolePermissions
     */
    public function setDefaultPerm($defaultPerm)
    {
        $this->defaultPerm = $defaultPerm;

        return $this;
    }

    /**
     * Get defaultPerm
     *
     * @return boolean 
     */
    public function getDefaultPerm()
    {
        return $this->defaultPerm;
    }
}
