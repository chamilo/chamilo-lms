<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntitySpecificFieldValues
 *
 * @Table(name="specific_field_values")
 * @Entity
 */
class EntitySpecificFieldValues
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="course_code", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $courseCode;

    /**
     * @var string
     *
     * @Column(name="tool_id", type="string", length=100, precision=0, scale=0, nullable=false, unique=false)
     */
    private $toolId;

    /**
     * @var integer
     *
     * @Column(name="ref_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $refId;

    /**
     * @var integer
     *
     * @Column(name="field_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $fieldId;

    /**
     * @var string
     *
     * @Column(name="value", type="string", length=200, precision=0, scale=0, nullable=false, unique=false)
     */
    private $value;


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
     * Set courseCode
     *
     * @param string $courseCode
     * @return EntitySpecificFieldValues
     */
    public function setCourseCode($courseCode)
    {
        $this->courseCode = $courseCode;

        return $this;
    }

    /**
     * Get courseCode
     *
     * @return string 
     */
    public function getCourseCode()
    {
        return $this->courseCode;
    }

    /**
     * Set toolId
     *
     * @param string $toolId
     * @return EntitySpecificFieldValues
     */
    public function setToolId($toolId)
    {
        $this->toolId = $toolId;

        return $this;
    }

    /**
     * Get toolId
     *
     * @return string 
     */
    public function getToolId()
    {
        return $this->toolId;
    }

    /**
     * Set refId
     *
     * @param integer $refId
     * @return EntitySpecificFieldValues
     */
    public function setRefId($refId)
    {
        $this->refId = $refId;

        return $this;
    }

    /**
     * Get refId
     *
     * @return integer 
     */
    public function getRefId()
    {
        return $this->refId;
    }

    /**
     * Set fieldId
     *
     * @param integer $fieldId
     * @return EntitySpecificFieldValues
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
     * Set value
     *
     * @param string $value
     * @return EntitySpecificFieldValues
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }
}
