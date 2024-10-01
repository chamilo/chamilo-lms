<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Traits\CourseTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * CPeerAssessment.
 */
#[ORM\Table(name: 'c_peer_assessment')]
#[ORM\Entity]
class CPeerAssessment
{
    use TimestampableEntity;
    use CourseTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?Course $course = null;

    #[ORM\ManyToOne(targetEntity: CGroupCategory::class)]
    #[ORM\JoinColumn(name: 'group_category_id', referencedColumnName: 'iid', nullable: true, onDelete: 'CASCADE')]
    protected ?CGroupCategory $groupCategory = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['default' => 0])]
    protected ?int $maxCorrectionPerStudent = 0;

    #[ORM\Column(type: 'integer', nullable: true, options: ['default' => 0])]
    protected ?int $state = 0;

    #[ORM\Column(type: 'integer', nullable: true, options: ['default' => 0])]
    protected ?int $startWorkRepositoryOption = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $endWorkRepositoryOption = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['default' => 0])]
    protected ?int $startCorrectionOption = 0;

    #[ORM\Column(type: 'integer', nullable: true, options: ['default' => 0])]
    protected ?int $endCorrectionOption = 0;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 0])]
    protected int $distributeCorrectionOption = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $endRepositoryOption = null;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => 0])]
    protected ?bool $examinerRoleCondition = false;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => 0])]
    protected ?bool $studentAccessToCorrection = false;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => 0])]
    protected ?bool $commentConstraint = false;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => 0])]
    protected ?bool $correctOwnWork = false;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => 0])]
    protected ?bool $correctBenchmarkWork = false;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => 0])]
    protected ?bool $distributionAlgorithm = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $sendWorkStartDate = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $sendWorkEndDate = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $startCorrectionDate = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $endCorrectionDate = null;

    public function __construct() {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroupCategory(): ?CGroupCategory
    {
        return $this->groupCategory;
    }

    public function setGroupCategory(?CGroupCategory $groupCategory): self
    {
        $this->groupCategory = $groupCategory;
        return $this;
    }

    public function getMaxCorrectionPerStudent(): ?int
    {
        return $this->maxCorrectionPerStudent;
    }

    public function setMaxCorrectionPerStudent(?int $maxCorrectionPerStudent): self
    {
        $this->maxCorrectionPerStudent = $maxCorrectionPerStudent;
        return $this;
    }

    public function getState(): ?int
    {
        return $this->state;
    }

    public function setState(?int $state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getStartWorkRepositoryOption(): ?int
    {
        return $this->startWorkRepositoryOption;
    }

    public function setStartWorkRepositoryOption(?int $startWorkRepositoryOption): self
    {
        $this->startWorkRepositoryOption = $startWorkRepositoryOption;
        return $this;
    }

    public function getEndWorkRepositoryOption(): ?int
    {
        return $this->endWorkRepositoryOption;
    }

    public function setEndWorkRepositoryOption(?int $endWorkRepositoryOption): self
    {
        $this->endWorkRepositoryOption = $endWorkRepositoryOption;
        return $this;
    }

    public function getStartCorrectionOption(): ?int
    {
        return $this->startCorrectionOption;
    }

    public function setStartCorrectionOption(?int $startCorrectionOption): self
    {
        $this->startCorrectionOption = $startCorrectionOption;
        return $this;
    }

    public function getEndCorrectionOption(): ?int
    {
        return $this->endCorrectionOption;
    }

    public function setEndCorrectionOption(?int $endCorrectionOption): self
    {
        $this->endCorrectionOption = $endCorrectionOption;
        return $this;
    }

    public function getDistributeCorrectionOption(): int
    {
        return $this->distributeCorrectionOption;
    }

    public function setDistributeCorrectionOption(int $distributeCorrectionOption): self
    {
        $this->distributeCorrectionOption = $distributeCorrectionOption;
        return $this;
    }

    public function getEndRepositoryOption(): ?int
    {
        return $this->endRepositoryOption;
    }

    public function setEndRepositoryOption(?int $endRepositoryOption): self
    {
        $this->endRepositoryOption = $endRepositoryOption;
        return $this;
    }

    public function getExaminerRoleCondition(): ?bool
    {
        return $this->examinerRoleCondition;
    }

    public function setExaminerRoleCondition(?bool $examinerRoleCondition): self
    {
        $this->examinerRoleCondition = $examinerRoleCondition;
        return $this;
    }

    public function getStudentAccessToCorrection(): ?bool
    {
        return $this->studentAccessToCorrection;
    }

    public function setStudentAccessToCorrection(?bool $studentAccessToCorrection): self
    {
        $this->studentAccessToCorrection = $studentAccessToCorrection;
        return $this;
    }

    public function getCommentConstraint(): ?bool
    {
        return $this->commentConstraint;
    }

    public function setCommentConstraint(?bool $commentConstraint): self
    {
        $this->commentConstraint = $commentConstraint;
        return $this;
    }

    public function getCorrectOwnWork(): ?bool
    {
        return $this->correctOwnWork;
    }

    public function setCorrectOwnWork(?bool $correctOwnWork): self
    {
        $this->correctOwnWork = $correctOwnWork;
        return $this;
    }

    public function getCorrectBenchmarkWork(): ?bool
    {
        return $this->correctBenchmarkWork;
    }

    public function setCorrectBenchmarkWork(?bool $correctBenchmarkWork): self
    {
        $this->correctBenchmarkWork = $correctBenchmarkWork;
        return $this;
    }

    public function getDistributionAlgorithm(): ?bool
    {
        return $this->distributionAlgorithm;
    }

    public function setDistributionAlgorithm(?bool $distributionAlgorithm): self
    {
        $this->distributionAlgorithm = $distributionAlgorithm;
        return $this;
    }

    public function getSendWorkStartDate(): ?\DateTime
    {
        return $this->sendWorkStartDate;
    }

    public function setSendWorkStartDate(?\DateTime $sendWorkStartDate): self
    {
        $this->sendWorkStartDate = $sendWorkStartDate;
        return $this;
    }

    public function getSendWorkEndDate(): ?\DateTime
    {
        return $this->sendWorkEndDate;
    }

    public function setSendWorkEndDate(?\DateTime $sendWorkEndDate): self
    {
        $this->sendWorkEndDate = $sendWorkEndDate;
        return $this;
    }

    public function getStartCorrectionDate(): ?\DateTime
    {
        return $this->startCorrectionDate;
    }

    public function setStartCorrectionDate(?\DateTime $startCorrectionDate): self
    {
        $this->startCorrectionDate = $startCorrectionDate;
        return $this;
    }

    public function getEndCorrectionDate(): ?\DateTime
    {
        return $this->endCorrectionDate;
    }

    public function setEndCorrectionDate(?\DateTime $endCorrectionDate): self
    {
        $this->endCorrectionDate = $endCorrectionDate;
        return $this;
    }
}
