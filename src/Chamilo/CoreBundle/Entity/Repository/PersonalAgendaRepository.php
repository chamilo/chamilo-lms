<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Chamilo\CoreBundle\Entity\AgendaEventSubscription;
use Chamilo\CoreBundle\Entity\PersonalAgenda;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;

class PersonalAgendaRepository extends EntityRepository
{
    /**
     * @return array<int, PersonalAgenda>
     */
    public function getEventsForInvitee(User $user, ?\DateTime $startDate, ?\DateTime $endDate): array
    {
        $qb = $this->createQueryBuilder('pa');
        $qb
            ->innerJoin('pa.invitation', 'i')
            ->innerJoin('i.invitees', 'iu')
            ->where(
                $qb->expr()->eq('iu.user', ':user')
            )
        ;

        if (api_get_configuration_value('agenda_event_subscriptions')) {
            $qb
                ->andWhere(
                    $qb->expr()->not(
                        $qb->expr()->isInstanceOf('i', AgendaEventSubscription::class)
                    )
                )
            ;
        }

        $params = [
            'user' => $user,
        ];

        if ($startDate) {
            $qb->andWhere(
                $qb->expr()->gte('pa.date', ':start_date')
            );

            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $qb->andWhere(
                $qb->expr()->lte('pa.enddate', ':end_date')
            );

            $params['end_date'] = $endDate;
        }

        $qb->setParameters($params);

        return $qb->getQuery()->getResult();
    }

    public function findOneByIdAndInvitee(int $eventId, User $user): ?PersonalAgenda
    {
        $qb = $this->createQueryBuilder('pa');
        $qb
            ->innerJoin('pa.invitation', 'i')
            ->innerJoin('i.invitees', 'iu')
            ->where(
                $qb->expr()->eq('pa.id', ':id')
            )
            ->andWhere(
                $qb->expr()->eq('iu.user', ':user')
            )
            ->setParameters(['id' => $eventId, 'user' => $user])
        ;

        try {
            return $qb->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
}
