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
use Chamilo\CourseBundle\Entity\CStudentPublicationAssignment;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

final class CStudentPublicationRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CStudentPublication::class);
    }

    public function getStudentAssignments(
        CStudentPublication $publication,
        Course $course,
        Session $session = null,
        CGroup $group = null
    ): QueryBuilder {
        $qb = $this->getResourcesByCourse($course, $session, $group, $publication->getResourceNode());

        $qb->andWhere($qb->expr()->in('resource.active', [1, 0]));
        $qb->andWhere($qb->expr()->eq('resource.publicationParent', $publication));

        return $qb;
    }

    /**
     * Find all the works registered by a teacher.
     */
    public function findWorksByTeacher(User $user, Course $course, Session $session = null, int $groupId = 0): array
    {
        $qb = $this->createQueryBuilder('w');

        return $qb
            ->leftJoin(
                CStudentPublicationAssignment::class,
                'a',
                Join::WITH,
                'a.publicationId = w.iid AND a.cId = w.cId'
            )
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('w.cId', ':course'),
                    $qb->expr()->eq('w.session', ':session'),
                    $qb->expr()->in('w.active', [0, 1]),
                    $qb->expr()->eq('w.parentId', 0),
                    $qb->expr()->eq('w.postGroupId', ':group'),
                    $qb->expr()->eq('w.userId', ':user')
                )
            )

            ->orderBy('w.sentDate', Criteria::ASC)
            ->setParameters([
                'course' => $course->getId(),
                'session' => $session,
                'group' => $groupId,
                'user' => $user->getId(),
            ])
            ->getQuery()
            ->getResult()
        ;
    }
}
