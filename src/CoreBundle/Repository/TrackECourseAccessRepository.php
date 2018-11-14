<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\UserBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * TrackECourseAccessRepository.
 *
 * @package Chamilo\CoreBundle\Repository
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class TrackECourseAccessRepository extends ServiceEntityRepository
{
    /**
     * TrackECourseAccessRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackECourseAccess::class);
    }

    /**
     * Get the last registered access by an user.
     *
     * @param User $user The user
     *
     * @return TrackECourseAccess The access if exists.
     *                            Otherwise return null
     */
    public function getLastAccessByUser(User $user)
    {
        if (empty($user)) {
            return null;
        }

        $lastAccess = $this->findBy(
            ['userId' => $user->getId()],
            ['courseAccessId' => 'DESC'],
            1
        );

        if (!empty($lastAccess)) {
            return $lastAccess[0];
        }

        return null;
    }
}
