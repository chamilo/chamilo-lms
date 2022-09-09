<?php

declare(strict_types = 1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\TrackELoginRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class TrackELoginRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackELoginRecord::class);
    }

    public function create(TrackELoginRecord $trackELoginRecord): void
    {
        $this->getEntityManager()->persist($trackELoginRecord);
        $this->getEntityManager()->flush();
    }
}
