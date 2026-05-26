<?php

/* For license terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\PluginBundle\TopLinks\Entity\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\PluginBundle\TopLinks\Entity\TopLink;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class TopLinkRelToolRepository extends EntityRepository
{
    public function findInCourse(Course $course): array
    {
        $qb = $this->createQueryBuilder('tlrt');

        return $qb
            ->innerJoin('tlrt.tool', 'tool', Join::WITH)
            ->where($qb->expr()->eq('tool.course', ':course'))
            ->setParameter('course', $course)
            ->getQuery()
            ->getResult()
        ;
    }

    public function updateTools(TopLink $link): void
    {
        $subQb = $this->createQueryBuilder('tlrt');
        $subQb
            ->select('tool.iid')
            ->innerJoin('tlrt.tool', 'tool', Join::WITH)
            ->where($subQb->expr()->eq('tlrt.link', ':link'))
            ->setParameter('link', $link)
        ;

        $linkTools = $subQb->getQuery()->getArrayResult();
        $toolIds = array_map('intval', array_column($linkTools, 'iid'));

        if ([] === $toolIds) {
            return;
        }

        $qb = $this->_em->createQueryBuilder();
        $qb
            ->update(CTool::class, 'tool')
            ->set('tool.title', ':linkTitle')
            ->where(
                $qb->expr()->in('tool.iid', ':tools')
            )
            ->setParameter('linkTitle', $link->getTitle(), Types::STRING)
            ->setParameter('tools', $toolIds, ArrayParameterType::INTEGER)
            ->getQuery()
            ->execute()
        ;
    }

    public function getMissingCoursesForTool(int $linkId): array
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT c
                FROM Chamilo\CoreBundle\Entity\Course c
                WHERE c.id NOT IN (
                    SELECT IDENTITY(t.course)
                    FROM Chamilo\CourseBundle\Entity\CTool t
                    INNER JOIN Chamilo\PluginBundle\TopLinks\Entity\TopLinkRelTool tlrt WITH IDENTITY(tlrt.tool) = t.iid
                    WHERE IDENTITY(tlrt.link) = :linkId
                )
                ORDER BY c.title ASC'
            )
            ->setParameter('linkId', $linkId, Types::INTEGER)
            ->getResult()
        ;
    }
}
