<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Templates;
use Chamilo\UserBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;

/**
 * TemplatesRepository class.
 */
class TemplatesRepository extends ServiceEntityRepository
{
    /**
     * TemplatesRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Templates::class);
    }

    /**
     * Get the course template for a user.
     *
     * @param Course $course
     * @param User   $user
     *
     * @return array
     */
    public function getCourseTemplates(Course $course, User $user)
    {
        $qb = $this->createQueryBuilder('t');

        $qb->select('t', 'd.path')
            ->innerJoin(
                'ChamiloCoreBundle:Course',
                'c',
                Join::WITH,
                $qb->expr()->eq('t.id', 'c.id')
            )
            ->innerJoin(
                'ChamiloCourseBundle:CDocument',
                'd',
                Join::WITH,
                $qb->expr()->eq('c.id', 'd.course')
            )
            ->where(
                $qb->expr()->eq('d.iid', 't.refDoc')
            )
            ->andWhere(
                $qb->expr()->eq('c.id', $course->getId())
            )
            ->andWhere(
                $qb->expr()->eq('t.userId', $user->getId())
            );

        return $qb->getQuery()->getResult();
    }
}
