<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

/**
 * TemplatesRepository class.
 */
class TemplatesRepository extends EntityRepository
{
    /**
     * Get the course template for a user.
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
                $qb->expr()->eq('t.courseCode', 'c.code')
            )
            ->innerJoin(
                'ChamiloCourseBundle:CDocument',
                'd',
                Join::WITH,
                $qb->expr()->eq('c.id', 'd.cId')
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
