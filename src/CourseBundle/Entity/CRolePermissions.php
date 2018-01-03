<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CRolePermissions
 *
 * @ORM\Table(
 *  name="c_role_permissions",
 *  indexes={
 *      @ORM\Index(name="course", columns="c_id"),
 *      @ORM\Index(name="role", columns="role_id")
 *  }
 * )
 * @ORM\Entity
 */
class CRolePermissions
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    private $id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="default_perm", type="boolean", nullable=false)
     */
    private $defaultPerm;

    /**
     * @var integer
     *
     * @ORM\Column(name="role_id", type="integer")
     */
    private $roleId;

    /**
     * @var string
     *
     * @ORM\Column(name="tool", type="string", length=250)
     */
    private $tool;

    /**
     * @var string
     *
     * @ORM\Column(name="action", type="string", length=50)
     */
    private $action;

    /**
     * Set defaultPerm
     *
     * @param boolean $defaultPerm
     * @return CRolePermissions
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

    /**
     * Set id
     *
     * @param integer $id
     * @return CRolePermissions
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
     * @return CRolePermissions
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
     * @return CRolePermissions
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
     * @return CRolePermissions
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
     * @return CRolePermissions
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
