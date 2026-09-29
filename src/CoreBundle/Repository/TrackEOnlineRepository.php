<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\TrackEOnline;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use DateTimeZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
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

        $onlineTime = new DateTime('now', new DateTimeZone('UTC'));
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

    public function createOnlineSession(
        User $user,
        string $userIp,
        int $cId = 0,
        int $sessionId = 0,
        ?int $accessUrlId = null,
    ): void {
        $this->touchOnlineSession($user, $userIp, $cId, $sessionId, $accessUrlId);
    }

    public function touchOnlineSession(
        User $user,
        string $userIp,
        ?int $cId = null,
        ?int $sessionId = null,
        ?int $accessUrlId = null,
    ): void {
        $effectiveAccessUrlId = $accessUrlId ?? (int) $this->accessUrlHelper->getCurrent()->getId();

        // track_e_online stores current presence, not login history: the unique index on
        // (login_user_id, access_url_id) guarantees at most one row per pair, so a single
        // lookup is enough — no need to fetch every row and delete the older duplicates.
        $trackEOnline = $this->findOneBy([
            'loginUserId' => $user->getId(),
            'accessUrlId' => $effectiveAccessUrlId,
        ]);

        if (!$trackEOnline instanceof TrackEOnline) {
            $trackEOnline = new TrackEOnline();
            $trackEOnline->setLoginUserId($user->getId());
            $trackEOnline->setCId($cId ?? 0);
            $trackEOnline->setSessionId($sessionId ?? 0);
        } else {
            // A global SPA heartbeat has no reliable course/session context.
            // Keep the last known context unless the caller explicitly has one.
            if (null !== $cId) {
                $trackEOnline->setCId($cId);
            }

            if (null !== $sessionId) {
                $trackEOnline->setSessionId($sessionId);
            }
        }

        $trackEOnline->setLoginDate(new DateTime('now', new DateTimeZone('UTC')));
        $trackEOnline->setUserIp($userIp);
        $trackEOnline->setAccessUrlId($effectiveAccessUrlId);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($trackEOnline);

        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            // Two near-simultaneous FIRST heartbeats for the same user/portal both found no
            // existing row and both tried to create one; the other request already marked the
            // user online an instant ago, so there is nothing left to do here.
        }
    }

    public function removeOnlineSessionsByUser(int $userId): void
    {
        $sessions = $this->findBy(['loginUserId' => $userId]);

        foreach ($sessions as $session) {
            $this->getEntityManager()->remove($session);
        }

        $this->getEntityManager()->flush();
    }

    public function hasOnlineSessionForUser(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        return $this->isUserOnline($userId);
    }
}
