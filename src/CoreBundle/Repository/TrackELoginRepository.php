<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
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

        $this->getEntityManager()->persist($loginRecord);
        $this->getEntityManager()->flush();

        return $loginRecord;
    }

    public function updateLastLoginLogoutDate(int $userId, DateTime $logoutDate): void
    {
        $lastLoginRecord = $this->findOneBy(
            ['user' => $userId, 'logoutDate' => null],
            ['loginDate' => 'DESC']
        );

        if (null !== $lastLoginRecord) {
            $lastLoginRecord->setLogoutDate($logoutDate);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Seconds the user spent connected, summed over their connections. Like the legacy
     * Tracking::get_time_spent_on_the_platform() 'custom' filter, a connection counts when it
     * started at or after $from and ended at or before $to; either bound may be omitted.
     * Dates are compared as stored, in UTC.
     */
    public function getTotalConnectionTime(int $userId, ?DateTime $from = null, ?DateTime $to = null): int
    {
        $sql = 'SELECT COALESCE(SUM(TIMESTAMPDIFF(SECOND, login_date, logout_date)), 0)
                FROM track_e_login
                WHERE login_user_id = :userId';
        $params = ['userId' => $userId];
        $types = ['userId' => Types::INTEGER];

        if (null !== $from) {
            $sql .= ' AND login_date >= :from';
            $params['from'] = $from;
            $types['from'] = Types::DATETIME_MUTABLE;
        }

        if (null !== $to) {
            $sql .= ' AND logout_date <= :to';
            $params['to'] = $to;
            $types['to'] = Types::DATETIME_MUTABLE;
        }

        return (int) $this->getEntityManager()->getConnection()->fetchOne($sql, $params, $types);
    }
}
