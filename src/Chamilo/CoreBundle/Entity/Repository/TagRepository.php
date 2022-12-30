<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\PortfolioRelTag;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * @method array findById(int|array $ids)
 */
class TagRepository extends EntityRepository
{
    public function findByFieldIdAndText(int $field, string $text, int $limit = 0)
    {
        $qb = $this
            ->createQueryBuilder('t')
            ->where("t.tag LIKE :text")
            ->andWhere('t.fieldId = :field');

        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        return $qb
            ->setParameter('field', $field)
            ->setParameter('text', "$text%")
            ->getQuery()
            ->getResult();
    }

    public function findForPortfolioInCourseQuery(Course $course, ?Session $session = null): QueryBuilder
    {
        $qb = $this
            ->createQueryBuilder('t')
            ->innerJoin(PortfolioRelTag::class, 'prt', Join::WITH, 't = prt.tag')
            ->where('prt.course = :course')
            ->setParameter('course', $course)
        ;

        if ($session) {
            $qb
                ->andWhere('prt.session = :session')
                ->setParameter('session', $session)
            ;
        } else {
            $qb->andWhere('prt.session IS NULL');
        }

        return $qb;
    }
}
