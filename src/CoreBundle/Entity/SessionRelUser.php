<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

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
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_ADMIN\') or object.user == user'), new GetCollection(security: 'is_granted(\'ROLE_ADMIN\')'), new Post(security: 'is_granted(\'ROLE_ADMIN\')')], security: 'is_granted(\'ROLE_USER\')', normalizationContext: ['groups' => ['session_rel_user:read']])]
#[ORM\Table(name: 'session_rel_user')]
#[ORM\Index(name: 'idx_session_rel_user_id_user_moved', columns: ['user_id', 'moved_to'])]
#[ORM\UniqueConstraint(name: 'session_user_unique', columns: ['session_id', 'user_id', 'relation_type'])]
#[ORM\Entity]
#[UniqueEntity(fields: ['session', 'user', 'relationType'], message: 'The user-course-relationType is already registered in this session.')]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['session' => 'exact', 'user' => 'exact', 'relationType' => 'exact'])]
#[ApiFilter(filterClass: DateFilter::class, properties: ['session.displayStartDate', 'session.displayEndDate', 'session.accessStartDate', 'session.accessEndDate', 'session.coachAccessStartDate', 'session.coachAccessEndDate'])]
class SessionRelUser
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;
    #[Assert\NotNull]
    #[Groups(['session_rel_user:read'])]
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\Session::class, inversedBy: 'users', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id')]
    protected ?Session $session = null;
    #[Assert\NotNull]
    #[Groups(['session_rel_user:read'])]
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\User::class, inversedBy: 'sessionsRelUser', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    protected User $user;
    #[Groups(['session_rel_user:read'])]
    #[Assert\Choice(callback: [Session::class, 'getRelationTypeList'], message: 'Choose a valid relation type.')]
    #[ORM\Column(name: 'relation_type', type: 'integer')]
    protected int $relationType;
    #[Groups(['session_rel_user:read'])]
    #[ORM\Column(name: 'duration', type: 'integer', nullable: false)]
    protected int $duration;
    #[ORM\Column(name: 'moved_to', type: 'integer', nullable: true, unique: false)]
    protected ?int $movedTo;
    #[ORM\Column(name: 'moved_status', type: 'integer', nullable: true, unique: false)]
    protected ?int $movedStatus;
    #[ORM\Column(name: 'moved_at', type: 'datetime', nullable: true, unique: false)]
    protected ?DateTime $movedAt = null;
    #[ORM\Column(name: 'registered_at', type: 'datetime')]
    protected DateTime $registeredAt;
    #[Groups(['session_rel_user:read'])]
    protected Collection $courses;
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
    public function getId(): ?int
    {
        return $this->id;
    }
    public function setSession(?Session $session): self
    {
        $this->session = $session;

        return $this;
    }
    public function getSession(): ?Session
    {
        return $this->session;
    }
    public function setRelationType(int $relationType): self
    {
        $this->relationType = $relationType;

        return $this;
    }
    public function getRelationType(): int
    {
        return $this->relationType;
    }
    public function setMovedTo(int $movedTo): self
    {
        $this->movedTo = $movedTo;

        return $this;
    }
    /**
     * Get movedTo.
     *
     * @return int
     */
    public function getMovedTo()
    {
        return $this->movedTo;
    }
    public function setMovedStatus(int $movedStatus): self
    {
        $this->movedStatus = $movedStatus;

        return $this;
    }
    /**
     * Get movedStatus.
     *
     * @return int
     */
    public function getMovedStatus()
    {
        return $this->movedStatus;
    }
    public function setMovedAt(DateTime $movedAt): self
    {
        $this->movedAt = $movedAt;

        return $this;
    }
    /**
     * Get movedAt.
     *
     * @return DateTime
     */
    public function getMovedAt()
    {
        return $this->movedAt;
    }
    public function setRegisteredAt(DateTime $registeredAt): self
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }
    /**
     * Get registeredAt.
     *
     * @return DateTime
     */
    public function getRegisteredAt()
    {
        return $this->registeredAt;
    }
    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }
    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
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
}
