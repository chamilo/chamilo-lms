<?php

/* For license terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\PluginBundle\TopLinks\Entity\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CourseBundle\Entity\CShortcut;
use Chamilo\PluginBundle\TopLinks\Entity\TopLink;
use Chamilo\PluginBundle\TopLinks\Entity\TopLinkRelShortcut;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;

class TopLinkRelShortcutRepository extends EntityRepository
{
    public function findOneByLinkAndCourse(TopLink $link, Course $course): ?TopLinkRelShortcut
    {
        return $this->createQueryBuilder('tlrs')
            ->innerJoin('tlrs.shortcut', 'shortcut')
            ->innerJoin('shortcut.resourceNode', 'resourceNode')
            ->innerJoin('resourceNode.resourceLinks', 'resourceLink')
            ->where('tlrs.link = :link')
            ->andWhere('resourceLink.course = :course')
            ->setParameter('link', $link)
            ->setParameter('course', $course)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByShortcut(CShortcut $shortcut): ?TopLinkRelShortcut
    {
        return $this->findOneBy(['shortcut' => $shortcut]);
    }

    public function getMissingCoursesForLink(TopLink $link): array
    {
        if (null === $link->getId()) {
            return [];
        }

        return $this->getEntityManager()
            ->createQuery(
                'SELECT c
                FROM Chamilo\CoreBundle\Entity\Course c
                WHERE c.id NOT IN (
                    SELECT IDENTITY(resourceLink.course)
                    FROM Chamilo\PluginBundle\TopLinks\Entity\TopLinkRelShortcut tlrs
                    INNER JOIN tlrs.shortcut shortcut
                    INNER JOIN shortcut.resourceNode resourceNode
                    INNER JOIN resourceNode.resourceLinks resourceLink
                    WHERE tlrs.link = :link
                    AND resourceLink.course IS NOT NULL
                )
                ORDER BY c.title ASC'
            )
            ->setParameter('link', $link)
            ->getResult()
        ;
    }

    public function getShortcutIdsForLink(TopLink $link): array
    {
        $rows = $this->createQueryBuilder('tlrs')
            ->select('shortcut.id')
            ->innerJoin('tlrs.shortcut', 'shortcut')
            ->where('tlrs.link = :link')
            ->setParameter('link', $link)
            ->getQuery()
            ->getArrayResult()
        ;

        return array_map('intval', array_column($rows, 'id'));
    }

    public function updateShortcutTitles(TopLink $link): void
    {
        $shortcutIds = $this->getShortcutIdsForLink($link);

        if ([] === $shortcutIds) {
            return;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->update(CShortcut::class, 'shortcut')
            ->set('shortcut.title', ':title')
            ->where($qb->expr()->in('shortcut.id', ':shortcutIds'))
            ->setParameter('title', $link->getTitle(), Types::STRING)
            ->setParameter('shortcutIds', $shortcutIds, ArrayParameterType::INTEGER)
            ->getQuery()
            ->execute()
        ;
    }
}
