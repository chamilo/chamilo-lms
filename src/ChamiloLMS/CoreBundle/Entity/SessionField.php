<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SessionField
 *
 * @ORM\Table(name="session_field")
 * @ORM\Entity
 */
class SessionField
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
     * @ORM\Column(name="field_type", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $fieldType;

    /**
     * @var string
     *
     * @ORM\Column(name="field_variable", type="string", length=64, precision=0, scale=0, nullable=false, unique=false)
     */
    private $fieldVariable;

    /**
     * @var string
     *
     * @ORM\Column(name="field_display_text", type="string", length=64, precision=0, scale=0, nullable=true, unique=false)
     */
    private $fieldDisplayText;

    /**
     * @var string
     *
     * @ORM\Column(name="field_default_value", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $fieldDefaultValue;

    /**
     * @var integer
     *
     * @ORM\Column(name="field_order", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $fieldOrder;

    /**
     * @var boolean
     *
     * @ORM\Column(name="field_visible", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $fieldVisible;

    /**
     * @var boolean
     *
     * @ORM\Column(name="field_changeable", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $fieldChangeable;

    /**
     * @var boolean
     *
     * @ORM\Column(name="field_filter", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $fieldFilter;

    /**
     * @var integer
     *
     * @ORM\Column(name="field_loggeable", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $fieldLoggeable;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="tms", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $tms;


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
     * Set fieldType
     *
     * @param integer $fieldType
     * @return SessionField
     */
    public function setFieldType($fieldType)
    {
        $this->fieldType = $fieldType;

        return $this;
    }

    /**
     * Get fieldType
     *
     * @return integer 
     */
    public function getFieldType()
    {
        return $this->fieldType;
    }

    /**
     * Set fieldVariable
     *
     * @param string $fieldVariable
     * @return SessionField
     */
    public function setFieldVariable($fieldVariable)
    {
        $this->fieldVariable = $fieldVariable;

        return $this;
    }

    /**
     * Get fieldVariable
     *
     * @return string 
     */
    public function getFieldVariable()
    {
        return $this->fieldVariable;
    }

    /**
     * Set fieldDisplayText
     *
     * @param string $fieldDisplayText
     * @return SessionField
     */
    public function setFieldDisplayText($fieldDisplayText)
    {
        $this->fieldDisplayText = $fieldDisplayText;

        return $this;
    }

    /**
     * Get fieldDisplayText
     *
     * @return string 
     */
    public function getFieldDisplayText()
    {
        return $this->fieldDisplayText;
    }

    /**
     * Set fieldDefaultValue
     *
     * @param string $fieldDefaultValue
     * @return SessionField
     */
    public function setFieldDefaultValue($fieldDefaultValue)
    {
        $this->fieldDefaultValue = $fieldDefaultValue;

        return $this;
    }

    /**
     * Get fieldDefaultValue
     *
     * @return string 
     */
    public function getFieldDefaultValue()
    {
        return $this->fieldDefaultValue;
    }

    /**
     * Set fieldOrder
     *
     * @param integer $fieldOrder
     * @return SessionField
     */
    public function setFieldOrder($fieldOrder)
    {
        $this->fieldOrder = $fieldOrder;

        return $this;
    }

    /**
     * Get fieldOrder
     *
     * @return integer 
     */
    public function getFieldOrder()
    {
        return $this->fieldOrder;
    }

    /**
     * Set fieldVisible
     *
     * @param boolean $fieldVisible
     * @return SessionField
     */
    public function setFieldVisible($fieldVisible)
    {
        $this->fieldVisible = $fieldVisible;

        return $this;
    }

    /**
     * Get fieldVisible
     *
     * @return boolean 
     */
    public function getFieldVisible()
    {
        return $this->fieldVisible;
    }

    /**
     * Set fieldChangeable
     *
     * @param boolean $fieldChangeable
     * @return SessionField
     */
    public function setFieldChangeable($fieldChangeable)
    {
        $this->fieldChangeable = $fieldChangeable;

        return $this;
    }

    /**
     * Get fieldChangeable
     *
     * @return boolean 
     */
    public function getFieldChangeable()
    {
        return $this->fieldChangeable;
    }

    /**
     * Set fieldFilter
     *
     * @param boolean $fieldFilter
     * @return SessionField
     */
    public function setFieldFilter($fieldFilter)
    {
        $this->fieldFilter = $fieldFilter;

        return $this;
    }

    /**
     * Get fieldFilter
     *
     * @return boolean 
     */
    public function getFieldFilter()
    {
        return $this->fieldFilter;
    }

    /**
     * Set fieldLoggeable
     *
     * @param integer $fieldLoggeable
     * @return SessionField
     */
    public function setFieldLoggeable($fieldLoggeable)
    {
        $this->fieldLoggeable = $fieldLoggeable;

        return $this;
    }

    /**
     * Get fieldLoggeable
     *
     * @return integer 
     */
    public function getFieldLoggeable()
    {
        return $this->fieldLoggeable;
    }

    /**
     * Set tms
     *
     * @param \DateTime $tms
     * @return SessionField
     */
    public function setTms($tms)
    {
        $this->tms = $tms;

        return $this;
    }

    /**
     * Get tms
     *
     * @return \DateTime 
     */
    public function getTms()
    {
        return $this->tms;
    }
}
