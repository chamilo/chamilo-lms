<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Questions per quiz user attempts.
 */
#[ApiResource(operations: [new Get(security: 'is_granted("VIEW", object)'), new GetCollection(security: 'is_granted("ROLE_USER")')], security: 'is_granted("ROLE_USER")', normalizationContext: ['groups' => ['track_e_attempt:read']])]
#[ORM\Table(name: 'track_e_attempt')]
#[ORM\Index(name: 'exe_id', columns: ['exe_id'])]
#[ORM\Index(name: 'user_id', columns: ['user_id'])]
#[ORM\Index(name: 'question_id', columns: ['question_id'])]
#[ORM\Index(name: 'idx_track_e_attempt_tms', columns: ['tms'])]
#[ORM\Entity]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['user' => 'exact', 'questionId' => 'exact', 'answer' => 'exact', 'marks' => 'exact'])]
class TrackEAttempt
{
    use UserTrait;
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected ?int $id = null;
    #[Assert\NotNull]
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\TrackEExercise::class, inversedBy: 'attempts')]
    #[ORM\JoinColumn(name: 'exe_id', referencedColumnName: 'exe_id', nullable: false, onDelete: 'CASCADE')]
    protected TrackEExercise $trackExercise;
    #[Assert\NotNull]
    #[Groups(['track_e_attempt:read'])]
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\User::class, inversedBy: 'trackEAttempts')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;
    #[Assert\NotBlank]
    #[Groups(['track_e_attempt:read'])]
    #[ORM\Column(name: 'question_id', type: 'integer', nullable: false)]
    protected ?int $questionId = null;
    #[Groups(['track_e_attempt:read'])]
    #[ORM\Column(name: 'answer', type: 'text', nullable: false)]
    protected string $answer;
    #[ORM\Column(name: 'teacher_comment', type: 'text', nullable: false)]
    protected string $teacherComment;
    #[Groups(['track_e_attempt:read'])]
    #[ORM\Column(name: 'marks', type: 'float', precision: 6, scale: 2, nullable: false)]
    protected float $marks;
    #[ORM\Column(name: 'position', type: 'integer', nullable: true)]
    protected ?int $position = null;
    #[Assert\NotNull]
    #[ORM\Column(name: 'tms', type: 'datetime', nullable: false)]
    protected DateTime $tms;
    #[ORM\Column(name: 'filename', type: 'string', length: 255, nullable: true)]
    protected ?string $filename = null;
    #[ORM\Column(name: 'seconds_spent', type: 'integer')]
    protected int $secondsSpent;
    /**
     * @var Collection|AttemptFile[]
     */
    #[ORM\OneToMany(targetEntity: \Chamilo\CoreBundle\Entity\AttemptFile::class, mappedBy: 'attempt', cascade: ['persist'], orphanRemoval: true)]
    protected Collection $attemptFiles;
    /**
     * @var Collection|AttemptFeedback[]
     */
    #[ORM\OneToMany(targetEntity: \Chamilo\CoreBundle\Entity\AttemptFeedback::class, mappedBy: 'attempt', cascade: ['persist'], orphanRemoval: true)]
    protected Collection $attemptFeedbacks;
    public function __construct()
    {
        $this->attemptFiles = new ArrayCollection();
        $this->attemptFeedbacks = new ArrayCollection();
        $this->teacherComment = '';
        $this->secondsSpent = 0;
    }
    public function setQuestionId(int $questionId): self
    {
        $this->questionId = $questionId;

        return $this;
    }
    /**
     * Get questionId.
     *
     * @return int
     */
    public function getQuestionId()
    {
        return $this->questionId;
    }
    public function setAnswer(string $answer): self
    {
        $this->answer = $answer;

        return $this;
    }
    /**
     * Get answer.
     *
     * @return string
     */
    public function getAnswer()
    {
        return $this->answer;
    }
    public function setTeacherComment(string $teacherComment): self
    {
        $this->teacherComment = $teacherComment;

        return $this;
    }
    /**
     * Get teacherComment.
     *
     * @return string
     */
    public function getTeacherComment()
    {
        return $this->teacherComment;
    }
    public function setMarks(float $marks): self
    {
        $this->marks = $marks;

        return $this;
    }
    /**
     * Get marks.
     *
     * @return float
     */
    public function getMarks()
    {
        return $this->marks;
    }
    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }
    /**
     * Get position.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }
    public function setTms(DateTime $tms): self
    {
        $this->tms = $tms;

        return $this;
    }
    /**
     * Get tms.
     *
     * @return DateTime
     */
    public function getTms()
    {
        return $this->tms;
    }
    /**
     * Set filename.
     *
     * @return TrackEAttempt
     */
    public function setFilename(string $filename)
    {
        $this->filename = $filename;

        return $this;
    }
    /**
     * Get filename.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
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
    public function getUser(): User
    {
        return $this->user;
    }
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
    public function getTrackEExercise(): TrackEExercise
    {
        return $this->trackExercise;
    }
    public function setTrackEExercise(TrackEExercise $trackExercise): self
    {
        $this->trackExercise = $trackExercise;

        return $this;
    }
    public function getSecondsSpent(): int
    {
        return $this->secondsSpent;
    }
    public function setSecondsSpent(int $secondsSpent): self
    {
        $this->secondsSpent = $secondsSpent;

        return $this;
    }
    /**
     * @return AttemptFile[]|Collection
     */
    public function getAttemptFiles(): array|Collection
    {
        return $this->attemptFiles;
    }
    /**
     * @param AttemptFile[]|Collection $attemptFiles
     */
    public function setAttemptFiles(array|Collection $attemptFiles): self
    {
        $this->attemptFiles = $attemptFiles;

        return $this;
    }
    /**
     * @return AttemptFeedback[]|Collection
     */
    public function getAttemptFeedbacks(): array|Collection
    {
        return $this->attemptFeedbacks;
    }
    /**
     * @param AttemptFeedback[]|Collection $attemptFeedbacks
     */
    public function setAttemptFeedbacks(array|Collection $attemptFeedbacks): self
    {
        $this->attemptFeedbacks = $attemptFeedbacks;

        return $this;
    }
    public function addAttemptFeedback(AttemptFeedback $attemptFeedback): self
    {
        if (!$this->attemptFeedbacks->contains($attemptFeedback)) {
            $this->attemptFeedbacks[] = $attemptFeedback;
            $attemptFeedback->setAttempt($this);
        }

        return $this;
    }
    public function addAttemptFile(AttemptFile $attemptFile): self
    {
        if (!$this->attemptFiles->contains($attemptFile)) {
            $this->attemptFiles[] = $attemptFile;
            $attemptFile->setAttempt($this);
        }

        return $this;
    }
}
