<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

/**
 * TrackECourseAccessRepository.
 *
 * @package Chamilo\CoreBundle\Entity\Repository
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class TrackECourseAccessRepository extends EntityRepository
{
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
