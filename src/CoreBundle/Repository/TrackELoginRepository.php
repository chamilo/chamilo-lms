<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

class TrackELoginRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackELogin::class);
    }

    public function createLoginRecord(User $user, DateTime $loginDate, string $userIp): TrackELogin
    {
        $loginRecord = new TrackELogin();
        $loginRecord->setUser($user);
        $loginRecord->setLoginDate($loginDate);
        $loginRecord->setUserIp($userIp);

        $this->_em->persist($loginRecord);
        $this->_em->flush();

        return $loginRecord;
    }

    public function updateLastLoginLogoutDate(int $userId, DateTime $logoutDate): void
    {
        $lastLoginRecord = $this->findOneBy(
            ['user' => $userId, 'logoutDate' => null],
            ['loginDate' => 'DESC']
        );

        if ($lastLoginRecord !== null) {
            $lastLoginRecord->setLogoutDate($logoutDate);
            $this->_em->flush();
        }
    }
}
