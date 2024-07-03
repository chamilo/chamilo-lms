<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\XApi\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

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

    public function countByCourseAndSession(Course $course, Session $session = null, $filteredForStudent = false): int
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

        if ($filteredForStudent) {
            $qb
                ->leftJoin(
                    CLpItem::class,
                    'lpi',
                    Join::WITH,
                    "tl.id = lpi.path AND tl.course = lpi.cId AND lpi.itemType = 'xapi'"
                )
                ->andWhere($qb->expr()->isNull('lpi.path'));
        }

        $query = $qb->getQuery();

        return (int) $query->getSingleScalarResult();
    }

    public function wasAddedInLp(ToolLaunch $toolLaunch): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        return (int) $qb->select($qb->expr()->count('lp'))
            ->from(CLp::class, 'lp')
            ->innerJoin(CLpItem::class, 'lpi', Join::WITH, 'lp.id = lpi.lpId')
            ->where('lpi.itemType = :type')
            ->andWhere('lpi.path = :tool_id')
            ->setParameter('type', TOOL_XAPI)
            ->setParameter('tool_id', $toolLaunch->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }
}
