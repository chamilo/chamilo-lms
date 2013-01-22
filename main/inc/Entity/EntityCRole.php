<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCRole
 *
 * @Table(name="c_role")
 * @Entity
 */
class EntityCRole
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
     * @Column(name="role_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $roleId;

    /**
     * @var string
     *
     * @Column(name="role_name", type="string", length=250, precision=0, scale=0, nullable=false, unique=false)
     */
    private $roleName;

    /**
     * @var string
     *
     * @Column(name="role_comment", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $roleComment;

    /**
     * @var boolean
     *
     * @Column(name="default_role", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $defaultRole;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCRole
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
     * @return EntityCRole
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
     * Set roleName
     *
     * @param string $roleName
     * @return EntityCRole
     */
    public function setRoleName($roleName)
    {
        $this->roleName = $roleName;

        return $this;
    }

    /**
     * Get roleName
     *
     * @return string 
     */
    public function getRoleName()
    {
        return $this->roleName;
    }

    /**
     * Set roleComment
     *
     * @param string $roleComment
     * @return EntityCRole
     */
    public function setRoleComment($roleComment)
    {
        $this->roleComment = $roleComment;

        return $this;
    }

    /**
     * Get roleComment
     *
     * @return string 
     */
    public function getRoleComment()
    {
        return $this->roleComment;
    }

    /**
     * Set defaultRole
     *
     * @param boolean $defaultRole
     * @return EntityCRole
     */
    public function setDefaultRole($defaultRole)
    {
        $this->defaultRole = $defaultRole;

        return $this;
    }

    /**
     * Get defaultRole
     *
     * @return boolean 
     */
    public function getDefaultRole()
    {
        return $this->defaultRole;
    }
}
