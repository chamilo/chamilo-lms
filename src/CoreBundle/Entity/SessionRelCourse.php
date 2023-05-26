<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Course subscriptions to a session.
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'VIEW\', object)'), new Put(security: 'is_granted(\'ROLE_ADMIN\')'), new GetCollection(security: 'is_granted(\'ROLE_USER\')'), new Post(security: 'is_granted(\'ROLE_ADMIN\')')], denormalizationContext: ['groups' => ['session_rel_course:write']], normalizationContext: ['groups' => ['session_rel_course:read']])]
#[ORM\Table(name: 'session_rel_course')]
#[ORM\Index(name: 'idx_session_rel_course_course_id', columns: ['c_id'])]
#[ORM\UniqueConstraint(name: 'course_session_unique', columns: ['session_id', 'c_id'])]
#[ORM\Entity]
#[UniqueEntity(fields: ['course', 'session'], message: 'The course is already registered in this session.')]
class SessionRelCourse
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;
    #[Groups(['session_rel_course:read', 'session_rel_course:write'])]
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\Session::class, inversedBy: 'courses', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false)]
    protected ?Session $session = null;
    #[Groups(['session_rel_course:read', 'session_rel_course:write', 'session:read'])]
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\Course::class, inversedBy: 'sessions', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: false)]
    protected ?Course $course = null;
    #[ORM\Column(name: 'position', type: 'integer', nullable: false)]
    protected int $position;
    #[ORM\Column(name: 'nbr_users', type: 'integer')]
    protected int $nbrUsers;
    public function __construct()
    {
        $this->nbrUsers = 0;
        $this->position = 0;
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
    public function setSession(Session $session): self
    {
        $this->session = $session;

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
    public function getSession(): Session
    {
        return $this->session;
    }
    public function setNbrUsers(int $nbrUsers): self
    {
        $this->nbrUsers = $nbrUsers;

        return $this;
    }
    public function getNbrUsers(): int
    {
        return $this->nbrUsers;
    }
    public function getPosition(): int
    {
        return $this->position;
    }
    public function setPosition(int $position): void
    {
        $this->position = $position;
    }
}
