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

#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_ADMIN\')'), new Put(security: 'is_granted(\'ROLE_ADMIN\')'), new GetCollection(security: 'is_granted(\'ROLE_ADMIN\')'), new Post(security: 'is_granted(\'ROLE_ADMIN\')')], security: 'is_granted(\'ROLE_ADMIN\')', denormalizationContext: ['groups' => ['extra_field:write']], normalizationContext: ['groups' => ['extra_field:read']])]
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
    #[ORM\Column(name: 'display_text', type: 'string', length: 255, nullable: true, unique: false)]
    protected ?string $displayText = null;
    #[Gedmo\Locale]
    protected ?string $locale = null;
    #[ORM\Column(name: 'helper_text', type: 'text', nullable: true, unique: false)]
    protected ?string $helperText = null;
    #[ORM\Column(name: 'default_value', type: 'text', nullable: true, unique: false)]
    protected ?string $defaultValue = null;
    #[ORM\Column(name: 'field_order', type: 'integer', nullable: true, unique: false)]
    protected ?int $fieldOrder = null;
    #[ORM\Column(name: 'visible_to_self', type: 'boolean', nullable: true, unique: false)]
    protected ?bool $visibleToSelf;
    #[ORM\Column(name: 'visible_to_others', type: 'boolean', nullable: true, unique: false)]
    protected ?bool $visibleToOthers;
    #[ORM\Column(name: 'changeable', type: 'boolean', nullable: true, unique: false)]
    protected ?bool $changeable = null;
    #[ORM\Column(name: 'filter', type: 'boolean', nullable: true, unique: false)]
    protected ?bool $filter = null;
    /**
     * @var Collection<int, ExtraFieldOptions>
     */
    #[Groups(['extra_field:read'])]
    #[ORM\OneToMany(targetEntity: ExtraFieldOptions::class, mappedBy: 'field')]
    protected Collection $options;
    /**
     * @var Tag[]|Collection
     */
    #[ORM\OneToMany(targetEntity: Tag::class, mappedBy: 'field')]
    protected Collection $tags;
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected DateTime $createdAt;
    public function __construct()
    {
        $this->options = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->description = '';
        $this->visibleToOthers = false;
        $this->visibleToSelf = false;
        $this->changeable = false;
        $this->filter = false;
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
     * @return string
     */
    public function getDisplayText()
    {
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
     * @return Tag[]|Collection
     */
    public function getTags(): array|Collection
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
    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }
    public function getTranslatableLocale()
    {
        return $this->locale;
    }
}
