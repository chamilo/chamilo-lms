<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Table(name="user_career")
 * @ORM\Entity
 */
class UserCareer
{
    use TimestampableEntity;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected User $user;

    /**
     * @ORM\Column(name="career_id", type="integer", nullable=false)
     */
    protected Career $career;

    /**
     * @ORM\Column(name="extra_data", type="text", nullable=true)
     */
    protected string $extraData;
}
