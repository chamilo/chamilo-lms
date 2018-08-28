<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * GradebookCategory.
 *
 * @ORM\Table(name="gradebook_category")
 * @ORM\Entity
 */
class GradebookCategory
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="text", nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", inversedBy="gradeBookCategories")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="gradebookCategories")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected $course;

    /**
     * @var int
     *
     * @ORM\Column(name="parent_id", type="integer", nullable=true)
     */
    protected $parentId;

    /**
     * @var float
     *
     * @ORM\Column(name="weight", type="float", precision=10, scale=0, nullable=false)
     */
    protected $weight;

    /**
     * @var bool
     *
     * @ORM\Column(name="visible", type="boolean", nullable=false)
     */
    protected $visible;

    /**
     * @var int
     *
     * @ORM\Column(name="certif_min_score", type="integer", nullable=true)
     */
    protected $certifMinScore;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    protected $sessionId;

    /**
     * @var int
     *
     * @ORM\Column(name="document_id", type="integer", nullable=true)
     */
    protected $documentId;

    /**
     * @var int
     *
     * @ORM\Column(name="locked", type="integer", nullable=false)
     */
    protected $locked;

    /**
     * @var bool
     *
     * @ORM\Column(name="default_lowest_eval_exclude", type="boolean", nullable=true)
     */
    protected $defaultLowestEvalExclude;

    /**
     * @var bool
     *
     * @ORM\Column(name="generate_certificates", type="boolean", nullable=false)
     */
    protected $generateCertificates;

    /**
     * @var int
     *
     * @ORM\Column(name="grade_model_id", type="integer", nullable=true)
     */
    protected $gradeModelId;

    /**
     * @var bool
     *
     * @ORM\Column(
     *      name="is_requirement",
     *      type="boolean",
     *      nullable=false,
     *      options={"default": 0 }
     * )
     */
    protected $isRequirement;

    /**
     * @var string
     *
     * @ORM\Column(name="depends", type="text", nullable=true)
     */
    protected $depends;

    /**
     * @var int
     *
     * @ORM\Column(name="minimum_to_validate", type="integer", nullable=true)
     */
    protected $minimumToValidate;

    /**
     * @var int
     *
     * @ORM\Column(name="gradebooks_to_validate_in_dependence", type="integer", nullable=true)
     */
    protected $gradeBooksToValidateInDependence;

    /**
     * GradebookCategory constructor.
     */
    public function __construct()
    {
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
     * @param string $name
     *
     * @return GradebookCategory
     */
    public function setName($name)
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

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return GradebookCategory
     */
    public function setDescription($description)
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

    /**
     * Set course.
     *
     * @param \Chamilo\CoreBundle\Entity\Course $course
     *
     * @return \Chamilo\CoreBundle\Entity\GradebookCategory
     */
    public function setCourse(Course $course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get course.
     *
     * @return \Chamilo\CoreBundle\Entity\Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * Set parentId.
     *
     * @param int $parentId
     *
     * @return GradebookCategory
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId.
     *
     * @return int
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set weight.
     *
     * @param float $weight
     *
     * @return GradebookCategory
     */
    public function setWeight($weight)
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
     * @param bool $visible
     *
     * @return GradebookCategory
     */
    public function setVisible($visible)
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
     * @param int $certifMinScore
     *
     * @return GradebookCategory
     */
    public function setCertifMinScore($certifMinScore)
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
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return GradebookCategory
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set documentId.
     *
     * @param int $documentId
     *
     * @return GradebookCategory
     */
    public function setDocumentId($documentId)
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
     * @param int $locked
     *
     * @return GradebookCategory
     */
    public function setLocked($locked)
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
     * @param bool $defaultLowestEvalExclude
     *
     * @return GradebookCategory
     */
    public function setDefaultLowestEvalExclude($defaultLowestEvalExclude)
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
     * @param bool $generateCertificates
     *
     * @return GradebookCategory
     */
    public function setGenerateCertificates($generateCertificates)
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
     * @param int $gradeModelId
     *
     * @return GradebookCategory
     */
    public function setGradeModelId($gradeModelId)
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
     * @param bool $isRequirement
     *
     * @return GradebookCategory
     */
    public function setIsRequirement($isRequirement)
    {
        $this->isRequirement = $isRequirement;

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

    /**
     * @return int
     */
    public function getGradeBooksToValidateInDependence(): int
    {
        return $this->gradeBooksToValidateInDependence;
    }

    /**
     * @param int $value
     *
     * @return GradebookCategory
     */
    public function setGradeBooksToValidateInDependence(int $value): GradebookCategory
    {
        $this->gradeBooksToValidateInDependence = $value;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return GradebookCategory
     */
    public function setUser(User $user): GradebookCategory
    {
        $this->user = $user;

        return $this;
    }
}
