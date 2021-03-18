<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\XApi\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\ORM\EntityRepository;

/**
 * Class ToolLaunchRepository.
 *
 * @package Chamilo\PluginBundle\Entity\XApi\Repository
 */
class ToolLaunchRepository extends EntityRepository
{
    public function findByCourseAndSession(
        Course $course,
        Session $session = null,
        array $orderBy = [],
        int $limit = null,
        int $start = null
    ): array {
        $criteria = [
            'course' => $course,
            'session' => null,
        ];

        if ($session) {
            $criteria['session'] = $session;
        }

        return $this->findBy($criteria, $orderBy, $limit, $start);
    }

    public function countByCourseAndSession(Course $course, Session $session = null): int
    {
        $qb = $this->createQueryBuilder('tl');
        $qb->select($qb->expr()->count('tl'))
            ->where($qb->expr()->eq('tl.course', ':course'))
            ->setParameter('course', $course);

        if ($session) {
            $qb->andWhere($qb->expr()->eq('tl.session', ':session'))
                ->setParameter('session', $session);
        } else {
            $qb->andWhere($qb->expr()->isNull('tl.session'));
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
