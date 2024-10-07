<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted("VIEW", object)'),
        new GetCollection(security: 'is_granted("ROLE_USER")'),
    ],
    security: 'is_granted("ROLE_USER")'
)]
#[ApiFilter(
    SearchFilter::class,
    strategy: 'exact',
    properties: ['questionId', 'trackExercise', 'trackExercise.user'],
)]
#[ORM\Table(name: 'track_e_attempt_qualify')]
#[ORM\Index(columns: ['exe_id'], name: 'exe_id')]
#[ORM\Index(columns: ['question_id'], name: 'question_id')]
#[ORM\Index(columns: ['session_id'], name: 'session_id')]
#[ORM\Entity]
class TrackEAttemptQualify
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'question_id', type: 'integer', nullable: false)]
    protected int $questionId;

    #[ORM\Column(name: 'marks', type: 'float', nullable: false)]
    protected float $marks;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'insert_date', type: 'datetime', nullable: false)]
    protected DateTime $insertDate;

    #[ORM\Column(name: 'author', type: 'integer', nullable: false)]
    protected int $author;

    #[ORM\Column(name: 'teacher_comment', type: 'text', nullable: false)]
    protected string $teacherComment;

    #[ORM\Column(name: 'session_id', type: 'integer', nullable: false)]
    protected int $sessionId;

    #[ORM\Column(name: 'answer', type: 'text', nullable: true)]
    protected ?string $answer;

    #[ORM\ManyToOne(inversedBy: 'revisedAttempts')]
    #[ORM\JoinColumn(name: 'exe_id', referencedColumnName: 'exe_id', nullable: false, onDelete: 'CASCADE')]
    private ?TrackEExercise $trackExercise = null;

    public function __construct()
    {
        $this->teacherComment = '';
        $this->answer = null;
        $this->sessionId = 0;
        $this->author = 0;
    }

    public function getTrackExercise(): ?TrackEExercise
    {
        return $this->trackExercise;
    }

    /**
     * Set exeId.
     *
     * @return TrackEAttemptQualify
     */
    public function setTrackExercise(?TrackEExercise $trackExercise): static
    {
        $this->trackExercise = $trackExercise;

        return $this;
    }

    public function getQuestionId(): int
    {
        return $this->questionId;
    }

    /**
     * Set questionId.
     *
     * @return TrackEAttemptQualify
     */
    public function setQuestionId(int $questionId): static
    {
        $this->questionId = $questionId;

        return $this;
    }

    public function getMarks(): float
    {
        return $this->marks;
    }

    /**
     * Set marks (score).
     */
    public function setMarks(float $marks): self
    {
        $this->marks = $marks;

        return $this;
    }

    public function getInsertDate(): DateTime
    {
        return $this->insertDate;
    }

    /**
     * Set insert date.
     */
    public function setInsertDate(DateTime $insertDate): self
    {
        $this->insertDate = $insertDate;

        return $this;
    }

    public function getAuthor(): int
    {
        return $this->author;
    }

    /**
     * Set author.
     *
     * @return TrackEAttemptQualify
     */
    public function setAuthor(int $author): static
    {
        $this->author = $author;

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

    public function getSessionId(): int
    {
        return $this->sessionId;
    }

    /**
     * Set sessionId.
     *
     * @return TrackEAttemptQualify
     */
    public function setSessionId(int $sessionId): static
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(?string $answer): self
    {
        $this->answer = $answer;

        return $this;
    }
}
