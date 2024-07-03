<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

/**
 * Class PortfolioRepository.
 *
 * @package Chamilo\CoreBundle\Entity\Repository
 */
class PortfolioRepository extends EntityRepository
{
    public function findItemsByUser(User $user, ?Course $course, ?Session $session, ?array $orderBy = null, array $visibility = []): array
    {
        $criteria = [];
        $criteria['user'] = $user;

        if ($course) {
            $criteria['course'] = $course;
            $criteria['session'] = $session;
        }

        if ($visibility) {
            $criteria['visibility'] = $visibility;
        }

        return $this->findBy($criteria, $orderBy);
    }
}
