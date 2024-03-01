<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\TrackELoginRecord;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class TrackELoginRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackELoginRecord::class);
    }

    public function addTrackLogin(string $username, string $userIp, bool $success): void
    {
        $trackELoginRecord = new TrackELoginRecord();
        $trackELoginRecord
            ->setUsername($username)
            ->setLoginDate(new DateTime())
            ->setUserIp($userIp)
            ->setSuccess($success);

        $this->_em->persist($trackELoginRecord);
        $this->_em->flush();
    }
}
