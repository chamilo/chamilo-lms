<?php
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
     * @var string
     *
     * @ORM\Column(name="course_code", type="string", length=40, nullable=false)
     */
    protected $courseCode;

    /**
     * @var string
     *
     * @ORM\Column(name="tool_id", type="string", length=100, nullable=false)
     */
    protected $toolId;

    /**
     * @var int
     *
     * @ORM\Column(name="ref_id", type="integer", nullable=false)
     */
    protected $refId;

    /**
     * @var int
     *
     * @ORM\Column(name="field_id", type="integer", nullable=false)
     */
    protected $fieldId;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=200, nullable=false)
     */
    protected $value;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * Set courseCode.
     *
     * @param string $courseCode
     *
     * @return SpecificFieldValues
     */
    public function setCourseCode($courseCode)
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
     * @param string $toolId
     *
     * @return SpecificFieldValues
     */
    public function setToolId($toolId)
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
     * @param int $refId
     *
     * @return SpecificFieldValues
     */
    public function setRefId($refId)
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
     * @param int $fieldId
     *
     * @return SpecificFieldValues
     */
    public function setFieldId($fieldId)
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
     * @param string $value
     *
     * @return SpecificFieldValues
     */
    public function setValue($value)
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
