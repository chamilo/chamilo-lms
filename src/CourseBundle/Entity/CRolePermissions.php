<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CRolePermissions.
 *
 * @ORM\Table(
 *     name="c_role_permissions",
 *     indexes={
 *         @ORM\Index(name="course", columns="c_id"),
 *         @ORM\Index(name="role", columns="role_id")
 *     }
 * )
 * @ORM\Entity
 */
class CRolePermissions
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
     * @ORM\Column(name="default_perm", type="boolean", nullable=false)
     */
    protected bool $defaultPerm;

    /**
     * @ORM\Column(name="role_id", type="integer")
     */
    protected int $roleId;

    /**
     * @ORM\Column(name="tool", type="string", length=250)
     */
    protected string $tool;

    /**
     * @ORM\Column(name="action", type="string", length=50)
     */
    protected string $action;

    /**
     * Set defaultPerm.
     *
     * @return CRolePermissions
     */
    public function setDefaultPerm(bool $defaultPerm)
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
     * Set cId.
     *
     * @return CRolePermissions
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
     * Set roleId.
     *
     * @return CRolePermissions
     */
    public function setRoleId(int $roleId)
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
     * @return CRolePermissions
     */
    public function setTool(string $tool)
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
     * @return CRolePermissions
     */
    public function setAction(string $action)
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
