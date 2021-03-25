<?php

/* For license terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\TopLinks\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Class TopLinkRelToolRepository.
 *
 * @package Chamilo\PluginBundle\Entity\TopLinks\Repository
 */
class TopLinkRelToolRepository extends EntityRepository
{
    public function findInCourse(Course $course)
    {
        $qb = $this->createQueryBuilder('tlrt');

        return $qb
            ->innerJoin('tlrt.tool', 'tool', Join::WITH)
            ->where($qb->expr()->eq('tool.cId', ':course'))
            ->setParameter('course', $course)
            ->getQuery()
            ->getResult();
    }
}
