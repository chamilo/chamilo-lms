<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CRole.
 *
 * @ORM\Table(
 *  name="c_role",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CRole
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
     * @var string
     *
     * @ORM\Column(name="role_name", type="string", length=250, nullable=false)
     */
    protected $roleName;

    /**
     * @var string
     *
     * @ORM\Column(name="role_comment", type="text", nullable=true)
     */
    protected $roleComment;

    /**
     * @var bool
     *
     * @ORM\Column(name="default_role", type="boolean", nullable=true)
     */
    protected $defaultRole;

    /**
     * @var int
     *
     * @ORM\Column(name="role_id", type="integer")
     */
    protected $roleId;

    /**
     * Set roleName.
     *
     * @param string $roleName
     *
     * @return CRole
     */
    public function setRoleName($roleName)
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
     * @param string $roleComment
     *
     * @return CRole
     */
    public function setRoleComment($roleComment)
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
     * @param bool $defaultRole
     *
     * @return CRole
     */
    public function setDefaultRole($defaultRole)
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
     * @param int $roleId
     *
     * @return CRole
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
     * Set cId.
     *
     * @param int $cId
     *
     * @return CRole
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
