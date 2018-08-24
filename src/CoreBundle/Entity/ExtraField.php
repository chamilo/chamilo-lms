<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class ExtraField.
 *
 * @ORM\Entity
 * @ORM\Table(name="extra_field")
 *
 * @ORM\MappedSuperclass
 */
class ExtraField // extends BaseAttribute
{
    public const USER_FIELD_TYPE = 1;
    public const COURSE_FIELD_TYPE = 2;
    public const SESSION_FIELD_TYPE = 3;
    public const QUESTION_FIELD_TYPE = 4;
    public const CALENDAR_FIELD_TYPE = 5;
    public const LP_FIELD_TYPE = 6;
    public const LP_ITEM_FIELD_TYPE = 7;
    public const SKILL_FIELD_TYPE = 8;
    public const WORK_FIELD_TYPE = 9;
    public const CAREER_FIELD_TYPE = 10;
    public const USER_CERTIFICATE = 11;
    public const SURVEY_FIELD_TYPE = 12;
    public const SCHEDULED_ANNOUNCEMENT = 13;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="extra_field_type", type="integer", nullable=false, unique=false)
     */
    protected $extraFieldType;

    /**
     * @var int
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
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

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
     * @var int
     *
     * @ORM\Column(name="field_order", type="integer", nullable=true, unique=false)
     */
    protected $fieldOrder;

    /**
     * @var bool
     *
     * @ORM\Column(name="visible_to_self", type="boolean", nullable=true, unique=false)
     */
    protected $visibleToSelf;

    /**
     * @var bool
     *
     * @ORM\Column(name="visible_to_others", type="boolean", nullable=true, unique=false)
     */
    protected $visibleToOthers;

    /**
     * @var bool
     *
     * @ORM\Column(name="changeable", type="boolean", nullable=true, unique=false)
     */
    protected $changeable;

    /**
     * @var bool
     *
     * @ORM\Column(name="filter", type="boolean", nullable=true, unique=false)
     */
    protected $filter;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\ExtraFieldOptions", mappedBy="field")
     */
    protected $options;

    /**
     * @var \DateTime
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
        //parent::__construct();
        $this->visibleToOthers = false;
        $this->visibleToSelf = false;
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
     *
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
     * @return bool
     */
    public function isChangeable()
    {
        return $this->changeable;
    }

    /**
     * @param bool $changeable
     *
     * @return $this
     */
    public function setChangeable($changeable)
    {
        $this->changeable = $changeable;

        return $this;
    }

    /**
     * @return bool
     */
    public function isFilter()
    {
        return $this->filter;
    }

    /**
     * @param bool $filter
     *
     * @return $this
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * @return bool
     */
    public function isVisibleToSelf()
    {
        return $this->visibleToSelf;
    }

    /**
     * @param bool $visibleToSelf
     *
     * @return ExtraField
     */
    public function setVisibleToSelf($visibleToSelf)
    {
        $this->visibleToSelf = $visibleToSelf;

        return $this;
    }

    /**
     * @return bool
     */
    public function isVisibleToOthers()
    {
        return $this->visibleToOthers;
    }

    /**
     * @param bool $visibleToOthers
     *
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
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return ExtraField
     */
    public function setDescription(string $description): ExtraField
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getTypeToString(): string
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
