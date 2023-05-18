<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(security: 'is_granted(\'ROLE_ADMIN\')', normalizationContext: ['groups' => ['skill:read']])]
#[ORM\Table(name: 'skill')]
#[ORM\Entity(repositoryClass: \Chamilo\CoreBundle\Repository\SkillRepository::class)]
class Skill implements Stringable
{
    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;
    #[Groups(['skill:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;
    #[ORM\ManyToOne(targetEntity: Profile::class, inversedBy: 'skills')]
    #[ORM\JoinColumn(name: 'profile_id', referencedColumnName: 'id')]
    protected ?Profile $profile = null;
    /**
     * @var SkillRelUser[]|Collection
     */
    #[ORM\OneToMany(targetEntity: SkillRelUser::class, mappedBy: 'skill', cascade: ['persist'])]
    protected Collection $issuedSkills;
    /**
     * @var Collection|SkillRelItem[]
     */
    #[ORM\OneToMany(targetEntity: SkillRelItem::class, mappedBy: 'skill', cascade: ['persist'])]
    protected Collection $items;
    /**
     * @var Collection|SkillRelSkill[]
     */
    #[ORM\OneToMany(targetEntity: SkillRelSkill::class, mappedBy: 'skill', cascade: ['persist'])]
    protected Collection $skills;
    /**
     * @var Collection|SkillRelCourse[]
     */
    #[ORM\OneToMany(targetEntity: SkillRelCourse::class, mappedBy: 'skill', cascade: ['persist'])]
    protected Collection $courses;
    /**
     * @var Collection|SkillRelGradebook[]
     */
    #[ORM\OneToMany(targetEntity: SkillRelGradebook::class, mappedBy: 'skill', cascade: ['persist'])]
    protected Collection $gradeBookCategories;
    #[Assert\NotBlank]
    #[Groups(['skill:read', 'skill:write'])]
    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    protected string $name;
    #[Assert\NotBlank]
    #[Groups(['skill:read', 'skill:write'])]
    #[ORM\Column(name: 'short_code', type: 'string', length: 100, nullable: false)]
    protected string $shortCode;
    #[Groups(['skill:read', 'skill:write'])]
    #[ORM\Column(name: 'description', type: 'text', nullable: false)]
    protected string $description;
    #[Assert\NotNull]
    #[ORM\Column(name: 'access_url_id', type: 'integer', nullable: false)]
    protected int $accessUrlId;
    #[ORM\Column(name: 'icon', type: 'string', length: 255, nullable: false)]
    protected string $icon;
    #[ORM\ManyToOne(targetEntity: Asset::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'asset_id', referencedColumnName: 'id')]
    protected ?Asset $asset = null;
    #[ORM\Column(name: 'criteria', type: 'text', nullable: true)]
    protected ?string $criteria = null;
    #[ORM\Column(name: 'status', type: 'integer', nullable: false, options: ['default' => 1])]
    protected int $status;
    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
    protected DateTime $updatedAt;
    public function __construct()
    {
        $this->issuedSkills = new ArrayCollection();
        $this->items = new ArrayCollection();
        $this->courses = new ArrayCollection();
        $this->gradeBookCategories = new ArrayCollection();
        $this->skills = new ArrayCollection();
        $this->icon = '';
        $this->description = '';
        $this->status = self::STATUS_ENABLED;
    }
    public function __toString(): string
    {
        return $this->getName();
    }
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getShortCode(): string
    {
        return $this->shortCode;
    }
    public function setShortCode(string $shortCode): self
    {
        $this->shortCode = $shortCode;

        return $this;
    }
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }
    public function getDescription(): string
    {
        return $this->description;
    }
    /**
     * Set accessUrlId.
     *
     * @return Skill
     */
    public function setAccessUrlId(int $accessUrlId)
    {
        $this->accessUrlId = $accessUrlId;

        return $this;
    }
    /**
     * Get accessUrlId.
     *
     * @return int
     */
    public function getAccessUrlId()
    {
        return $this->accessUrlId;
    }
    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }
    /**
     * Get icon.
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }
    public function setCriteria(string $criteria): self
    {
        $this->criteria = $criteria;

        return $this;
    }
    /**
     * Get criteria.
     *
     * @return string
     */
    public function getCriteria()
    {
        return $this->criteria;
    }
    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }
    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
    /**
     * Set updatedAt.
     *
     * @param DateTime $updatedAt The update datetime
     *
     * @return Skill
     */
    public function setUpdatedAt(DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
    /**
     * Get updatedAt.
     *
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
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
     * @return Profile
     */
    public function getProfile()
    {
        return $this->profile;
    }
    public function setProfile(Profile $profile): self
    {
        $this->profile = $profile;

        return $this;
    }
    /**
     * Get issuedSkills.
     *
     * @return Collection
     */
    public function getIssuedSkills()
    {
        return $this->issuedSkills;
    }
    /**
     * @return Collection
     */
    public function getItems()
    {
        return $this->items;
    }
    public function setItems(ArrayCollection $items): self
    {
        $this->items = $items;

        return $this;
    }
    public function hasItem(int $typeId, int $itemId): bool
    {
        if (0 !== $this->getItems()->count()) {
            $found = false;
            /** @var SkillRelItem $item */
            foreach ($this->getItems() as $item) {
                if ($item->getItemId() === $itemId && $item->getItemType() === $typeId) {
                    $found = true;

                    break;
                }
            }

            return $found;
        }

        return false;
    }
    public function addItem(SkillRelItem $skillRelItem): void
    {
        $skillRelItem->setSkill($this);
        $this->items[] = $skillRelItem;
    }
    /**
     * @return Collection
     */
    public function getCourses()
    {
        return $this->courses;
    }
    public function setCourses(ArrayCollection $courses): self
    {
        $this->courses = $courses;

        return $this;
    }
    /**
     * @return SkillRelSkill[]|Collection
     */
    public function getSkills(): array|Collection
    {
        return $this->skills;
    }
    /**
     * @param SkillRelSkill[]|Collection $skills
     */
    public function setSkills(array|Collection $skills): self
    {
        $this->skills = $skills;

        return $this;
    }
    /**
     * @return SkillRelGradebook[]|Collection
     */
    public function getGradeBookCategories(): array|Collection
    {
        return $this->gradeBookCategories;
    }
    /**
     * @param SkillRelGradebook[]|Collection $gradeBookCategories
     */
    public function setGradeBookCategories(array|Collection $gradeBookCategories): self
    {
        $this->gradeBookCategories = $gradeBookCategories;

        return $this;
    }
    public function hasAsset(): bool
    {
        return null !== $this->asset;
    }
    public function getAsset(): ?Asset
    {
        return $this->asset;
    }
    public function setAsset(?Asset $asset): self
    {
        $this->asset = $asset;

        return $this;
    }
    public function hasCourseAndSession(SkillRelCourse $searchItem): bool
    {
        if (0 !== $this->getCourses()->count()) {
            $found = false;
            /** @var SkillRelCourse $item */
            foreach ($this->getCourses() as $item) {
                $sessionPassFilter = false;
                $session = $item->getSession();
                $sessionId = empty($session) ? 0 : $session->getId();
                $searchSessionId = empty($searchItem->getSession()) ? 0 : $searchItem->getSession()->getId();
                if ($sessionId === $searchSessionId) {
                    $sessionPassFilter = true;
                }
                if ($item->getCourse()->getId() === $searchItem->getCourse()->getId() && $sessionPassFilter) {
                    $found = true;

                    break;
                }
            }

            return $found;
        }

        return false;
    }
    public function addToCourse(SkillRelCourse $item): void
    {
        $item->setSkill($this);
        $this->courses[] = $item;
    }
}
