<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     attributes={"security"="is_granted('ROLE_ADMIN')"},
 *     normalizationContext={"groups"={"skill:read"}}
 * )
 *
 * @ORM\Table(name="skill")
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\SkillRepository")
 */
class Skill
{
    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    /**
     * @Groups({"skill:read"})
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Profile", inversedBy="skills")
     * @ORM\JoinColumn(name="profile_id", referencedColumnName="id")
     */
    protected ?Profile $profile = null;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SkillRelUser", mappedBy="skill", cascade={"persist"})
     *
     * @var SkillRelUser[]|Collection
     */
    protected Collection $issuedSkills;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SkillRelItem", mappedBy="skill", cascade={"persist"})
     *
     * @var Collection|SkillRelItem[]
     */
    protected Collection $items;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SkillRelCourse", mappedBy="skill", cascade={"persist"})
     *
     * @var Collection|SkillRelCourse[]
     */
    protected Collection $courses;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SkillRelGradebook", mappedBy="skill", cascade={"persist"})
     *
     * @var Collection|SkillRelGradebook[]
     */
    protected Collection $gradeBookCategories;

    /**
     * @Groups({"skill:read", "skill:write"})
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected string $name;

    /**
     * @Assert\NotBlank()
     * @Groups({"skill:read", "skill:write"})
     *
     * @ORM\Column(name="short_code", type="string", length=100, nullable=false)
     */
    protected string $shortCode;

    /**
     * @Groups({"skill:read", "skill:write"})
     *
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    protected string $description;

    /**
     * @ORM\Column(name="access_url_id", type="integer", nullable=false)
     */
    protected int $accessUrlId;

    /**
     * @ORM\Column(name="icon", type="string", length=255, nullable=false)
     */
    protected string $icon;

    /**
     * @ORM\Column(name="criteria", type="text", nullable=true)
     */
    protected ?string $criteria = null;

    /**
     * @ORM\Column(name="status", type="integer", nullable=false, options={"default":1})
     */
    protected int $status;

    /**
     * @Gedmo\Timestampable(on="update")
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    protected DateTime $updatedAt;

    public function __construct()
    {
        $this->issuedSkills = new ArrayCollection();
        $this->items = new ArrayCollection();
        $this->courses = new ArrayCollection();
        $this->icon = '';
        $this->description = '';
        $this->status = self::STATUS_ENABLED;
    }

    public function __toString()
    {
        return (string) $this->getName();
    }

    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getShortCode()
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

    public function getDescription()
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
     * @return bool
     */
    public function hasCourseAndSession(SkillRelCourse $searchItem)
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
                if ($item->getCourse()->getId() === $searchItem->getCourse()->getId() &&
                    $sessionPassFilter
                ) {
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
