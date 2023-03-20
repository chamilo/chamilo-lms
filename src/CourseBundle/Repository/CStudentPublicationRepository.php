<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

final class CStudentPublicationRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CStudentPublication::class);
    }

    public function findAllByCourse(
        Course $course,
        Session $session = null,
        ?string $title = null,
        ?int $active = null,
        ?string $fileType = null
    ): QueryBuilder {
        $qb = $this->getResourcesByCourse($course, $session);

        $this->addTitleQueryBuilder($title, $qb);
        $this->addActiveQueryBuilder($active, $qb);
        $this->addFileTypeQueryBuilder($fileType, $qb);

        return $qb;
    }

    public function getStudentAssignments(
        CStudentPublication $publication,
        Course $course,
        ?Session $session = null,
        ?CGroup $group = null,
        ?User $user = null
    ): QueryBuilder {
        $qb = $this->getResourcesByCourse($course, $session, $group);

        $this->addNotDeletedPublicationQueryBuilder($qb);
        $qb
            ->andWhere('resource.publicationParent =:publicationParent')
            ->setParameter('publicationParent', $publication)
        ;

        return $qb;
    }

    public function getStudentPublicationByUser(User $user, Course $course, Session $session = null): array
    {
        $qb = $this->findAllByCourse($course, $session);
        /** @var CStudentPublication[] $works */
        $works = $qb->getQuery()->getResult();
        $list = [];
        foreach ($works as $work) {
            $qb = $this->getStudentAssignments($work, $course, $session, null, $user);
            $results = $qb->getQuery()->getResult();
            $list[$work->getIid()]['work'] = $work;
            $list[$work->getIid()]['results'] = $results;
        }

        return $list;
    }

    public function countUserPublications(User $user, Course $course, Session $session = null, CGroup $group = null): int
    {
        $qb = $this->getResourcesByCourseLinkedToUser($user, $course, $session);
        $qb->andWhere('resource.publicationParent IS NOT NULL');

        return $this->getCount($qb);
    }

    public function countCoursePublications(Course $course, Session $session = null, CGroup $group = null): int
    {
        $qb = $this->getResourcesByCourse($course, $session, $group);

        $this->addNotDeletedPublicationQueryBuilder($qb);

        return $this->getCount($qb);
    }

    /**
     * Find all the works registered by a teacher.
     */
    public function findWorksByTeacher(User $user, Course $course, Session $session = null)
    {
        $qb = $this->getResourcesByCourseLinkedToUser($user, $course, $session);
        $qb->andWhere('resource.publicationParent IS NOT NULL');

        return $qb
            ->orderBy('resource.sentDate', Criteria::ASC)
            ->getQuery()
            ->getResult()
        ;
    }

    private function addActiveQueryBuilder(?int $active = null, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);

        if (null !== $active) {
            $qb
                ->andWhere('resource.active = :active')
                ->setParameter('active', $active)
            ;
        }

        return $qb;
    }

    private function addNotDeletedPublicationQueryBuilder(QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);
        $qb
            ->andWhere('resource.active <> 2')
        ;

        return $qb;
    }

    private function addFileTypeQueryBuilder(?string $fileType, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);
        if (null === $fileType) {
            return $qb;
        }

        $qb
            ->andWhere('resource.filetype = :filetype')
            ->setParameter('filetype', $fileType)
        ;

        return $qb;
    }
}
