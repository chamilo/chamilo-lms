<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Ticket;
use Doctrine\Persistence\ManagerRegistry;

class TicketRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }
}
