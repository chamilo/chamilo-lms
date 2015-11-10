<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CRole
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
     * @var string
     *
     * @ORM\Column(name="role_name", type="string", length=250, nullable=false)
     */
    private $roleName;

    /**
     * @var string
     *
     * @ORM\Column(name="role_comment", type="text", nullable=true)
     */
    private $roleComment;

    /**
     * @var boolean
     *
     * @ORM\Column(name="default_role", type="boolean", nullable=true)
     */
    private $defaultRole;

    /**
     * @var integer
     *
     * @ORM\Column(name="role_id", type="integer")
     */
    private $roleId;

    /**
     * Set roleName
     *
     * @param string $roleName
     * @return CRole
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
     * @return CRole
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
     * @return CRole
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

    /**
     * Set roleId
     *
     * @param integer $roleId
     * @return CRole
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
     * Set cId
     *
     * @param integer $cId
     * @return CRole
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
}
