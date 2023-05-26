<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\CourseTrait;
use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'gradebook_link')]
#[ORM\Index(name: 'idx_gl_cat', columns: ['category_id'])]
#[ORM\Entity]
class GradebookLink
{
    use CourseTrait;
    use UserTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'type', type: 'integer', nullable: false)]
    protected int $type;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'ref_id', type: 'integer', nullable: false)]
    protected int $refId;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\User::class, inversedBy: 'gradeBookLinks')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\Course::class, inversedBy: 'gradebookLinks')]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Course $course;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\GradebookCategory::class, inversedBy: 'links')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected GradebookCategory $category;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    protected DateTime $createdAt;

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

    public function __construct()
    {
        $this->locked = 0;
        $this->visible = 1;
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
}
