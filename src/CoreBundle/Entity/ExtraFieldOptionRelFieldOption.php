<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="extra_field_option_rel_field_option",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="idx", columns={"field_id", "role_id", "field_option_id", "related_field_option_id"})
 *     }
 * )
 * @ORM\Entity
 */
class ExtraFieldOptionRelFieldOption
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="ExtraFieldOptions")
     * @ORM\JoinColumn(name="field_option_id", referencedColumnName="id")
     */
    protected ExtraFieldOptions $extraFieldOption;

    /**
     * @ORM\ManyToOne(targetEntity="ExtraFieldOptions")
     * @ORM\JoinColumn(name="related_field_option_id", referencedColumnName="id")
     */
    protected ExtraFieldOptions $relatedFieldOption;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\ExtraField")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id")
     */
    protected ExtraField $field;

    /**
     * @ORM\Column(name="role_id", type="integer", nullable=true, unique=false)
     */
    protected ?int $roleId = null;

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
     * Set roleId.
     *
     * @return ExtraFieldOptionRelFieldOption
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

    public function getExtraFieldOption(): ExtraFieldOptions
    {
        return $this->extraFieldOption;
    }

    public function setExtraFieldOption(ExtraFieldOptions $extraFieldOption): self
    {
        $this->extraFieldOption = $extraFieldOption;

        return $this;
    }

    public function getRelatedFieldOption(): ExtraFieldOptions
    {
        return $this->relatedFieldOption;
    }

    public function setRelatedFieldOption(ExtraFieldOptions $relatedFieldOption): self
    {
        $this->relatedFieldOption = $relatedFieldOption;

        return $this;
    }

    public function getField(): ExtraField
    {
        return $this->field;
    }

    public function setField(ExtraField $field): self
    {
        $this->field = $field;

        return $this;
    }
}
