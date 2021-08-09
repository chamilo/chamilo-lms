<?php

/* For license terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\TopLinks\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\PluginBundle\Entity\TopLinks\TopLink;
use Chamilo\PluginBundle\Entity\TopLinks\TopLinkRelTool;
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

    public function updateTools(TopLink $link)
    {
        $subQb = $this->createQueryBuilder('tlrt');
        $subQb
            ->select('tool.iid')
            ->innerJoin('tlrt.tool', 'tool', Join::WITH)
            ->where($subQb->expr()->eq('tlrt.link', ':link'))
            ->setParameter('link', $link);

        $linkTools = $subQb->getQuery()->getArrayResult();

        $qb = $this->_em->createQueryBuilder();
        $qb
            ->update(CTool::class, 'tool')
            ->set('tool.name', ':link_name')
            ->set('tool.target', ':link_target')
            ->where(
                $qb->expr()->in('tool.iid', ':tools')
            )
            ->setParameter('link_name', $link->getTitle())
            ->setParameter('link_target', $link->getTarget())
            ->setParameter('tools', array_column($linkTools, 'iid'))
            ->getQuery()
            ->execute();
    }

    public function getMissingCoursesForTool(int $linkId)
    {
        $qb = $this->_em->createQueryBuilder();

        $subQb = $this->_em->createQueryBuilder();
        $subQb
            ->select('t.cId')
            ->from(CTool::class, 't')
            ->innerJoin(TopLinkRelTool::class, 'tlrt', Join::WITH, $subQb->expr()->eq('t.iid', 'tlrt.tool'))
            ->where($subQb->expr()->eq('tlrt.link', ':link_id'));

        return $qb
            ->select('c')
            ->from(Course::class, 'c')
            ->where($qb->expr()->notIn('c.id', $subQb->getDQL()))
            ->setParameter('link_id', $linkId)
            ->getQuery()
            ->getResult();
    }
}
