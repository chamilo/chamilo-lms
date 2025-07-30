<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Chamilo\CoreBundle\Repository\TrackEAttemptRepository;
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
#[ApiResource(
    operations: [
        new Get(security: 'is_granted("VIEW", object)'),
        new GetCollection(security: 'is_granted("ROLE_USER")'),
    ],
    normalizationContext: [
        'groups' => ['track_e_attempt:read'],
    ],
    security: 'is_granted("ROLE_USER")'
)]
#[ORM\Table(name: 'track_e_attempt')]
#[ORM\Index(columns: ['exe_id'], name: 'exe_id')]
#[ORM\Index(columns: ['user_id'], name: 'user_id')]
#[ORM\Index(columns: ['question_id'], name: 'question_id')]
#[ORM\Index(columns: ['tms'], name: 'idx_track_e_attempt_tms')]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'user' => 'exact',
        'questionId' => 'exact',
        'answer' => 'exact',
        'marks' => 'exact',
    ]
)]
#[ORM\Entity(repositoryClass: TrackEAttemptRepository::class)]
class TrackEAttempt
{
    use UserTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected ?int $id = null;

    #[Assert\NotNull]
    #[ORM\ManyToOne(targetEntity: TrackEExercise::class, cascade: ['persist'], inversedBy: 'attempts')]
    #[ORM\JoinColumn(name: 'exe_id', referencedColumnName: 'exe_id', nullable: false, onDelete: 'CASCADE')]
    protected TrackEExercise $trackExercise;

    #[Assert\NotNull]
    #[Groups(['track_e_attempt:read'])]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'trackEAttempts')]
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
     * @var Collection<int, AttemptFile>
     */
    #[ORM\OneToMany(
        mappedBy: 'attempt',
        targetEntity: AttemptFile::class,
        cascade: ['persist'],
        orphanRemoval: true
    )]
    protected Collection $attemptFiles;

    /**
     * @var Collection<int, AttemptFeedback>
     */
    #[ORM\OneToMany(
        mappedBy: 'attempt',
        targetEntity: AttemptFeedback::class,
        cascade: ['persist'],
        orphanRemoval: true
    )]
    protected Collection $attemptFeedbacks;

    public function __construct()
    {
        $this->attemptFiles = new ArrayCollection();
        $this->attemptFeedbacks = new ArrayCollection();
        $this->teacherComment = '';
        $this->secondsSpent = 0;
    }

    public function getQuestionId(): ?int
    {
        return $this->questionId;
    }

    public function setQuestionId(int $questionId): self
    {
        $this->questionId = $questionId;

        return $this;
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): self
    {
        $this->answer = $answer;

        return $this;
    }

    public function getTeacherComment(): string
    {
        return $this->teacherComment;
    }

    public function setTeacherComment(string $teacherComment): self
    {
        $this->teacherComment = $teacherComment;

        return $this;
    }

    public function getMarks(): float
    {
        return $this->marks;
    }

    public function setMarks(float $marks): self
    {
        $this->marks = $marks;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getTms(): DateTime
    {
        return $this->tms;
    }

    public function setTms(DateTime $tms): self
    {
        $this->tms = $tms;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getId(): ?int
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
     * @return Collection<int, AttemptFile>
     */
    public function getAttemptFiles(): Collection
    {
        return $this->attemptFiles;
    }

    /**
     * @param Collection<int, AttemptFile> $attemptFiles
     */
    public function setAttemptFiles(Collection $attemptFiles): self
    {
        $this->attemptFiles = $attemptFiles;

        return $this;
    }

    /**
     * @return Collection<int, AttemptFeedback>
     */
    public function getAttemptFeedbacks(): Collection
    {
        return $this->attemptFeedbacks;
    }

    /**
     * @param Collection<int, AttemptFeedback> $attemptFeedbacks
     */
    public function setAttemptFeedbacks(Collection $attemptFeedbacks): self
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
