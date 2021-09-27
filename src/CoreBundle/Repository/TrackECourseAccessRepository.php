<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class TrackECourseAccessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackECourseAccess::class);
    }

    /**
     * Get the last registered access by an user.
     */
    public function getLastAccessByUser(User $user = null): ?TrackECourseAccess
    {
        if (null === $user) {
            return null;
        }

        $lastAccess = $this->findBy(
            [
                'userId' => $user->getId(),
            ],
            [
                'courseAccessId' => 'DESC',
            ],
            1
        );

        if (!empty($lastAccess)) {
            return $lastAccess[0];
        }

        return null;
    }
}
