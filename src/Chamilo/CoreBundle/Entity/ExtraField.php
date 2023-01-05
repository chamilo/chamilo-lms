<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Database;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Sylius\Component\Attribute\Model\Attribute as BaseAttribute;

/**
 * Class ExtraField.
 *
 * @ORM\Entity
 * @ORM\Table(name="extra_field")
 *
 * @ORM\MappedSuperclass
 */
class ExtraField extends BaseAttribute
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
    public const TERMS_AND_CONDITION_TYPE = 14;
    public const FORUM_CATEGORY_TYPE = 15;
    public const FORUM_POST_TYPE = 16;
    public const EXERCISE_FIELD_TYPE = 17;
    public const TRACK_EXERCISE_FIELD_TYPE = 18;
    public const PORTFOLIO_TYPE = 19;
    public const LP_VIEW_TYPE = 20;
    public const COURSE_ANNOUNCEMENT = 21;
    public const MESSAGE_TYPE = 22;
    public const DOCUMENT_TYPE = 23;
    public const ATTENDANCE_CALENDAR_TYPE = 24;

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
        parent::__construct();
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

    /**
     * Retreives and returns the value stored in this extra field for an item.
     *
     * @param $itemId string|int the item identifier
     *
     * @return mixed|null the value if found, null if not found
     */
    public function getValueForItem($itemId)
    {
        $values = Database::getManager()->getRepository(
            "ChamiloCoreBundle:ExtraFieldValues"
        )->matching(
            Criteria::create()->where(
                Criteria::expr()->eq('field', $this)
            )->andWhere(
                Criteria::expr()->eq('itemId', $itemId)
            )
        );

        return count($values) === 1 ? $values[0] : null;
    }

    /**
     * Retreives and returns the value stored in this extra field for each item.
     *
     * @return array itemId => value
     */
    public function getValueForEachItem()
    {
        $values = [];
        /** @var ExtraFieldValues $value */
        foreach (Database::getManager()->getRepository(
            "ChamiloCoreBundle:ExtraFieldValues"
        )->matching(
            Criteria::create()->where(
                Criteria::expr()->eq('field', $this)
            )
        ) as $value) {
            $values[$value->getItemId()] = $value->getValue();
        }

        return $values;
    }

    /**
     * Retreives and returns the value stored in each extra field for each item.
     *
     * @param ExtraField[] $extraFields
     * @param int[]        $itemIds
     *
     * @return array itemId => [ fieldId => value ]
     */
    public static function getValueForEachExtraFieldForEachItem($extraFields, $itemIds)
    {
        $values = [];
        /** @var ExtraFieldValues $value */
        foreach (Database::getManager()->getRepository(
            "ChamiloCoreBundle:ExtraFieldValues"
        )->matching(
            Criteria::create()->where(
                Criteria::expr()->in('field', $extraFields)
            )->andWhere(
                Criteria::expr()->in('itemId', $itemIds)
            )
        ) as $value) {
            $itemId = $value->getItemId();
            if (!array_key_exists($itemId, $values)) {
                $values[$itemId] = [];
            }
            $values[$itemId][$value->getField()->getId()] = $value->getValue();
        }

        return $values;
    }

    /**
     * Retreives extra fields from a list of variables.
     *
     * @param string[] $variables      extra field variables
     * @param int      $extraFieldType such as self::COURSE_FIELD_TYPE
     *
     * @return ExtraField[] found extra fields
     */
    public static function getExtraFieldsFromVariables($variables, $extraFieldType)
    {
        /** @var ExtraField[] $extraFields */
        $extraFields = Database::getManager()->getRepository("ChamiloCoreBundle:ExtraField")->matching(
            Criteria::create()->where(
                Criteria::expr()->eq('extraFieldType', $extraFieldType)
            )->andWhere(
                Criteria::expr()->in('variable', $variables)
            )
        );

        return $extraFields;
    }

    /**
     * Retreives and sorts extra fields from a list of variables.
     *
     * @param string[] $variables      extra field variables
     * @param int      $extraFieldType such as self::COURSE_FIELD_TYPE
     *
     * @return ExtraField[] the sorted extra fields, in the same order as the variables
     */
    public static function getExtraFieldsFromVariablesOrdered($variables, $extraFieldType)
    {
        /** @var ExtraField[] $sortedExtraFields */
        $sortedExtraFields = [];
        /** @var ExtraField[] $extraFields */
        $extraFields = self::getExtraFieldsFromVariables($variables, $extraFieldType);
        foreach ($variables as $variable) {
            foreach ($extraFields as $extraField) {
                if ($extraField->getVariable() === $variable) {
                    $sortedExtraFields[] = $extraField;
                    break;
                }
            }
        }

        return $sortedExtraFields;
    }
}
