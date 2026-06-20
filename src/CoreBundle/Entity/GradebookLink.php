<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\Traits\CourseTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'gradebook_link')]
#[ORM\Index(name: 'idx_gl_cat', columns: ['category_id'])]
#[ORM\Entity]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_USER')"),
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER')"),
        new Put(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER')"),
        new Delete(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER')"),
    ],
    normalizationContext: [
        'groups' => ['gradebookLink:read'],
    ],
    denormalizationContext: [
        'groups' => ['gradebookLink:write'],
    ],
    security: "is_granted('ROLE_USER')",
)]
#[ApiFilter(SearchFilter::class, properties: [
    'category' => 'exact',
    'course' => 'exact',
])]
class GradebookLink
{
    use CourseTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['gradebookLink:read'])]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[Groups(['gradebookLink:read', 'gradebookLink:write'])]
    #[ORM\Column(name: 'type', type: 'integer', nullable: false)]
    protected int $type;

    #[Assert\NotBlank]
    #[Groups(['gradebookLink:read', 'gradebookLink:write'])]
    #[ORM\Column(name: 'ref_id', type: 'integer', nullable: false)]
    protected int $refId;

    #[Groups(['gradebookLink:read', 'gradebookLink:write'])]
    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'gradebookLinks')]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Course $course;

    #[Groups(['gradebookLink:read', 'gradebookLink:write'])]
    #[ORM\ManyToOne(targetEntity: GradebookCategory::class, inversedBy: 'links')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected GradebookCategory $category;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    protected DateTime $createdAt;

    #[Groups(['gradebookLink:read', 'gradebookLink:write'])]
    #[ORM\Column(name: 'weight', type: 'float', precision: 10, scale: 0, nullable: false)]
    protected float $weight;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'visible', type: 'integer', nullable: false)]
    protected int $visible;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'locked', type: 'integer', nullable: false)]
    protected int $locked;

    #[ORM\Column(name: 'best_score', type: 'float', precision: 6, scale: 2, nullable: true)]
    protected ?float $bestScore = null;

    #[ORM\Column(name: 'average_score', type: 'float', precision: 6, scale: 2, nullable: true)]
    protected ?float $averageScore = null;

    #[ORM\Column(name: 'score_weight', type: 'float', precision: 6, scale: 2, nullable: true)]
    protected ?float $scoreWeight = null;

    #[ORM\Column(name: 'user_score_list', type: 'array', nullable: true)]
    protected ?array $userScoreList = null;

    #[ORM\Column(name: 'min_score', type: 'float', precision: 6, scale: 2, nullable: true)]
    protected ?float $minScore = null;

    /**
     * Points awarded when the student posted exactly one message in the thread
     * (only used by forum participation links).
     */
    #[Assert\PositiveOrZero]
    #[Groups(['gradebookLink:read', 'gradebookLink:write'])]
    #[ORM\Column(name: 'points_one', type: 'decimal', precision: 7, scale: 4, nullable: true)]
    protected ?string $pointsOne = null;

    /**
     * Points awarded when the student posted two or more messages in the thread
     * (only used by forum participation links).
     */
    #[Assert\PositiveOrZero]
    #[Groups(['gradebookLink:read', 'gradebookLink:write'])]
    #[ORM\Column(name: 'points_many', type: 'decimal', precision: 7, scale: 4, nullable: true)]
    protected ?string $pointsMany = null;

    public function __construct()
    {
        $this->locked = 0;
        $this->visible = 1;
        $this->userScoreList = [];
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Set refId.
     *
     * @return GradebookLink
     */
    public function setRefId(int $refId)
    {
        $this->refId = $refId;

        return $this;
    }

    /**
     * Get refId.
     *
     * @return int
     */
    public function getRefId()
    {
        return $this->refId;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setWeight(float $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight.
     *
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    public function setVisible(int $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible.
     *
     * @return int
     */
    public function getVisible()
    {
        return $this->visible;
    }

    public function setLocked(int $locked): self
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Get locked.
     *
     * @return int
     */
    public function getLocked()
    {
        return $this->locked;
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
     * @return float
     */
    public function getBestScore()
    {
        return $this->bestScore;
    }

    public function setBestScore(float $bestScore): self
    {
        $this->bestScore = $bestScore;

        return $this;
    }

    /**
     * @return float
     */
    public function getAverageScore()
    {
        return $this->averageScore;
    }

    public function setAverageScore(float $averageScore): self
    {
        $this->averageScore = $averageScore;

        return $this;
    }

    /**
     * @return array
     */
    public function getUserScoreList()
    {
        if (empty($this->userScoreList)) {
            return [];
        }

        return $this->userScoreList;
    }

    public function setUserScoreList(array $userScoreList): self
    {
        $this->userScoreList = $userScoreList;

        return $this;
    }

    /**
     * @return float
     */
    public function getScoreWeight()
    {
        return $this->scoreWeight;
    }

    public function setScoreWeight(float $scoreWeight): self
    {
        $this->scoreWeight = $scoreWeight;

        return $this;
    }

    public function getCategory(): GradebookCategory
    {
        return $this->category;
    }

    public function setCategory(GradebookCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getMinScore(): ?float
    {
        return $this->minScore;
    }

    public function setMinScore(?float $minScore): self
    {
        $this->minScore = $minScore;

        return $this;
    }

    public function getPointsOne(): ?string
    {
        return $this->pointsOne;
    }

    public function setPointsOne(?string $pointsOne): self
    {
        $this->pointsOne = $pointsOne;

        return $this;
    }

    public function getPointsMany(): ?string
    {
        return $this->pointsMany;
    }

    public function setPointsMany(?string $pointsMany): self
    {
        $this->pointsMany = $pointsMany;

        return $this;
    }
}
