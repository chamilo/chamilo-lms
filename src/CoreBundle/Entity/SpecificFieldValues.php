<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SpecificFieldValues.
 *
 * @ORM\Table(name="specific_field_values")
 * @ORM\Entity
 */
class SpecificFieldValues
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * @ORM\Column(name="course_code", type="string", length=40, nullable=false)
     */
    protected string $courseCode;

    /**
     * @ORM\Column(name="tool_id", type="string", length=100, nullable=false)
     */
    protected string $toolId;

    /**
     * @ORM\Column(name="ref_id", type="integer", nullable=false)
     */
    protected int $refId;

    /**
     * @ORM\Column(name="field_id", type="integer", nullable=false)
     */
    protected int $fieldId;

    /**
     * @ORM\Column(name="value", type="string", length=200, nullable=false)
     */
    protected string $value;

    /**
     * Set courseCode.
     *
     * @return SpecificFieldValues
     */
    public function setCourseCode(string $courseCode)
    {
        $this->courseCode = $courseCode;

        return $this;
    }

    /**
     * Get courseCode.
     *
     * @return string
     */
    public function getCourseCode()
    {
        return $this->courseCode;
    }

    /**
     * Set toolId.
     *
     * @return SpecificFieldValues
     */
    public function setToolId(string $toolId)
    {
        $this->toolId = $toolId;

        return $this;
    }

    /**
     * Get toolId.
     *
     * @return string
     */
    public function getToolId()
    {
        return $this->toolId;
    }

    /**
     * Set refId.
     *
     * @return SpecificFieldValues
     */
    public function setRefId(int $refId)
    {
        $this->refId = $refId;

        return $this;
    }

    /**
     * Get refId.
     *
     * @return int
     */
    public function getRefId()
    {
        return $this->refId;
    }

    /**
     * Set fieldId.
     *
     * @return SpecificFieldValues
     */
    public function setFieldId(int $fieldId)
    {
        $this->fieldId = $fieldId;

        return $this;
    }

    /**
     * Get fieldId.
     *
     * @return int
     */
    public function getFieldId()
    {
        return $this->fieldId;
    }

    /**
     * Set value.
     *
     * @return SpecificFieldValues
     */
    public function setValue(string $value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
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
}
