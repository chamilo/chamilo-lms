<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PortfolioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Portfolio::class);
    }

    public function findItemsByUser(
        User $user,
        ?Course $course,
        ?Session $session,
        ?array $orderBy = null,
        array $visibility = []
    ): array {
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
