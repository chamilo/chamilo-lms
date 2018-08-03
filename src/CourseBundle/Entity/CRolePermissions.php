<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CRolePermissions.
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
     * @var bool
     *
     * @ORM\Column(name="default_perm", type="boolean", nullable=false)
     */
    protected $defaultPerm;

    /**
     * @var int
     *
     * @ORM\Column(name="role_id", type="integer")
     */
    protected $roleId;

    /**
     * @var string
     *
     * @ORM\Column(name="tool", type="string", length=250)
     */
    protected $tool;

    /**
     * @var string
     *
     * @ORM\Column(name="action", type="string", length=50)
     */
    protected $action;

    /**
     * Set defaultPerm.
     *
     * @param bool $defaultPerm
     *
     * @return CRolePermissions
     */
    public function setDefaultPerm($defaultPerm)
    {
        $this->defaultPerm = $defaultPerm;

        return $this;
    }

    /**
     * Get defaultPerm.
     *
     * @return bool
     */
    public function getDefaultPerm()
    {
        return $this->defaultPerm;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CRolePermissions
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
     * @return CRolePermissions
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
     * Set roleId.
     *
     * @param int $roleId
     *
     * @return CRolePermissions
     */
    public function setRoleId($roleId)
    {
        $this->roleId = $roleId;

        return $this;
    }

    /**
     * Get roleId.
     *
     * @return int
     */
    public function getRoleId()
    {
        return $this->roleId;
    }

    /**
     * Set tool.
     *
     * @param string $tool
     *
     * @return CRolePermissions
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
     * @return CRolePermissions
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
}
