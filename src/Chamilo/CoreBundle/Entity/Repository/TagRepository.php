<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

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
}
