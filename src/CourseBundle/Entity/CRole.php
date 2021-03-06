<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CRole.
 *
 * @ORM\Table(
 *     name="c_role",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CRole
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
     * @ORM\Column(name="role_name", type="string", length=250, nullable=false)
     */
    protected string $roleName;

    /**
     * @ORM\Column(name="role_comment", type="text", nullable=true)
     */
    protected ?string $roleComment = null;

    /**
     * @ORM\Column(name="default_role", type="boolean", nullable=true)
     */
    protected ?bool $defaultRole = null;

    /**
     * @ORM\Column(name="role_id", type="integer")
     */
    protected int $roleId;

    /**
     * Set roleName.
     *
     * @return CRole
     */
    public function setRoleName(string $roleName)
    {
        $this->roleName = $roleName;

        return $this;
    }

    /**
     * Get roleName.
     *
     * @return string
     */
    public function getRoleName()
    {
        return $this->roleName;
    }

    /**
     * Set roleComment.
     *
     * @return CRole
     */
    public function setRoleComment(string $roleComment)
    {
        $this->roleComment = $roleComment;

        return $this;
    }

    /**
     * Get roleComment.
     *
     * @return string
     */
    public function getRoleComment()
    {
        return $this->roleComment;
    }

    /**
     * Set defaultRole.
     *
     * @return CRole
     */
    public function setDefaultRole(bool $defaultRole)
    {
        $this->defaultRole = $defaultRole;

        return $this;
    }

    /**
     * Get defaultRole.
     *
     * @return bool
     */
    public function getDefaultRole()
    {
        return $this->defaultRole;
    }

    /**
     * Set roleId.
     *
     * @return CRole
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
     * Set cId.
     *
     * @return CRole
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
