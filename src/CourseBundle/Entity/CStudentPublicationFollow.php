<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Repository\CStudentPublicationFollowRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * A teacher (or session coach) following an assignment: followers are the only
 * users e-mailed when a learner comments on a submission of that assignment.
 *
 * Kept apart from c_student_publication_rel_user on purpose: that table lists the
 * users an assignment is restricted to, and mixing both meanings would change who
 * can see restricted assignments.
 */
#[ORM\Table(name: 'c_student_publication_follow')]
#[ORM\UniqueConstraint(name: 'uniq_student_publication_follow', columns: ['publication_id', 'user_id'])]
#[ORM\Entity(repositoryClass: CStudentPublicationFollowRepository::class)]
class CStudentPublicationFollow
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CStudentPublication::class)]
    #[ORM\JoinColumn(name: 'publication_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    protected CStudentPublication $publication;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    protected DateTime $createdAt;

    public function __construct(CStudentPublication $publication, User $user)
    {
        $this->publication = $publication;
        $this->user = $user;
        $this->createdAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublication(): CStudentPublication
    {
        return $this->publication;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
}
