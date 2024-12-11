<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\ApiResource\SkillTreeNode;
use Chamilo\CoreBundle\Repository\SkillRepository;
use Chamilo\CoreBundle\State\SkillTreeStateProvider;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(),
        new Patch(),
        new Put(),
        new Delete(),
        new GetCollection(
            uriTemplate: '/skills/tree.{_format}',
            paginationEnabled: false,
            normalizationContext: [
                'groups' => ['skill:tree:read']
            ],
            output: SkillTreeNode::class,
            provider: SkillTreeStateProvider::class
        ),
        new GetCollection(),
        new Get(),
    ],
    normalizationContext: [
        'groups' => ['skill:read'],
    ],
    security: "is_granted('ROLE_ADMIN')"
)]
#[ApiFilter(SearchFilter::class, properties: ['issuedSkills.user' => 'exact'])]
#[ApiFilter(SearchFilter::class, properties: ['title' => 'partial'])]
#[ORM\Table(name: 'skill')]
#[ORM\Entity(repositoryClass: SkillRepository::class)]
class Skill implements Stringable, Translatable
{
    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    #[Groups(['skill:read', 'skill_profile:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SkillLevelProfile::class, inversedBy: 'skills')]
    #[ORM\JoinColumn(name: 'profile_id', referencedColumnName: 'id')]
    protected ?SkillLevelProfile $levelProfile = null;

    /**
     * @var Collection<int, SkillRelUser>
     */
    #[Groups(['skill:read'])]
    #[ORM\OneToMany(mappedBy: 'skill', targetEntity: SkillRelUser::class, cascade: ['persist'])]
    protected Collection $issuedSkills;

    /**
     * @var Collection<int, SkillRelItem>
     */
    #[ORM\OneToMany(mappedBy: 'skill', targetEntity: SkillRelItem::class, cascade: ['persist'])]
    protected Collection $items;

    /**
     * @var Collection<int, SkillRelSkill>
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: SkillRelSkill::class, cascade: ['persist'])]
    protected Collection $skills;

    /**
     * @var Collection<int, SkillRelCourse>
     */
    #[ORM\OneToMany(mappedBy: 'skill', targetEntity: SkillRelCourse::class, cascade: ['persist'])]
    protected Collection $courses;

    /**
     * @var Collection<int, SkillRelGradebook>
     */
    #[ORM\OneToMany(mappedBy: 'skill', targetEntity: SkillRelGradebook::class, cascade: ['persist'])]
    protected Collection $gradeBookCategories;

    #[Gedmo\Translatable]
    #[Assert\NotBlank]
    #[Groups(['skill:read', 'skill:write', 'skill_rel_user:read'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[Gedmo\Translatable]
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

    #[Groups(['skill:read', 'skill_rel_user:read'])]
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

    #[Gedmo\Locale]
    private ?string $locale = null;

    /**
     * @var Collection<int, SkillRelProfile>
     */
    #[ORM\OneToMany(mappedBy: 'skill', targetEntity: SkillRelProfile::class, cascade: ['persist'])]
    private Collection $profiles;

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
        $this->profiles = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
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

    public function setAccessUrlId(int $accessUrlId): static
    {
        $this->accessUrlId = $accessUrlId;

        return $this;
    }

    public function getAccessUrlId(): int
    {
        return $this->accessUrlId;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setCriteria(string $criteria): self
    {
        $this->criteria = $criteria;

        return $this;
    }

    public function getCriteria(): ?string
    {
        return $this->criteria;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setUpdatedAt(DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLevelProfile(): ?SkillLevelProfile
    {
        return $this->levelProfile;
    }

    public function setLevelProfile(SkillLevelProfile $levelProfile): self
    {
        $this->levelProfile = $levelProfile;

        return $this;
    }

    /**
     * @return Collection<int, SkillRelUser>
     */
    public function getIssuedSkills(): Collection
    {
        return $this->issuedSkills;
    }

    public function addIssuedSkill(SkillRelUser $issuedSkill): static
    {
        if (!$this->issuedSkills->contains($issuedSkill)) {
            $this->issuedSkills->add($issuedSkill);
            $issuedSkill->setSkill($this);
        }

        return $this;
    }

    public function removeIssuedSkill(SkillRelUser $issuedSkill): static
    {
        if ($this->issuedSkills->removeElement($issuedSkill)) {
            // set the owning side to null (unless already changed)
            if ($issuedSkill->getSkill() === $this) {
                $issuedSkill->setSkill(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SkillRelItem>
     */
    public function getItems(): Collection
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

    public function getCourses(): Collection
    {
        return $this->courses;
    }

    public function setCourses(ArrayCollection $courses): self
    {
        $this->courses = $courses;

        return $this;
    }

    /**
     * @return Collection<int, SkillRelSkill>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    /**
     * @param Collection<int, SkillRelSkill> $skills
     */
    public function setSkills(Collection $skills): self
    {
        $this->skills = $skills;

        return $this;
    }

    /**
     * @return Collection<int, SkillRelGradebook>
     */
    public function getGradeBookCategories(): Collection
    {
        return $this->gradeBookCategories;
    }

    /**
     * @param Collection<int, SkillRelGradebook> $gradeBookCategories
     */
    public function setGradeBookCategories(Collection $gradeBookCategories): self
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

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return Collection<int, Skill>
     */
    public function getChildSkills(): Collection
    {
        return $this
            ->getSkills()
            ->map(fn(SkillRelSkill $skillRelSkill): Skill => $skillRelSkill->getSkill())
        ;
    }

    /**
     * @return Collection<int, SkillRelProfile>
     */
    public function getProfiles(): Collection
    {
        return $this->profiles;
    }

    public function addProfile(SkillRelProfile $profile): static
    {
        if (!$this->profiles->contains($profile)) {
            $this->profiles->add($profile);
            $profile->setSkill($this);
        }

        return $this;
    }

    public function removeProfile(SkillRelProfile $profile): static
    {
        if ($this->profiles->removeElement($profile)) {
            // set the owning side to null (unless already changed)
            if ($profile->getSkill() === $this) {
                $profile->setSkill(null);
            }
        }

        return $this;
    }
}
