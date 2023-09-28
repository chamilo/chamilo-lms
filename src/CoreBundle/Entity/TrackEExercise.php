<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Chamilo\CoreBundle\Traits\UserExtraFieldFilterTrait;
use Chamilo\CourseBundle\Entity\CQuiz;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Quiz user attempts.
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted("VIEW", object)'),
        new GetCollection(security: 'is_granted("ROLE_USER")'),
    ],
    normalizationContext: [
        'groups' => ['track_e_exercise:read'],
    ],
    denormalizationContext: [
        'groups' => ['track_e_exercise:write'],
    ],
    security: 'is_granted("ROLE_USER")'
)]
#[ORM\Table(name: 'track_e_exercises')]
#[ORM\Index(columns: ['exe_user_id'], name: 'idx_tee_user_id')]
#[ORM\Index(columns: ['c_id'], name: 'idx_tee_c_id')]
#[ORM\Index(columns: ['session_id'], name: 'session_id')]
#[ORM\Entity]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'user' => 'exact',
        'quiz' => 'exact',
        'course' => 'exact',
        'session' => 'exact',
    ]
)]
#[ApiFilter(
    filterClass: OrderFilter::class,
    properties: [
        'user.username',
        'user.fisrname',
        'user.lastname',
        'startDate',
        'exeDate',
        'exeDuration',
        'score',
        'status',
        'userIp',
    ]
)]
class TrackEExercise
{
    use UserExtraFieldFilterTrait;

    #[ORM\Column(name: 'exe_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected int $exeId;

    #[Assert\NotNull]
    #[Groups(['track_e_exercise:read'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'exe_user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected User $user;

    #[Assert\NotNull]
    #[Groups(['track_e_exercise:read'])]
    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Course $course;

    #[Groups(['track_e_exercise:read'])]
    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Session $session = null;

    #[Assert\NotBlank]
    #[Groups(['track_e_exercise:read'])]
    #[ORM\Column(name: 'exe_date', type: 'datetime', nullable: false)]
    protected DateTime $exeDate;

    #[Groups(['track_e_exercise:read'])]
    #[ORM\ManyToOne(targetEntity: CQuiz::class, inversedBy: 'attempts')]
    #[ORM\JoinColumn(name: 'exe_exo_id', referencedColumnName: 'iid', nullable: true, onDelete: 'SET NULL')]
    protected ?CQuiz $quiz = null;

    #[Assert\NotNull]
    #[Groups(['track_e_exercise:read'])]
    #[ORM\Column(name: 'score', type: 'float', precision: 6, scale: 2, nullable: false)]
    protected float $score;

    #[Assert\NotNull]
    #[Groups(['track_e_exercise:read'])]
    #[ORM\Column(name: 'max_score', type: 'float', precision: 6, scale: 2, nullable: false)]
    protected float $maxScore;

    #[Assert\NotBlank]
    #[Groups(['track_e_exercise:read'])]
    #[ORM\Column(name: 'user_ip', type: 'string', length: 45, nullable: false)]
    protected string $userIp;

    #[Assert\NotNull]
    #[Groups(['track_e_exercise:read'])]
    #[ORM\Column(name: 'status', type: 'string', length: 20, nullable: false)]
    protected string $status;

    #[Assert\NotNull]
    #[Groups(['track_e_exercise:read'])]
    #[ORM\Column(name: 'data_tracking', type: 'text', nullable: false)]
    protected string $dataTracking;

    #[Groups(['track_e_exercise:read'])]
    #[ORM\Column(name: 'start_date', type: 'datetime', nullable: false)]
    protected DateTime $startDate;

    #[Assert\NotNull]
    #[Groups(['track_e_exercise:read'])]
    #[ORM\Column(name: 'steps_counter', type: 'smallint', nullable: false)]
    protected int $stepsCounter;

    #[Groups(['track_e_exercise:read'])]
    #[ORM\Column(name: 'orig_lp_id', type: 'integer', nullable: false)]
    protected int $origLpId;

    #[Groups(['track_e_exercise:read'])]
    #[ORM\Column(name: 'orig_lp_item_id', type: 'integer', nullable: false)]
    protected int $origLpItemId;

    #[Assert\NotNull]
    #[Groups(['track_e_exercise:read'])]
    #[ORM\Column(name: 'exe_duration', type: 'integer', nullable: false)]
    protected int $exeDuration;

    #[Groups(['track_e_exercise:read'])]
    #[ORM\Column(name: 'expired_time_control', type: 'datetime', nullable: true)]
    protected ?DateTime $expiredTimeControl = null;

    #[Groups(['track_e_exercise:read'])]
    #[ORM\Column(name: 'orig_lp_item_view_id', type: 'integer', nullable: false)]
    protected int $origLpItemViewId;

    #[Groups(['track_e_exercise:read'])]
    #[Assert\NotNull]
    #[ORM\Column(name: 'questions_to_check', type: 'text', nullable: false)]
    protected string $questionsToCheck;

    #[Groups(['track_e_exercise:read'])]
    #[ORM\Column(name: 'blocked_categories', type: 'text', nullable: true)]
    protected ?string $blockedCategories;

    /**
     * @var Collection<int, TrackEAttempt>
     */
    #[Groups(['track_e_exercise:read'])]
    #[ORM\OneToMany(mappedBy: 'trackExercise', targetEntity: TrackEAttempt::class, cascade: ['persist'])]
    protected Collection $attempts;

    public function __construct()
    {
        $this->attempts = new ArrayCollection();
        $this->questionsToCheck = '';
        $this->blockedCategories = '';
        $this->dataTracking = '';
        $this->exeDuration = 0;
        $this->score = 0;
        $this->status = 'incomplete';
        $this->stepsCounter = 0;
        $this->exeDate = new DateTime();
        $this->startDate = new DateTime();
    }

    public function getExeDate(): DateTime
    {
        return $this->exeDate;
    }

    public function setExeDate(DateTime $exeDate): self
    {
        $this->exeDate = $exeDate;

        return $this;
    }

    public function getQuiz(): ?CQuiz
    {
        return $this->quiz;
    }

    public function setQuiz(CQuiz $cQuiz): self
    {
        $this->quiz = $cQuiz;

        return $this;
    }

    public function getUserIp(): string
    {
        return $this->userIp;
    }

    public function setUserIp(string $userIp): self
    {
        $this->userIp = $userIp;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getDataTracking(): string
    {
        return $this->dataTracking;
    }

    public function setDataTracking(string $dataTracking): self
    {
        $this->dataTracking = $dataTracking;

        return $this;
    }

    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(DateTime $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getStepsCounter(): int
    {
        return $this->stepsCounter;
    }

    public function setStepsCounter(int $stepsCounter): self
    {
        $this->stepsCounter = $stepsCounter;

        return $this;
    }

    public function getOrigLpId(): int
    {
        return $this->origLpId;
    }

    public function setOrigLpId(int $origLpId): self
    {
        $this->origLpId = $origLpId;

        return $this;
    }

    public function getOrigLpItemId(): int
    {
        return $this->origLpItemId;
    }

    public function setOrigLpItemId(int $origLpItemId): self
    {
        $this->origLpItemId = $origLpItemId;

        return $this;
    }

    public function getExeDuration(): int
    {
        return $this->exeDuration;
    }

    public function setExeDuration(int $exeDuration): self
    {
        $this->exeDuration = $exeDuration;

        return $this;
    }

    public function getExpiredTimeControl(): ?DateTime
    {
        return $this->expiredTimeControl;
    }

    public function setExpiredTimeControl(?DateTime $expiredTimeControl): self
    {
        $this->expiredTimeControl = $expiredTimeControl;

        return $this;
    }

    public function getOrigLpItemViewId(): int
    {
        return $this->origLpItemViewId;
    }

    public function setOrigLpItemViewId(int $origLpItemViewId): self
    {
        $this->origLpItemViewId = $origLpItemViewId;

        return $this;
    }

    public function getQuestionsToCheck(): string
    {
        return $this->questionsToCheck;
    }

    public function setQuestionsToCheck(string $questionsToCheck): self
    {
        $this->questionsToCheck = $questionsToCheck;

        return $this;
    }

    public function getExeId(): int
    {
        return $this->exeId;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function setScore(float $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getMaxScore(): float
    {
        return $this->maxScore;
    }

    public function setMaxScore(float $maxScore): self
    {
        $this->maxScore = $maxScore;

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

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    /**
     * @return Collection<int, TrackEAttempt>
     */
    public function getAttempts(): Collection
    {
        return $this->attempts;
    }

    /**
     * @param Collection<int, TrackEAttempt> $attempts
     */
    public function setAttempts(Collection $attempts): self
    {
        $this->attempts = $attempts;

        return $this;
    }

    public function addAttempt(TrackEAttempt $attempt): self
    {
        if (!$this->attempts->contains($attempt)) {
            $this->attempts[] = $attempt;
            $attempt->setTrackEExercise($this);
        }

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

    public function getAttemptByQuestionId(int $questionId): ?TrackEAttempt
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('questionId', $questionId))->setMaxResults(1);
        $attempt = $this->attempts->matching($criteria)->first();

        if (!empty($attempt)) {
            return $attempt;
        }

        return null;
    }
}
