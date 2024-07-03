<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\XApiToolLaunch;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<XApiToolLaunch>
 *
 * @method XApiToolLaunch|null find($id, $lockMode = null, $lockVersion = null)
 * @method XApiToolLaunch|null findOneBy(array $criteria, array $orderBy = null)
 * @method XApiToolLaunch[]    findAll()
 * @method XApiToolLaunch[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class XApiToolLaunchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, XApiToolLaunch::class);
    }

    /**
     * @return array<int, XApiToolLaunch>
     */
    public function findByCourseAndSession(
        Course $course,
        ?Session $session = null,
        array $orderBy = [],
        ?int $limit = null,
        ?int $start = null
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

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countByCourseAndSession(
        Course $course,
        ?Session $session = null,
        bool $filteredForStudent = false
    ): int {
        $qb = $this->createQueryBuilder('tl');
        $qb->select($qb->expr()->count('tl'))
            ->where($qb->expr()->eq('tl.course', ':course'))
            ->setParameter('course', $course)
        ;

        if ($session) {
            $qb->andWhere($qb->expr()->eq('tl.session', ':session'))
                ->setParameter('session', $session)
            ;
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
                ->andWhere($qb->expr()->isNull('lpi.path'))
            ;
        }

        $query = $qb->getQuery();

        return (int) $query->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function wasAddedInLp(XApiToolLaunch $toolLaunch): int
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
            ->getSingleScalarResult()
        ;
    }
}
