<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Chamilo\CoreBundle\Traits\CourseTrait;
use Chamilo\CoreBundle\Traits\UserTrait;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'gradebook_category')]
#[ORM\Entity]
#[ApiResource(
    normalizationContext: [
        'groups' => ['gradebookCategory:read'],
    ],
    denormalizationContext: [
        'groups' => ['gradebookCategory:write'],
    ],
    security: "is_granted('ROLE_USER')",
)]
#[ApiFilter(SearchFilter::class, properties: [
    'course' => 'exact',
])]
class GradebookCategory
{
    use CourseTrait;
    use UserTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['document:read', 'gradebookCategory:read'])]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'text', nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'gradeBookCategories', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'gradebookCategories')]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Groups(['gradebookCategory:read'])]
    protected Course $course;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subCategories')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?GradebookCategory $parent = null;

    /**
     * @var GradebookCategory[]|Collection
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    protected Collection $subCategories;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Session $session = null;

    /**
     * @var SkillRelGradebook[]|Collection
     */
    #[ORM\OneToMany(targetEntity: SkillRelGradebook::class, mappedBy: 'gradeBookCategory')]
    protected Collection $skills;

    /**
     * @var Collection|GradebookEvaluation[]
     */
    #[ORM\OneToMany(targetEntity: GradebookEvaluation::class, mappedBy: 'category', cascade: ['persist', 'remove'])]
    protected Collection $evaluations;

    /**
     * @var Collection|GradebookLink[]
     */
    #[ORM\OneToMany(targetEntity: GradebookLink::class, mappedBy: 'category', cascade: ['persist', 'remove'])]
    protected Collection $links;

    /**
     * @var Collection|GradebookComment[]
     */
    #[ORM\OneToMany(targetEntity: GradebookComment::class, mappedBy: 'gradeBook')]
    protected Collection $comments;

    #[ORM\ManyToOne(targetEntity: GradeModel::class)]
    #[ORM\JoinColumn(name: 'grade_model_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?GradeModel $gradeModel = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'weight', type: 'float', precision: 10, scale: 0, nullable: false)]
    protected float $weight;

    #[Assert\NotNull]
    #[ORM\Column(name: 'visible', type: 'boolean', nullable: false)]
    protected bool $visible;

    #[ORM\Column(name: 'certif_min_score', type: 'integer', nullable: true)]
    protected ?int $certifMinScore = null;

    #[Groups(['gradebookCategory:read', 'gradebookCategory:write'])]
    #[ORM\ManyToOne(targetEntity: CDocument::class, inversedBy: 'gradebookCategories')]
    #[ORM\JoinColumn(name: 'document_id', referencedColumnName: 'iid', onDelete: 'set null')]
    private ?CDocument $document = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'locked', type: 'integer', nullable: false)]
    protected ?int $locked;

    #[ORM\Column(name: 'default_lowest_eval_exclude', type: 'boolean', nullable: true)]
    protected ?bool $defaultLowestEvalExclude = null;

    #[Assert\NotNull]
    #[ORM\Column(name: 'generate_certificates', type: 'boolean', nullable: false)]
    protected bool $generateCertificates;

    #[Groups(['gradebookCategory:read', 'gradebookCategory:write'])]
    #[ORM\Column(name: 'certificate_validity_period', type: 'integer', nullable: true)]
    protected ?int $certificateValidityPeriod = null;

    #[ORM\Column(name: 'is_requirement', type: 'boolean', nullable: false, options: ['default' => 0])]
    protected bool $isRequirement;

    #[ORM\Column(name: 'depends', type: 'text', nullable: true)]
    protected ?string $depends = null;

    #[ORM\Column(name: 'minimum_to_validate', type: 'integer', nullable: true)]
    protected ?int $minimumToValidate = null;

    #[ORM\Column(name: 'gradebooks_to_validate_in_dependence', type: 'integer', nullable: true)]
    protected ?int $gradeBooksToValidateInDependence = null;

    #[ORM\Column(name: 'allow_skills_by_subcategory', type: 'integer', nullable: true, options: ['default' => 1])]
    protected ?int $allowSkillsBySubcategory;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
        $this->links = new ArrayCollection();
        $this->subCategories = new ArrayCollection();
        $this->skills = new ArrayCollection();

        $this->description = '';
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

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
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

    public function setVisible(bool $visible): self
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

    public function setCertifMinScore(int $certifMinScore): self
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

    public function setDocument(?CDocument $document): static
    {
        $this->document = $document;

        return $this;
    }

    public function getDocument(): ?CDocument
    {
        return $this->document;
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

    public function setDefaultLowestEvalExclude(bool $defaultLowestEvalExclude): self
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

    public function setGenerateCertificates(bool $generateCertificates): self
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
     * Get number of days the linked certificate is valid after attribution.
     */
    public function getCertificateValidityPeriod(): ?int
    {
        return $this->certificateValidityPeriod;
    }

    /**
     * Set number of days the linked certificate is valid after attribution.
     */
    public function setCertificateValidityPeriod(?int $certificateValidityPeriod): self
    {
        $this->certificateValidityPeriod = $certificateValidityPeriod;

        return $this;
    }

    public function setIsRequirement(bool $isRequirement): self
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

    public function getGradeBooksToValidateInDependence(): ?int
    {
        return $this->gradeBooksToValidateInDependence;
    }

    public function setGradeBooksToValidateInDependence(?int $value): self
    {
        $this->gradeBooksToValidateInDependence = $value;

        return $this;
    }

    /**
     * @return GradebookComment[]|Collection
     */
    public function getComments(): array|Collection
    {
        return $this->comments;
    }

    /**
     * @param GradebookComment[]|Collection $comments
     */
    public function setComments(array|Collection $comments): self
    {
        $this->comments = $comments;

        return $this;
    }

    public function getGradeModel(): ?GradeModel
    {
        return $this->gradeModel;
    }

    public function setGradeModel(?GradeModel $gradeModel): self
    {
        $this->gradeModel = $gradeModel;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return GradebookEvaluation[]|Collection
     */
    public function getEvaluations(): array|Collection
    {
        return $this->evaluations;
    }

    /**
     * @param GradebookEvaluation[]|Collection $evaluations
     */
    public function setEvaluations(array|Collection $evaluations): self
    {
        $this->evaluations = $evaluations;

        return $this;
    }

    /**
     * @return GradebookLink[]|Collection
     */
    public function getLinks(): array|Collection
    {
        return $this->links;
    }

    /**
     * @param GradebookLink[]|Collection $links
     */
    public function setLinks(array|Collection $links): self
    {
        $this->links = $links;

        return $this;
    }

    /**
     * @return GradebookCategory[]|Collection
     */
    public function getSubCategories(): array|Collection
    {
        return $this->subCategories;
    }

    public function hasSubCategories(): bool
    {
        return $this->subCategories->count() > 0;
    }

    public function setSubCategories(Collection $subCategories): self
    {
        $this->subCategories = $subCategories;

        return $this;
    }

    public function getDepends(): ?string
    {
        return $this->depends;
    }

    public function setDepends(?string $depends): self
    {
        $this->depends = $depends;

        return $this;
    }

    public function getMinimumToValidate(): ?int
    {
        return $this->minimumToValidate;
    }

    public function setMinimumToValidate(?int $minimumToValidate): self
    {
        $this->minimumToValidate = $minimumToValidate;

        return $this;
    }

    /**
     * @return SkillRelGradebook[]|Collection
     */
    public function getSkills(): array|Collection
    {
        return $this->skills;
    }

    /**
     * @param SkillRelGradebook[]|Collection $skills
     */
    public function setSkills(array|Collection $skills): self
    {
        $this->skills = $skills;

        return $this;
    }

    /**
     * @return int
     */
    public function getAllowSkillsBySubcategory()
    {
        return $this->allowSkillsBySubcategory;
    }

    /**
     * @param int $allowSkillsBySubcategory
     *
     * @return GradebookCategory
     */
    public function setAllowSkillsBySubcategory($allowSkillsBySubcategory)
    {
        $this->allowSkillsBySubcategory = $allowSkillsBySubcategory;

        return $this;
    }
}
