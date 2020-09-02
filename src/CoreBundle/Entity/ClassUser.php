<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClassUser.
 *
 * @ORM\Table(name="class_user")
 * @ORM\Entity
 *
 * @deprecated  The class user class will be removed as it will not be used.
 */
class ClassUser
{
    /**
     * @var int
     *
     * @ORM\Column(name="class_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected $classId;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="class_user")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set classId.
     *
     * @param int $classId
     *
     * @return ClassUser
     */
    public function setClassId($classId)
    {
        $this->classId = $classId;

        return $this;
    }

    /**
     * Get classId.
     *
     * @return int
     */
    public function getClassId()
    {
        return $this->classId;
    }
}
