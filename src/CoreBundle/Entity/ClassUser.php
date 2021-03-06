<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClassUser.
 *
 * @ORM\Table(name="class_user")
 * @ORM\Entity
 */
class ClassUser
{
    /**
     * @ORM\Column(name="class_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected int $classId;

    /**
     * @ORM\Column(name="user_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected int $userId;

    /**
     * Set classId.
     *
     * @return ClassUser
     */
    public function setClassId(int $classId)
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

    /**
     * Set userId.
     *
     * @return ClassUser
     */
    public function setUserId(int $userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }
}
