<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ExtraFieldOptionRelFieldOption
 *
 * @ORM\Table(
 *     name="extra_field_option_rel_field_option",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idx", columns={"field_id", "role_id", "field_option_id", "related_field_option_id"})}
 * )
 * @ORM\Entity
 */
class ExtraFieldOptionRelFieldOption
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

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
    protected $fieldOptionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="related_field_option_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    protected $relatedFieldOptionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="role_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    protected $roleId;

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
     * Set fieldId
     *
     * @param integer $fieldId
     * @return ExtraFieldOptionRelFieldOption
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
     * @return ExtraFieldOptionRelFieldOption
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

    /**
     * Set relatedFieldOptionId
     *
     * @param integer $relatedFieldOptionId
     * @return ExtraFieldOptionRelFieldOption
     */
    public function setRelatedFieldOptionId($relatedFieldOptionId)
    {
        $this->relatedFieldOptionId = $relatedFieldOptionId;

        return $this;
    }

    /**
     * Get relatedFieldOptionId
     *
     * @return integer
     */
    public function getRelatedFieldOptionId()
    {
        return $this->relatedFieldOptionId;
    }

    /**
     * Set roleId
     *
     * @param integer $roleId
     * @return ExtraFieldOptionRelFieldOption
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
}
