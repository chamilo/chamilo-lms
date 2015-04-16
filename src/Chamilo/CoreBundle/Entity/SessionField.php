<?php

namespace Chamilo\CoreBundle\Entity;

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
     * @ORM\Column(name="field_type", type="integer", nullable=false)
     */
    private $fieldType;

    /**
     * @var string
     *
     * @ORM\Column(name="field_variable", type="string", length=64, nullable=false)
     */
    private $fieldVariable;

    /**
     * @var string
     *
     * @ORM\Column(name="field_display_text", type="string", length=64, nullable=true)
     */
    private $fieldDisplayText;

    /**
     * @var string
     *
     * @ORM\Column(name="field_default_value", type="text", nullable=true)
     */
    private $fieldDefaultValue;

    /**
     * @var integer
     *
     * @ORM\Column(name="field_order", type="integer", nullable=true)
     */
    private $fieldOrder;

    /**
     * @var boolean
     *
     * @ORM\Column(name="field_visible", type="boolean", nullable=true)
     */
    private $fieldVisible;

    /**
     * @var boolean
     *
     * @ORM\Column(name="field_changeable", type="boolean", nullable=true)
     */
    private $fieldChangeable;

    /**
     * @var boolean
     *
     * @ORM\Column(name="field_filter", type="boolean", nullable=true)
     */
    private $fieldFilter;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="tms", type="datetime", nullable=false)
     */
    private $tms;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



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

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
