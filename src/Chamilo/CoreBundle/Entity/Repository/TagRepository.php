<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldRelTag;
use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tag;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

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

    /**
     * @return array<Tag>
     */
    public function findForPortfolioInCourse(Course $course, ?Session $session = null): array
    {
        $qb = $this
            ->createQueryBuilder('t')
            ->innerJoin(ExtraField::class, 'ef', Join::WITH, 't.fieldId = ef.id')
            ->innerJoin(ExtraFieldRelTag::class, 'efrt', Join::WITH, 't.id = efrt.tagId AND ef.id = efrt.fieldId')
            ->innerJoin(Portfolio::class, 'p', Join::WITH, 'efrt.itemId = p.id')
            ->where('ef.variable = :variable')
            ->andWhere('ef.extraFieldType = :type')
            ->andWhere('p.course = :course')
            ->setParameter('variable', 'tags')
            ->setParameter('type', ExtraField::PORTFOLIO_TYPE)
            ->setParameter('course', $course)
        ;

        if ($session) {
            $qb
                ->andWhere('p.session = :session')
                ->setParameter('session', $session)
            ;
        } else {
            $qb->andWhere('p.session IS NULL');
        }

        return $qb->getQuery()->getResult();
    }
}
