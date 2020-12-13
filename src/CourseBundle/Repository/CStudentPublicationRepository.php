<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class CStudentPublicationRepository.
 */
final class CStudentPublicationRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CStudentPublication::class);
    }

    /**
     * Find all the works registered by a teacher.
     */
    public function findWorksByTeacher(User $user, Course $course, Session $session = null, $groupId = 0): array
    {
        $qb = $this->createQueryBuilder('w');

        return $qb
            ->leftJoin(
                'ChamiloCourseBundle:CStudentPublicationAssignment',
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
            ->orderBy('w.sentDate', 'ASC')
            ->setParameters([
                'course' => $course->getId(),
                'session' => $session,
                'group' => $groupId,
                'user' => $user->getId(),
            ])
            ->getQuery()
            ->getResult();
    }
}
