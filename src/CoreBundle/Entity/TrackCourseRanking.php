<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: [
        'groups' => ['trackCourseRanking:read'],
    ],
    denormalizationContext: [
        'groups' => ['trackCourseRanking:write'],
    ],
    security: "is_granted('ROLE_USER')"
)]
#[ORM\Table(name: 'track_course_ranking')]
#[ORM\Index(columns: ['c_id'], name: 'idx_tcc_cid')]
#[ORM\Index(columns: ['session_id'], name: 'idx_tcc_sid')]
#[ORM\Index(columns: ['url_id'], name: 'idx_tcc_urlid')]
#[ORM\Index(columns: ['creation_date'], name: 'idx_tcc_creation_date')]
#[ORM\Entity]
class TrackCourseRanking
{
    #[Groups(['course:read', 'trackCourseRanking:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected ?int $id = null;

    #[Groups(['course:read', 'trackCourseRanking:read', 'trackCourseRanking:write'])]
    #[ORM\OneToOne(inversedBy: 'trackCourseRanking', targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    protected Course $course;

    #[Groups(['trackCourseRanking:read', 'trackCourseRanking:write'])]
    #[ORM\Column(name: 'session_id', type: 'integer', nullable: false)]
    protected int $sessionId;

    #[Groups(['trackCourseRanking:read', 'trackCourseRanking:write'])]
    #[ORM\Column(name: 'url_id', type: 'integer', nullable: false)]
    protected int $urlId;

    #[Groups(['course:read', 'trackCourseRanking:read'])]
    #[ORM\Column(name: 'accesses', type: 'integer', nullable: false)]
    protected int $accesses;

    #[Groups(['course:read', 'trackCourseRanking:read', 'trackCourseRanking:write'])]
    #[ORM\Column(name: 'total_score', type: 'integer', nullable: false)]
    protected int $totalScore;

    #[Groups(['course:read'])]
    #[ORM\Column(name: 'users', type: 'integer', nullable: false)]
    protected int $users;

    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: false)]
    protected DateTime $creationDate;

    public function __construct()
    {
        $this->urlId = 0;
        $this->accesses = 0;
        $this->totalScore = 0;
        $this->users = 0;
        $this->creationDate = new DateTime();
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

    public function getSessionId(): int
    {
        return $this->sessionId;
    }

    public function setSessionId(int $sessionId): static
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    public function getUrlId(): int
    {
        return $this->urlId;
    }

    public function setUrlId(int $urlId): static
    {
        $this->urlId = $urlId;

        return $this;
    }

    public function getAccesses(): int
    {
        return $this->accesses;
    }

    public function setAccesses(int $accesses): static
    {
        $this->accesses = $accesses;

        return $this;
    }

    public function getTotalScore(): int
    {
        return $this->totalScore;
    }

    public function setTotalScore(int $totalScore): static
    {
        $this->users++;
        $this->totalScore += $totalScore;

        return $this;
    }

    public function getUsers(): int
    {
        return $this->users;
    }

    public function setUsers(int $users): static
    {
        $this->users = $users;

        return $this;
    }

    public function getCreationDate(): DateTime
    {
        return $this->creationDate;
    }

    public function setCreationDate(DateTime $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(['course:read', 'trackCourseRanking:read'])]
    public function getRealTotalScore(): int
    {
        if (0 !== $this->totalScore && 0 !== $this->users) {
            return (int) round($this->totalScore / $this->users);
        }

        return 0;
    }
}
