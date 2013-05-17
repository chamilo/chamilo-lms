<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ExtraFieldOptionRelRole
 *
 * @ORM\Table(name="extra_field_option_rel_role", uniqueConstraints={@ORM\UniqueConstraint(name="idx", columns={"role_id", "field_id", "field_option_id"})})
 * @ORM\Entity
 */
class ExtraFieldOptionRelRole
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="role_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $roleId;

    /**
     * @var integer
     *
     * @ORM\Column(name="field_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $fieldId;

    /**
     * @var integer
     *
     * @ORM\Column(name="field_option_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $fieldOptionId;


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
     * Set roleId
     *
     * @param integer $roleId
     * @return EntityExtraFieldOptionRelRole
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
     * Set fieldId
     *
     * @param integer $fieldId
     * @return EntityExtraFieldOptionRelRole
     */
    public function setFieldId($fieldId)
    {
        $this->fieldId = $fieldId;

        return $this;
    }

    /**
     * Get fieldId
     *
     * @return integer
     */
    public function getFieldId()
    {
        return $this->fieldId;
    }

    /**
     * Set fieldOptionId
     *
     * @param integer $fieldOptionId
     * @return EntityExtraFieldOptionRelRole
     */
    public function setFieldOptionId($fieldOptionId)
    {
        $this->fieldOptionId = $fieldOptionId;

        return $this;
    }

    /**
     * Get fieldOptionId
     *
     * @return integer
     */
    public function getFieldOptionId()
    {
        return $this->fieldOptionId;
    }
}
