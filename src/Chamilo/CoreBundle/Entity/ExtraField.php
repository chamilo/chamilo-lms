<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Sylius\Component\Attribute\Model\Attribute as BaseAttribute;

/**
 * Class ExtraField
 *
 * @ORM\Entity
 * @ORM\Table(name="extra_field")
 *
 * @ORM\MappedSuperclass
 */
class ExtraField extends BaseAttribute
{
    const USER_FIELD_TYPE = 1;
    const COURSE_FIELD_TYPE = 2;
    const SESSION_FIELD_TYPE = 3;
    const QUESTION_FIELD_TYPE = 4;
    const CALENDAR_FIELD_TYPE = 5;
    const LP_FIELD_TYPE = 6;
    const LP_ITEM_FIELD_TYPE = 7;
    const SKILL_FIELD_TYPE = 8;
    const WORK_FIELD_TYPE = 9;
    const CAREER_FIELD_TYPE = 10;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="extra_field_type", type="integer", nullable=false, unique=false)
     */
    protected $extraFieldType;

    /**
     * @var integer
     *
     * @ORM\Column(name="field_type", type="integer", nullable=false, unique=false)
     */
    protected $fieldType;

    /**
     * @var string
     *
     * @ORM\Column(name="variable", type="string", length=255, nullable=false, unique=false)
     */
    protected $variable;

    /**
     * @var string
     *
     * @ORM\Column(name="display_text", type="string", length=255, nullable=true, unique=false)
     */
    protected $displayText;

    /**
     * @var string
     *
     * @ORM\Column(name="default_value", type="text", nullable=true, unique=false)
     */
    protected $defaultValue;

    /**
     * @var integer
     *
     * @ORM\Column(name="field_order", type="integer", nullable=true, unique=false)
     */
    protected $fieldOrder;

    /**
     * @var boolean
     *
     * @ORM\Column(name="visible_to_self", type="boolean", nullable=true, unique=false)
     */
    protected $visibleToSelf;

    /**
     * @var boolean
     *
     * @ORM\Column(name="visible_to_others", type="boolean", nullable=true, unique=false)
     */
    protected $visibleToOthers;

    /**
     * @var boolean
     *
     * @ORM\Column(name="changeable", type="boolean", nullable=true, unique=false)
     */
    protected $changeable;

    /**
     * @var boolean
     *
     * @ORM\Column(name="filter", type="boolean", nullable=true, unique=false)
     */
    protected $filter;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\ExtraFieldOptions", mappedBy="field")
     **/
    protected $options;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * ExtraField constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->visibleToOthers = false;
        $this->visibleToSelf = false;
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

    /**
     * @return int
     */
    public function getExtraFieldType()
    {
        return $this->extraFieldType;
    }

    /**
     * @param int $extraFieldType
     *
     * @return $this
     */
    public function setExtraFieldType($extraFieldType)
    {
        $this->extraFieldType = $extraFieldType;

        return $this;
    }

    /**
     * @return int
     */
    public function getFieldType()
    {
        return $this->fieldType;
    }

    /**
     * @param int $fieldType
     *
     * @return $this
     */
    public function setFieldType($fieldType)
    {
        $this->fieldType = $fieldType;

        return $this;
    }

    /**
     * @return string
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * @param string $variable
     *
     * @return $this
     */
    public function setVariable($variable)
    {
        $this->variable = $variable;

        return $this;
    }

    /**
     * @param bool $translated Optional. Whether translate the display text
     * @return string
     */
    public function getDisplayText($translated = true)
    {
        if ($translated) {
            return \ExtraField::translateDisplayName($this->variable, $this->displayText);
        }

        return $this->displayText;
    }

    /**
     * @param string $displayText
     *
     * @return $this
     */
    public function setDisplayText($displayText)
    {
        $this->displayText = $displayText;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param string $defaultValue
     *
     * @return $this
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    /**
     * @return int
     */
    public function getFieldOrder()
    {
        return $this->fieldOrder;
    }

    /**
     * @param int $fieldOrder
     *
     * @return $this
     */
    public function setFieldOrder($fieldOrder)
    {
        $this->fieldOrder = $fieldOrder;

        return $this;
    }

     /**
     * @return boolean
     */
    public function isChangeable()
    {
        return $this->changeable;
    }

    /**
     * @param boolean $changeable
     *
     * @return $this
     */
    public function setChangeable($changeable)
    {
        $this->changeable = $changeable;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isFilter()
    {
        return $this->filter;
    }

    /**
     * @param boolean $filter
     *
     * @return $this
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isVisibleToSelf()
    {
        return $this->visibleToSelf;
    }

    /**
     * @param boolean $visibleToSelf
     * @return ExtraField
     */
    public function setVisibleToSelf($visibleToSelf)
    {
        $this->visibleToSelf = $visibleToSelf;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isVisibleToOthers()
    {
        return $this->visibleToOthers;
    }

    /**
     * @param boolean $visibleToOthers
     * @return ExtraField
     */
    public function setVisibleToOthers($visibleToOthers)
    {
        $this->visibleToOthers = $visibleToOthers;

        return $this;
    }

    /**
     * @return string
     */
    public function getTypeToString()
    {
        switch ($this->type) {
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
