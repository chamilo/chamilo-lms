<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Datetime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

final class CSurveyInvitationRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CSurveyInvitation::class);
    }

    /**
     * @return CSurveyInvitation[]
     */
    public function getUserPendingInvitations(User $user)
    {
        $qb = $this->createQueryBuilder('i');
        $qb
            ->select('i')
            ->innerJoin('i.user', 'u')
            ->innerJoin('i.survey', 's')
            ->andWhere('i.user = :u')
            ->andWhere('s.availFrom <= :now AND s.availTill >= :now')
            ->andWhere('s.answered = 0')
            ->setParameters([
                'now' => new Datetime(),
                'u' => $user,
            ])
            ->orderBy('s.availTill', Criteria::ASC)
        ;

        return $qb->getQuery()->getResult();
    }

    public function getAnsweredInvitations(CSurvey $survey, Course $course, ?Session $session = null): array
    {
        $qb = $this->createQueryBuilder('i')
            ->select('i')
            ->innerJoin('i.user', 'u')
            ->innerJoin('i.survey', 's')
            ->where('s = :survey')
            ->andWhere('i.course = :course')
            ->andWhere('i.answered = 1')
            ->setParameter('survey', $survey)
            ->setParameter('course', $course);

        if ($session) {
            $qb->andWhere('i.session = :session')
                ->setParameter('session', $session);
        } else {
            $qb->andWhere('i.session IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    public function hasUserAnswered(
        CSurvey $survey,
        Course $course,
        User $user,
        ?Session $session = null
    ): bool {
        $qb = $this->createQueryBuilder('i')
            ->select('i')
            ->innerJoin('i.survey', 's')
            ->where('s = :survey')
            ->andWhere('i.user = :user')
            ->andWhere('i.course = :course')
            ->andWhere('i.answered = 1')
            ->setParameters([
                'survey' => $survey,
                'user' => $user,
                'course' => $course,
            ]);

        if ($session) {
            $qb->andWhere('i.session = :session')
                ->setParameter('session', $session);
        } else {
            $qb->andWhere('i.session IS NULL');
        }

        return (bool) $qb->getQuery()->getOneOrNullResult();
    }
}
