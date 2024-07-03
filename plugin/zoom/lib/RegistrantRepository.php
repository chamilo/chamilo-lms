<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Chamilo\UserBundle\Entity\User;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityRepository;

/**
 * Class RegistrantEntityRepository.
 */
class RegistrantRepository extends EntityRepository
{
    /**
     * Returns the upcoming meeting registrations for the given user.
     *
     * @param User $user
     *
     * @return array|Registrant[]
     */
    public function meetingsComingSoonRegistrationsForUser($user)
    {
        $start = new DateTime();
        $end = new DateTime();
        $end->add(new DateInterval('P7D'));
        $meetings = $this->getEntityManager()->getRepository(Meeting::class)->periodMeetings($start, $end);

        return $this->findBy(['meeting' => $meetings, 'user' => $user]);
    }

    public function findByMeetingPaginated(Meeting $meeting, int $from, int $limit, string $column, string $direction)
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->join('r.user', 'u')
            ->leftJoin('r.signature', 's')
            ->where('r.meeting = :meeting')
            ->setFirstResult($from)
            ->setMaxResults($limit)
            ->orderBy($column, $direction)
        ;

        $queryBuilder->setParameter('meeting', $meeting);

        return $queryBuilder->getQuery()->getResult();
    }
}
