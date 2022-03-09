<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
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
 *
 * @ORM\Table(name="track_e_exercises", indexes={
 *     @ORM\Index(name="idx_tee_user_id", columns={"exe_user_id"}),
 *     @ORM\Index(name="idx_tee_c_id", columns={"c_id"}),
 *     @ORM\Index(name="session_id", columns={"session_id"})
 * })
 * @ORM\Entity
 */
#[ApiResource(
    collectionOperations: [
        'get' => [
            'security' => 'is_granted("ROLE_USER")',
        ],
    ],
    itemOperations: [
        'get' => [
            'security' => 'is_granted("VIEW", object)',
        ],
    ],
    attributes: [
        'security' => 'is_granted("ROLE_USER")',
    ],
    denormalizationContext: [
        'groups' => ['track_e_exercise:write'],
    ],
    normalizationContext: [
        'groups' => ['track_e_exercise:read'],
    ],
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'user' => 'exact',
        'quiz' => 'exact',
        'course' => 'exact',
        'session' => 'exact',
    ]
)]
class TrackEExercise
{
    /**
     * @ORM\Column(name="exe_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $exeId;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="exe_user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    #[Assert\NotNull]
    #[Groups(['track_e_exercise:read'])]
    protected User $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    #[Assert\NotNull]
    #[Groups(['track_e_exercise:read'])]
    protected Course $course;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[Groups(['track_e_exercise:read'])]
    protected ?Session $session = null;

    /**
     * @ORM\Column(name="exe_date", type="datetime", nullable=false)
     */
    #[Assert\NotBlank]
    #[Groups(['track_e_exercise:read'])]
    protected DateTime $exeDate;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CQuiz", inversedBy="attempts")
     * @ORM\JoinColumn(name="exe_exo_id", referencedColumnName="iid", nullable=true, onDelete="SET NULL")
     */
    #[Groups(['track_e_exercise:read'])]
    protected ?CQuiz $quiz;

    /**
     * @ORM\Column(name="score", type="float", precision=6, scale=2, nullable=false)
     */
    #[Assert\NotNull]
    #[Groups(['track_e_exercise:read'])]
    protected float $score;

    /**
     * @ORM\Column(name="max_score", type="float", precision=6, scale=2, nullable=false)
     */
    #[Assert\NotNull]
    #[Groups(['track_e_exercise:read'])]
    protected float $maxScore;

    /**
     * @ORM\Column(name="user_ip", type="string", length=39, nullable=false)
     */
    #[Assert\NotBlank]
    #[Groups(['track_e_exercise:read'])]
    protected string $userIp;

    /**
     * @ORM\Column(name="status", type="string", length=20, nullable=false)
     */
    #[Assert\NotNull]
    #[Groups(['track_e_exercise:read'])]
    protected string $status;

    /**
     * @ORM\Column(name="data_tracking", type="text", nullable=false)
     */
    #[Assert\NotNull]
    #[Groups(['track_e_exercise:read'])]
    protected string $dataTracking;

    /**
     * @ORM\Column(name="start_date", type="datetime", nullable=false)
     */
    #[Groups(['track_e_exercise:read'])]
    protected DateTime $startDate;

    /**
     * @ORM\Column(name="steps_counter", type="smallint", nullable=false)
     */
    #[Assert\NotNull]
    #[Groups(['track_e_exercise:read'])]
    protected int $stepsCounter;

    /**
     * @ORM\Column(name="orig_lp_id", type="integer", nullable=false)
     */
    #[Groups(['track_e_exercise:read'])]
    protected int $origLpId;

    /**
     * @ORM\Column(name="orig_lp_item_id", type="integer", nullable=false)
     */
    #[Groups(['track_e_exercise:read'])]
    protected int $origLpItemId;

    /**
     * @ORM\Column(name="exe_duration", type="integer", nullable=false)
     */
    #[Assert\NotNull]
    #[Groups(['track_e_exercise:read'])]
    protected int $exeDuration;

    /**
     * @ORM\Column(name="expired_time_control", type="datetime", nullable=true)
     */
    #[Groups(['track_e_exercise:read'])]
    protected ?DateTime $expiredTimeControl = null;

    /**
     * @ORM\Column(name="orig_lp_item_view_id", type="integer", nullable=false)
     */
    #[Groups(['track_e_exercise:read'])]
    protected int $origLpItemViewId;

    /**
     * @ORM\Column(name="questions_to_check", type="text", nullable=false)
     */
    #[Groups(['track_e_exercise:read'])]
    #[Assert\NotNull]
    protected string $questionsToCheck;

    /**
     * @ORM\Column(name="blocked_categories", type="text", nullable=true)
     */
    #[Groups(['track_e_exercise:read'])]
    protected ?string $blockedCategories;

    /**
     * @var Collection<int, TrackEAttempt>
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\TrackEAttempt", mappedBy="trackExercise", cascade={"persist"})
     */
    #[Groups(['track_e_exercise:read'])]
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

    public function setExeDate(DateTime $exeDate): self
    {
        $this->exeDate = $exeDate;

        return $this;
    }

    /**
     * Get exeDate.
     *
     * @return DateTime
     */
    public function getExeDate()
    {
        return $this->exeDate;
    }

    public function setQuiz(CQuiz $cQuiz): self
    {
        $this->quiz = $cQuiz;

        return $this;
    }

    public function getQuiz(): ?CQuiz
    {
        return $this->quiz;
    }

    public function setUserIp(string $userIp): self
    {
        $this->userIp = $userIp;

        return $this;
    }

    /**
     * Get userIp.
     *
     * @return string
     */
    public function getUserIp()
    {
        return $this->userIp;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function setDataTracking(string $dataTracking): self
    {
        $this->dataTracking = $dataTracking;

        return $this;
    }

    /**
     * Get dataTracking.
     *
     * @return string
     */
    public function getDataTracking()
    {
        return $this->dataTracking;
    }

    public function setStartDate(DateTime $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate.
     *
     * @return DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    public function setStepsCounter(int $stepsCounter): self
    {
        $this->stepsCounter = $stepsCounter;

        return $this;
    }

    /**
     * Get stepsCounter.
     *
     * @return int
     */
    public function getStepsCounter()
    {
        return $this->stepsCounter;
    }

    public function setOrigLpId(int $origLpId): self
    {
        $this->origLpId = $origLpId;

        return $this;
    }

    /**
     * Get origLpId.
     *
     * @return int
     */
    public function getOrigLpId()
    {
        return $this->origLpId;
    }

    public function setOrigLpItemId(int $origLpItemId): self
    {
        $this->origLpItemId = $origLpItemId;

        return $this;
    }

    /**
     * Get origLpItemId.
     *
     * @return int
     */
    public function getOrigLpItemId()
    {
        return $this->origLpItemId;
    }

    public function setExeDuration(int $exeDuration): self
    {
        $this->exeDuration = $exeDuration;

        return $this;
    }

    /**
     * Get exeDuration.
     *
     * @return int
     */
    public function getExeDuration()
    {
        return $this->exeDuration;
    }

    public function setExpiredTimeControl(?DateTime $expiredTimeControl): self
    {
        $this->expiredTimeControl = $expiredTimeControl;

        return $this;
    }

    public function getExpiredTimeControl(): ?DateTime
    {
        return $this->expiredTimeControl;
    }

    public function setOrigLpItemViewId(int $origLpItemViewId): self
    {
        $this->origLpItemViewId = $origLpItemViewId;

        return $this;
    }

    /**
     * Get origLpItemViewId.
     *
     * @return int
     */
    public function getOrigLpItemViewId()
    {
        return $this->origLpItemViewId;
    }

    public function setQuestionsToCheck(string $questionsToCheck): self
    {
        $this->questionsToCheck = $questionsToCheck;

        return $this;
    }

    /**
     * Get questionsToCheck.
     *
     * @return string
     */
    public function getQuestionsToCheck()
    {
        return $this->questionsToCheck;
    }

    /**
     * Get exeId.
     *
     * @return int
     */
    public function getExeId()
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
     * @return TrackEAttempt[]|Collection
     */
    public function getAttempts()
    {
        return $this->attempts;
    }

    /**
     * @param TrackEAttempt[]|Collection $attempts
     */
    public function setAttempts($attempts): self
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
        $criteria
            ->where(
                Criteria::expr()->eq('questionId', $questionId)
            )
            ->setMaxResults(1)
        ;

        /** @var TrackEAttempt $attempt */
        $attempt = $this->attempts->matching($criteria)->first();

        if (!empty($attempt)) {
            return $attempt;
        }

        return null;
    }
}
