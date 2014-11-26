<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Attribute\Model\Attribute as BaseAttribute;

/**
 * ExtraField
 *
 * @ORM\MappedSuperclass
 */
class ExtraField extends BaseAttribute
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
     * @ORM\Column(name="field_type", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    protected $fieldType;

    /**
     * @var string
     *
     * @ORM\Column(name="field_variable", type="string", length=64, precision=0, scale=0, nullable=false, unique=false)
     */
    protected $fieldVariable;

    /**
     * @var string
     *
     * @ORM\Column(name="field_display_text", type="string", length=64, precision=0, scale=0, nullable=true, unique=false)
     */
    protected $fieldDisplayText;

    /**
     * @var string
     *
     * @ORM\Column(name="field_default_value", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    protected $fieldDefaultValue;

    /**
     * @var integer
     *
     * @ORM\Column(name="field_order", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    protected $fieldOrder;

    /**
     * @var boolean
     *
     * @ORM\Column(name="field_visible", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    protected $fieldVisible;

    /**
     * @var boolean
     *
     * @ORM\Column(name="field_changeable", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    protected $fieldChangeable;

    /**
     * @var boolean
     *
     * @ORM\Column(name="field_filter", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    protected $fieldFilter;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="tms", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    protected $tms;

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
     * @return ExtraField
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
     * @return ExtraField
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
     * @return ExtraField
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
     * @return ExtraField
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
     * @return ExtraField
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
     * @return ExtraField
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
     * @return ExtraField
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
     * @return ExtraField
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
     * @return ExtraField
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
     * @inheritdoc
     */
    public function getType()
    {
        return $this->getFieldType();
    }

    /**
     * @inheritdoc
     */
    public function setType($type)
    {
        return $this->setFieldType($type);
    }

    /**
     * @return string
     */
    public function getFieldTypeToString()
    {
        switch ($this->fieldType) {
            case \ExtraField::FIELD_TYPE_TEXT:
            case \ExtraField::FIELD_TYPE_TEXTAREA:
                return 'text';
            case \ExtraField::FIELD_TYPE_RADIO:
            case \ExtraField::FIELD_TYPE_SELECT:
                return 'choice';
            default:
                return 'text';
        }
    }

}
