<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="extra_field")
 *
 * @ORM\MappedSuperclass
 */
class ExtraField
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

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(name="extra_field_type", type="integer")
     */
    protected int $extraFieldType;

    /**
     * @ORM\Column(name="field_type", type="integer")
     */
    protected int $fieldType;

    /**
     * @ORM\Column(name="variable", type="string", length=255)
     */
    #[Assert\NotBlank]
    protected string $variable;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description;

    /**
     * @ORM\Column(name="display_text", type="string", length=255, nullable=true, unique=false)
     */
    protected ?string $displayText = null;

    /**
     * @ORM\Column(name="helper_text", type="text", nullable=true, unique=false)
     */
    protected ?string $helperText = null;

    /**
     * @ORM\Column(name="default_value", type="text", nullable=true, unique=false)
     */
    protected ?string $defaultValue = null;

    /**
     * @ORM\Column(name="field_order", type="integer", nullable=true, unique=false)
     */
    protected ?int $fieldOrder = null;

    /**
     * @ORM\Column(name="visible_to_self", type="boolean", nullable=true, unique=false)
     */
    protected ?bool $visibleToSelf;

    /**
     * @ORM\Column(name="visible_to_others", type="boolean", nullable=true, unique=false)
     */
    protected ?bool $visibleToOthers;

    /**
     * @ORM\Column(name="changeable", type="boolean", nullable=true, unique=false)
     */
    protected ?bool $changeable = null;

    /**
     * @ORM\Column(name="filter", type="boolean", nullable=true, unique=false)
     */
    protected ?bool $filter = null;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\ExtraFieldOptions", mappedBy="field")
     *
     * @var ExtraFieldOptions[]|Collection
     */
    protected Collection $options;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\Tag", mappedBy="field")
     *
     * @var Tag[]|Collection
     */
    protected Collection $tags;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected DateTime $createdAt;

    public function __construct()
    {
        $this->options = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->description = '';
        $this->visibleToOthers = false;
        $this->visibleToSelf = false;
        $this->changeable = false;
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

    public function getExtraFieldType(): int
    {
        return $this->extraFieldType;
    }

    public function setExtraFieldType(int $extraFieldType): self
    {
        $this->extraFieldType = $extraFieldType;

        return $this;
    }

    public function getFieldType(): int
    {
        return $this->fieldType;
    }

    public function setFieldType(int $fieldType): self
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

    public function setVariable(string $variable): self
    {
        $this->variable = $variable;

        return $this;
    }

    /**
     * @param bool $translated Optional. Whether translate the display text
     *
     * @return string
     */
    public function getDisplayText(bool $translated = true)
    {
        /*if ($translated) {
            return \ExtraField::translateDisplayName($this->variable, $this->displayText);
        }*/

        return $this->displayText;
    }

    public function setDisplayText(string $displayText): self
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

    public function setDefaultValue(string $defaultValue): self
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

    public function setFieldOrder(int $fieldOrder): self
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

    public function setChangeable(bool $changeable): self
    {
        $this->changeable = $changeable;

        return $this;
    }

    public function isFilter(): bool
    {
        return $this->filter;
    }

    public function setFilter(bool $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    public function isVisibleToSelf(): bool
    {
        return $this->visibleToSelf;
    }

    public function setVisibleToSelf(bool $visibleToSelf): self
    {
        $this->visibleToSelf = $visibleToSelf;

        return $this;
    }

    public function isVisibleToOthers(): bool
    {
        return $this->visibleToOthers;
    }

    public function setVisibleToOthers(bool $visibleToOthers): self
    {
        $this->visibleToOthers = $visibleToOthers;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return ExtraFieldOptions[]|Collection
     */
    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(Collection $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return Tag[]|Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    public function setTags(Collection $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function hasTag(string $tagName): bool
    {
        if (0 === $this->tags->count()) {
            return false;
        }

        return $this->tags->exists(function ($key, Tag $tag) use ($tagName) {
            return $tagName === $tag->getTag();
        });
    }

    public function getTypeToString(): string
    {
        switch ($this->getExtraFieldType()) {
            case \ExtraField::FIELD_TYPE_RADIO:
            case \ExtraField::FIELD_TYPE_SELECT:
                return 'choice';
            case \ExtraField::FIELD_TYPE_TEXT:
            case \ExtraField::FIELD_TYPE_TEXTAREA:
            default:
                return 'text';
        }
    }

    public function getHelperText(): string
    {
        return $this->helperText;
    }

    public function setHelperText(string $helperText): self
    {
        $this->helperText = $helperText;

        return $this;
    }
}
