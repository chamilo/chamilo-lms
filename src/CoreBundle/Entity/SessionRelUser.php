<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User subscriptions to a session. See also SessionRelCourseRelUser.php for a more detail subscription by course.
 */
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_ADMIN') or object.user == user"),
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: [
        'groups' => [
            'session_rel_user:read',
        ],
    ],
    security: "is_granted('ROLE_USER')"
)]
#[ORM\Table(name: 'session_rel_user')]
#[ORM\Index(columns: ['user_id', 'moved_to'], name: 'idx_session_rel_user_id_user_moved')]
#[ORM\UniqueConstraint(name: 'session_user_unique', columns: ['session_id', 'user_id', 'relation_type'])]
#[ORM\Entity]
#[UniqueEntity(
    fields: ['session', 'user', 'relationType'],
    message: 'The user-course-relationType is already registered in this session.'
)]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: ['session' => 'exact', 'user' => 'exact', 'relationType' => 'exact']
)]
#[ApiFilter(
    filterClass: DateFilter::class,
    properties: [
        'session.displayStartDate',
        'session.displayEndDate',
        'session.accessStartDate',
        'session.accessEndDate',
        'session.coachAccessStartDate',
        'session.coachAccessEndDate',
    ]
)]
class SessionRelUser
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotNull]
    #[Groups(['session_rel_user:read'])]
    #[ORM\ManyToOne(targetEntity: Session::class, cascade: ['persist'], inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id')]
    protected ?Session $session = null;

    #[Assert\NotNull]
    #[Groups(['session_rel_user:read', 'session:item:read'])]
    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'sessionsRelUser')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    protected User $user;

    #[Groups(['session_rel_user:read', 'session:item:read'])]
    #[Assert\Choice(callback: [Session::class, 'getRelationTypeList'], message: 'Choose a valid relation type.')]
    #[ORM\Column(name: 'relation_type', type: 'integer')]
    protected int $relationType;

    #[Groups(['session_rel_user:read'])]
    #[ORM\Column(name: 'duration', type: 'integer', nullable: false)]
    protected int $duration;

    #[ORM\Column(name: 'moved_to', type: 'integer', unique: false, nullable: true)]
    protected ?int $movedTo;

    #[ORM\Column(name: 'moved_status', type: 'integer', unique: false, nullable: true)]
    protected ?int $movedStatus;

    #[ORM\Column(name: 'moved_at', type: 'datetime', unique: false, nullable: true)]
    protected ?DateTime $movedAt = null;

    #[ORM\Column(name: 'registered_at', type: 'datetime')]
    protected DateTime $registeredAt;

    #[Groups(['session_rel_user:read'])]
    protected Collection $courses;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->relationType = Session::STUDENT;
        $this->duration = 0;
        $this->movedTo = null;
        $this->movedStatus = null;
        $this->registeredAt = new DateTime('now', new DateTimeZone('UTC'));
    }

    public function getCourses(): Collection
    {
        return $this->session->getSessionRelCourseByUser($this->getUser());
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $user->addSessionRelUser($this);
        $this->user = $user;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getRelationType(): int
    {
        return $this->relationType;
    }

    public function setRelationType(int $relationType): self
    {
        $this->relationType = $relationType;

        return $this;
    }

    public function getMovedTo(): ?int
    {
        return $this->movedTo;
    }

    public function setMovedTo(int $movedTo): self
    {
        $this->movedTo = $movedTo;

        return $this;
    }

    public function getMovedStatus(): ?int
    {
        return $this->movedStatus;
    }

    public function setMovedStatus(int $movedStatus): self
    {
        $this->movedStatus = $movedStatus;

        return $this;
    }

    public function getMovedAt(): ?DateTime
    {
        return $this->movedAt;
    }

    public function setMovedAt(DateTime $movedAt): self
    {
        $this->movedAt = $movedAt;

        return $this;
    }

    public function getRegisteredAt(): DateTime
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(DateTime $registeredAt): self
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }
}
