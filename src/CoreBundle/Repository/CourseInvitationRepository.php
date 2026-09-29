<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseInvitation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CourseInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseInvitation::class);
    }

    public function save(CourseInvitation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * All invitations sent from a given course's Users tool page, newest
     * first — this includes whole-session invitations sent from that page
     * (course is stored as the informational "sent from" reference in that
     * case), not just plain course invitations.
     *
     * @return list<CourseInvitation>
     */
    public function findAllForCourse(Course $course): array
    {
        return $this->createQueryBuilder('invitation')
            ->andWhere('invitation.course = :course')
            ->setParameter('course', $course->getId())
            ->orderBy('invitation.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
