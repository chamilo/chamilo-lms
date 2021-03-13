<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * CStudentPublicationRelUser.
 *
 * @ORM\Table(
 *     name="c_student_publication_rel_user",
 *     indexes={
 *     }
 * )
 * @ORM\Entity
 */
class CStudentPublicationRelUser
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\ManyToOne(targetEntity="CStudentPublication")
     * @ORM\JoinColumn(name="work_id", referencedColumnName="iid", onDelete="CASCADE")
     */
    protected CStudentPublication $publication;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    public function getPublication(): CStudentPublication
    {
        return $this->publication;
    }

    public function setPublication(CStudentPublication $publication): self
    {
        $this->publication = $publication;

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
}
