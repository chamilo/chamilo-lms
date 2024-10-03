<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\TrackEOnline;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

class TrackEOnlineRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly SettingsManager $settingsManager,
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {
        parent::__construct($registry, TrackEOnline::class);
    }

    public function isUserOnline(int $userId): bool
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        $timeLimit = $this->settingsManager->getSetting('display.time_limit_whosonline');

        $onlineTime = new DateTime();
        $onlineTime->modify("-{$timeLimit} minutes");

        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.loginUserId)')
            ->where('t.loginUserId = :userId')
            ->andWhere('t.accessUrlId = :accessUrlId')
            ->andWhere('t.loginDate >= :limitDate')
            ->setParameter('userId', $userId)
            ->setParameter('accessUrlId', $accessUrl->getId())
            ->setParameter('limitDate', $onlineTime)
            ->setMaxResults(1)
        ;

        try {
            $count = $qb->getQuery()->getSingleScalarResult();

            return $count > 0;
        } catch (NonUniqueResultException|NoResultException) {
            return false;
        }
    }

    public function createOnlineSession(User $user, string $userIp, int $cId = 0, int $sessionId = 0, int $accessUrlId = 1): void
    {
        $trackEOnline = new TrackEOnline();
        $trackEOnline->setLoginUserId($user->getId());
        $trackEOnline->setLoginDate(new DateTime());
        $trackEOnline->setUserIp($userIp);
        $trackEOnline->setCId($cId);
        $trackEOnline->setSessionId($sessionId);
        $trackEOnline->setAccessUrlId($accessUrlId);

        $this->_em->persist($trackEOnline);
        $this->_em->flush();
    }

    public function removeOnlineSessionsByUser(int $userId): void
    {
        $sessions = $this->findBy(['loginUserId' => $userId]);

        foreach ($sessions as $session) {
            $this->_em->remove($session);
        }

        $this->_em->flush();
    }
}
