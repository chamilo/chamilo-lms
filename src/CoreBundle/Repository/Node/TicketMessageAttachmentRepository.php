<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\TicketMessageAttachment;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Doctrine\Persistence\ManagerRegistry;

class TicketMessageAttachmentRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketMessageAttachment::class);
    }
}
