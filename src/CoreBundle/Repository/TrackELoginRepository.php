<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
        $lastLoginId = $this->createQueryBuilder('t')
            ->select('t.loginId')
            ->where('t.user = :userId')
            ->andWhere('t.logoutDate IS NULL')
            ->setParameter('userId', $userId)
            ->orderBy('t.loginDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        if ($lastLoginId) {
            $qb = $this->createQueryBuilder('t')
                ->update()
                ->set('t.logoutDate', ':logoutDate')
                ->where('t.loginId = :loginId')
                ->setParameter('loginId', $lastLoginId)
                ->setParameter('logoutDate', $logoutDate)
            ;

            $qb->getQuery()->execute();
        }
    }
}
