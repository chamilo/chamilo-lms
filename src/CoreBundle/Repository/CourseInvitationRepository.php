<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseInvitation;
use Chamilo\CoreBundle\Entity\Session;
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
     * Invitations sent from a given course's Users tool page, newest first,
     * restricted to the requested context: the invitations of that session
     * when a session is given, the plain course invitations otherwise.
     * Whole-session invitations keep the course they were sent from as an
     * informational reference, so the course alone does not identify the
     * session they belong to.
     *
     * @return list<CourseInvitation>
     */
    public function findAllForContext(Course $course, ?Session $session): array
    {
        $qb = $this->createQueryBuilder('invitation')
            ->andWhere('invitation.course = :course')
            ->setParameter('course', (int) $course->getId())
            ->orderBy('invitation.createdAt', 'DESC')
        ;

        if ($session instanceof Session) {
            $qb->andWhere('invitation.session = :session')
                ->setParameter('session', (int) $session->getId())
            ;
        } else {
            $qb->andWhere('invitation.session IS NULL');
        }

        return $qb->getQuery()->getResult();
    }
}
