<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCRoleGroup
 *
 * @Table(name="c_role_group")
 * @Entity
 */
class EntityCRoleGroup
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
     * @Column(name="group_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $groupId;

    /**
     * @var integer
     *
     * @Column(name="role_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $roleId;

    /**
     * @var string
     *
     * @Column(name="scope", type="string", length=20, precision=0, scale=0, nullable=false, unique=false)
     */
    private $scope;


    /**
     * Set id
     *
     * @param integer $id
     * @return EntityCRoleGroup
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
     * @return EntityCRoleGroup
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
     * Set groupId
     *
     * @param integer $groupId
     * @return EntityCRoleGroup
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId
     *
     * @return integer 
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set roleId
     *
     * @param integer $roleId
     * @return EntityCRoleGroup
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
     * Set scope
     *
     * @param string $scope
     * @return EntityCRoleGroup
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Get scope
     *
     * @return string 
     */
    public function getScope()
    {
        return $this->scope;
    }
}
