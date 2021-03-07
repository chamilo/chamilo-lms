<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\CourseTrait;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="gradebook_category",
 *     indexes={
 *     }))
 *     @ORM\Entity
 */
class GradebookCategory
{
    use UserTrait;
    use CourseTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @Assert\NotBlank
     *
     * @ORM\Column(name="name", type="text", nullable=false)
     */
    protected string $name;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="gradeBookCategories")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected User $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="gradebookCategories")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected Course $course;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\GradebookCategory")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected ?GradebookCategory $parent = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected ?Session $session = null;

    /**
     * @ORM\Column(name="weight", type="float", precision=10, scale=0, nullable=false)
     */
    protected float $weight;

    /**
     * @ORM\Column(name="visible", type="boolean", nullable=false)
     */
    protected bool $visible;

    /**
     * @ORM\Column(name="certif_min_score", type="integer", nullable=true)
     */
    protected ?int $certifMinScore = null;

    /**
     * @ORM\Column(name="document_id", type="integer", nullable=true)
     */
    protected ?int $documentId = null;

    /**
     * @ORM\Column(name="locked", type="integer", nullable=false)
     */
    protected ?int $locked;

    /**
     * @ORM\Column(name="default_lowest_eval_exclude", type="boolean", nullable=true)
     */
    protected ?bool $defaultLowestEvalExclude = null;

    /**
     * @ORM\Column(name="generate_certificates", type="boolean", nullable=false)
     */
    protected bool $generateCertificates;

    /**
     * @ORM\Column(name="grade_model_id", type="integer", nullable=true)
     */
    protected ?int $gradeModelId = null;

    /**
     * @ORM\Column(
     *     name="is_requirement",
     *     type="boolean",
     *     nullable=false,
     *     options={"default":0 }
     * )
     */
    protected bool $isRequirement;

    /**
     * @ORM\Column(name="depends", type="text", nullable=true)
     */
    protected ?string $depends = null;

    /**
     * @ORM\Column(name="minimum_to_validate", type="integer", nullable=true)
     */
    protected ?int $minimumToValidate = null;

    /**
     * @ORM\Column(name="gradebooks_to_validate_in_dependence", type="integer", nullable=true)
     */
    protected ?int $gradeBooksToValidateInDependence = null;

    /**
     * @var Collection|GradebookComment[]
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\GradebookComment", mappedBy="gradebook")
     */
    protected \Doctrine\Common\Collections\Collection $comments;

    public function __construct()
    {
        $this->description = '';
        $this->comments = new ArrayCollection();
        $this->locked = 0;
        $this->generateCertificates = false;
        $this->isRequirement = false;
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
     * Set name.
     *
     * @return GradebookCategory
     */
    public function setName(string $name)
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

    public function getDescription(): ?string
    {
        return $this->description;
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

    /**
     * Set visible.
     *
     * @return GradebookCategory
     */
    public function setVisible(bool $visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible.
     *
     * @return bool
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set certifMinScore.
     *
     * @return GradebookCategory
     */
    public function setCertifMinScore(int $certifMinScore)
    {
        $this->certifMinScore = $certifMinScore;

        return $this;
    }

    /**
     * Get certifMinScore.
     *
     * @return int
     */
    public function getCertifMinScore()
    {
        return $this->certifMinScore;
    }

    /**
     * Set documentId.
     *
     * @return GradebookCategory
     */
    public function setDocumentId(int $documentId)
    {
        $this->documentId = $documentId;

        return $this;
    }

    /**
     * Get documentId.
     *
     * @return int
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

    /**
     * Set locked.
     *
     * @return GradebookCategory
     */
    public function setLocked(int $locked)
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
     * Set defaultLowestEvalExclude.
     *
     * @return GradebookCategory
     */
    public function setDefaultLowestEvalExclude(bool $defaultLowestEvalExclude)
    {
        $this->defaultLowestEvalExclude = $defaultLowestEvalExclude;

        return $this;
    }

    /**
     * Get defaultLowestEvalExclude.
     *
     * @return bool
     */
    public function getDefaultLowestEvalExclude()
    {
        return $this->defaultLowestEvalExclude;
    }

    /**
     * Set generateCertificates.
     *
     * @return GradebookCategory
     */
    public function setGenerateCertificates(bool $generateCertificates)
    {
        $this->generateCertificates = $generateCertificates;

        return $this;
    }

    /**
     * Get generateCertificates.
     *
     * @return bool
     */
    public function getGenerateCertificates()
    {
        return $this->generateCertificates;
    }

    /**
     * Set gradeModelId.
     *
     * @return GradebookCategory
     */
    public function setGradeModelId(int $gradeModelId)
    {
        $this->gradeModelId = $gradeModelId;

        return $this;
    }

    /**
     * Get gradeModelId.
     *
     * @return int
     */
    public function getGradeModelId()
    {
        return $this->gradeModelId;
    }

    /**
     * Set isRequirement.
     *
     * @return GradebookCategory
     */
    public function setIsRequirement(bool $isRequirement)
    {
        $this->isRequirement = $isRequirement;

        return $this;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): self
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Get isRequirement.
     *
     * @return bool
     */
    public function getIsRequirement()
    {
        return $this->isRequirement;
    }

    public function getGradeBooksToValidateInDependence(): int
    {
        return $this->gradeBooksToValidateInDependence;
    }

    public function setGradeBooksToValidateInDependence(int $value): self
    {
        $this->gradeBooksToValidateInDependence = $value;

        return $this;
    }

    /**
     * @return GradebookComment[]|Collection
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param GradebookComment[]|Collection $comments
     */
    public function setComments(Collection $comments): self
    {
        $this->comments = $comments;

        return $this;
    }
}
