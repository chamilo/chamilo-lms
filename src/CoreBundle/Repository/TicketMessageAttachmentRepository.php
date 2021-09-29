<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\TicketMessageAttachment;
use Doctrine\Persistence\ManagerRegistry;

class TicketMessageAttachmentRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketMessageAttachment::class);
    }
}
