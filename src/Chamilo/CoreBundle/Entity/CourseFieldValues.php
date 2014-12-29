<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * CourseFieldValues
 *
 * @ORM\Table(name="course_field_values")
 * @ORM\Entity
 */
class CourseFieldValues extends ExtraFieldValues
{
    /**
     * @ORM\OneToOne(targetEntity="Chamilo\CoreBundle\Entity\SessionField")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id")
     */
    protected $field;

    /**
     * @var integer
     *
     * @ORM\Column(name="course_code", type="string", precision=0, scale=0, nullable=false, unique=false)
     */
    protected $courseCode;

    /**
     * @var string
     * @Gedmo\Versioned
     *
     * @ORM\Column(name="field_value", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    protected $fieldValue;

    /**
     * Set fieldValue
     *
     * @param string $fieldValue
     * @return ExtraFieldValues
     */
    public function setFieldValue($fieldValue)
    {
        $this->fieldValue = $fieldValue;

        return $this;
    }

    /**
     * Get fieldValue
     *
     * @return string
     */
    public function getFieldValue()
    {
        return $this->fieldValue;
    }

    /**
     * Set questionId
     *
     * @param integer $questionId
     *
     * @return QuestionFieldValues
     */
    public function setCourseCode($code)
    {
        $this->courseCode = $code;
        return $this;
    }

    /**
     * Get questionId
     *
     * @return integer
     */
    public function getCourseCode()
    {
        return $this->courseCode;
    }
}
