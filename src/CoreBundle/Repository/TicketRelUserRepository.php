<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Ticket;
use Chamilo\CoreBundle\Entity\TicketRelUser;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TicketRelUserRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketRelUser::class);
    }

    public function subscribeUserToTicket(User $user, Ticket $ticket): void
    {
        $em = $this->getEntityManager();

        $existingSubscription = $this->findOneBy(['user' => $user, 'ticket' => $ticket]);

        if (!$existingSubscription) {
            $subscription = new TicketRelUser($user, $ticket, true);
            $em->persist($subscription);
            $em->flush();
        }
    }

    public function unsubscribeUserFromTicket(User $user, Ticket $ticket): void
    {
        $em = $this->getEntityManager();

        $subscription = $this->findOneBy(['user' => $user, 'ticket' => $ticket]);

        if ($subscription) {
            $em->remove($subscription);
            $em->flush();
        }
    }

    public function isUserSubscribedToTicket(User $user, Ticket $ticket): bool
    {
        return (bool) $this->findOneBy(['user' => $user, 'ticket' => $ticket]);
    }

    public function getTicketFollowers(Ticket $ticket): array
    {
        return $this->findBy(['ticket' => $ticket]);
    }
}
