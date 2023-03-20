<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
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
}
