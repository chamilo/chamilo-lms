<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\CourseTrait;
use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * GradebookEvaluation.
 *
 * @ORM\Table(name="gradebook_evaluation",
 *     indexes={
 *         @ORM\Index(name="idx_ge_cat", columns={"category_id"}),
 *     })
 *     @ORM\Entity
 */
class GradebookEvaluation
{
    use CourseTrait;
    use UserTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(name="name", type="text", nullable=false)
     */
    protected string $name;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="gradeBookEvaluations")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="gradebookEvaluations")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected Course $course;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\GradebookCategory", inversedBy="evaluations")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected GradebookCategory $category;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected DateTime $createdAt;

    /**
     * @ORM\Column(name="weight", type="float", precision=10, scale=0, nullable=false)
     */
    protected float $weight;

    /**
     * @ORM\Column(name="max", type="float", precision=10, scale=0, nullable=false)
     */
    protected float $max;

    /**
     * @ORM\Column(name="visible", type="integer", nullable=false)
     */
    protected int $visible;

    /**
     * @ORM\Column(name="type", type="string", length=40, nullable=false)
     */
    protected string $type;

    /**
     * @ORM\Column(name="locked", type="integer", nullable=false)
     */
    protected int $locked;

    /**
     * @ORM\Column(name="best_score", type="float", precision=6, scale=2, nullable=true)
     */
    protected ?float $bestScore = null;

    /**
     * @ORM\Column(name="average_score", type="float", precision=6, scale=2, nullable=true)
     */
    protected ?float $averageScore = null;

    /**
     * @ORM\Column(name="score_weight", type="float", precision=6, scale=2, nullable=true)
     */
    protected ?float $scoreWeight = null;

    /**
     * @ORM\Column(name="user_score_list", type="array", nullable=true)
     */
    protected ?array $userScoreList = null;

    public function __construct()
    {
        $this->locked = 0;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
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

    public function setMax(float $max): self
    {
        $this->max = $max;

        return $this;
    }

    /**
     * Get max.
     *
     * @return float
     */
    public function getMax()
    {
        return $this->max;
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

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
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

    /**
     * @return GradebookEvaluation
     */
    public function setUserScoreList(array $userScoreList)
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

    /**
     * @return GradebookEvaluation
     */
    public function setScoreWeight(float $scoreWeight)
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
}
