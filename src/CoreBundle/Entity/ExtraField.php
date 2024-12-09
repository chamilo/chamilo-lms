<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_ADMIN')"),
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: [
        'groups' => ['extra_field:read'],
    ],
    denormalizationContext: [
        'groups' => ['extra_field:write'],
    ],
    security: "is_granted('ROLE_ADMIN')"),
]
#[ORM\Table(name: 'extra_field')]
#[ORM\Entity]
#[ORM\MappedSuperclass]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['variable'])]
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
    public const COURSE_ANNOUNCEMENT = 21;
    public const MESSAGE_TYPE = 22;
    public const DOCUMENT_TYPE = 23;
    public const ATTENDANCE_CALENDAR_TYPE = 24;

    public const USER_FIELD_TYPE_RADIO = 3;
    public const USER_FIELD_TYPE_SELECT_MULTIPLE = 5;
    public const USER_FIELD_TYPE_TAG = 10;
    public const FIELD_TYPE_TEXT = 1;
    public const FIELD_TYPE_TEXTAREA = 2;
    public const FIELD_TYPE_RADIO = 3;
    public const FIELD_TYPE_SELECT = 4;
    public const FIELD_TYPE_SELECT_MULTIPLE = 5;
    public const FIELD_TYPE_DATE = 6;
    public const FIELD_TYPE_DATETIME = 7;
    public const FIELD_TYPE_DOUBLE_SELECT = 8;
    public const FIELD_TYPE_TAG = 10;
    public const FIELD_TYPE_SOCIAL_PROFILE = 12;
    public const FIELD_TYPE_CHECKBOX = 13;
    public const FIELD_TYPE_INTEGER = 15;
    public const FIELD_TYPE_FILE_IMAGE = 16;
    public const FIELD_TYPE_FLOAT = 17;
    public const FIELD_TYPE_FILE = 18;
    public const FIELD_TYPE_GEOLOCALIZATION = 24;
    public const FIELD_TYPE_GEOLOCALIZATION_COORDINATES = 25;

    #[Groups(['extra_field:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Groups(['extra_field:read', 'extra_field:write'])]
    #[ORM\Column(name: 'item_type', type: 'integer')]
    protected int $itemType;

    #[Groups(['extra_field:read', 'extra_field:write'])]
    #[ORM\Column(name: 'value_type', type: 'integer')]
    protected int $valueType;

    #[Assert\NotBlank]
    #[Groups(['extra_field:read', 'extra_field:write'])]
    #[ORM\Column(name: 'variable', type: 'string', length: 255)]
    protected string $variable;

    #[Groups(['extra_field:read', 'extra_field:write'])]
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description;

    #[Assert\NotBlank]
    #[Groups(['extra_field:read', 'extra_field:write'])]
    #[Gedmo\Translatable]
    #[ORM\Column(name: 'display_text', type: 'string', length: 255, unique: false, nullable: true)]
    protected ?string $displayText = null;

    #[ORM\Column(name: 'helper_text', type: 'text', unique: false, nullable: true)]
    protected ?string $helperText = null;

    #[ORM\Column(name: 'default_value', type: 'text', unique: false, nullable: true)]
    protected ?string $defaultValue = null;

    #[ORM\Column(name: 'field_order', type: 'integer', unique: false, nullable: true)]
    protected ?int $fieldOrder = null;

    #[ORM\Column(name: 'visible_to_self', type: 'boolean', unique: false, nullable: true)]
    protected ?bool $visibleToSelf = false;
    #[ORM\Column(name: 'visible_to_others', type: 'boolean', unique: false, nullable: true)]
    protected ?bool $visibleToOthers = false;

    #[ORM\Column(name: 'changeable', type: 'boolean', unique: false, nullable: true)]
    protected ?bool $changeable = false;

    #[ORM\Column(name: 'filter', type: 'boolean', unique: false, nullable: true)]
    protected ?bool $filter = false;

    /**
     * @var Collection<int, ExtraFieldOptions>
     */
    #[Groups(['extra_field:read'])]
    #[ORM\OneToMany(mappedBy: 'field', targetEntity: ExtraFieldOptions::class)]
    protected Collection $options;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\OneToMany(mappedBy: 'field', targetEntity: Tag::class)]
    protected Collection $tags;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected DateTime $createdAt;

    #[Groups(['extra_field:read'])]
    #[ORM\Column(name: 'auto_remove', type: 'boolean', options: ['default' => false])]
    protected bool $autoRemove = false;

    #[Gedmo\Locale]
    private ?string $locale = null;

    public function __construct()
    {
        $this->options = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->description = '';
        $this->visibleToOthers = false;
        $this->visibleToSelf = false;
        $this->changeable = false;
        $this->filter = false;
        $this->autoRemove = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItemType(): int
    {
        return $this->itemType;
    }

    public function setItemType(int $itemType): self
    {
        $this->itemType = $itemType;

        return $this;
    }

    public function getValueType(): int
    {
        return $this->valueType;
    }

    public function setValueType(int $valueType): self
    {
        $this->valueType = $valueType;

        return $this;
    }

    public function getVariable(): string
    {
        return $this->variable;
    }

    public function setVariable(string $variable): self
    {
        $this->variable = $variable;

        return $this;
    }

    public function getDisplayText(): ?string
    {
        return $this->displayText;
    }

    public function setDisplayText(string $displayText): self
    {
        $this->displayText = $displayText;

        return $this;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(string $defaultValue): self
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    public function getFieldOrder(): ?int
    {
        return $this->fieldOrder;
    }

    public function setFieldOrder(int $fieldOrder): self
    {
        $this->fieldOrder = $fieldOrder;

        return $this;
    }

    public function isChangeable(): ?bool
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
     * @return Collection<int, ExtraFieldOptions>
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function setOptions(Collection $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
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

        return $this->tags->exists(fn ($key, Tag $tag) => $tagName === $tag->getTag());
    }

    public function getTypeToString(): string
    {
        return match ($this->getItemType()) {
            \ExtraField::FIELD_TYPE_RADIO, \ExtraField::FIELD_TYPE_SELECT => 'choice',
            default => 'text',
        };
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

    public function getAutoRemove(): bool
    {
        return $this->autoRemove;
    }

    public function setAutoRemove(bool $autoRemove): self
    {
        $this->autoRemove = $autoRemove;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }
}
