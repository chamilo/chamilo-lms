<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use DateTimeZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class TrackECourseAccessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackECourseAccess::class);
    }

    /**
     * Get the last registered access by an user.
     */
    public function getLastAccessByUser(?User $user = null): ?TrackECourseAccess
    {
        if (null === $user) {
            return null;
        }

        $lastAccess = $this->findBy(
            [
                'userId' => $user->getId(),
            ],
            [
                'courseAccessId' => 'DESC',
            ],
            1
        );

        if (!empty($lastAccess)) {
            return $lastAccess[0];
        }

        return null;
    }

    /**
     * Find existing access for a user.
     */
    public function findExistingAccess(User $user, int $courseId, int $sessionId)
    {
        return $this->findOneBy(['user' => $user, 'cId' => $courseId, 'sessionId' => $sessionId]);
    }

    /**
     * Update access record.
     */
    public function updateAccess(TrackECourseAccess $access): void
    {
        $now = new DateTime();
        if (!$access->getLogoutCourseDate() || $now->getTimestamp() - $access->getLogoutCourseDate()->getTimestamp() > 300) {
            $access->setLogoutCourseDate($now);
            $access->setCounter($access->getCounter() + 1);
            $this->_em->flush();
        }
    }

    /**
     * Record a new access entry.
     */
    public function recordAccess(User $user, int $courseId, int $sessionId, string $ip): void
    {
        $access = new TrackECourseAccess();
        $access->setUser($user);
        $access->setCId($courseId);
        $access->setSessionId($sessionId);
        $access->setUserIp($ip);
        $access->setLoginCourseDate(new DateTime());
        $access->setCounter(1);
        $this->_em->persist($access);
        $this->_em->flush();
    }

    /**
     * Log out user access to a course.
     */
    public function logoutAccess(User $user, int $courseId, int $sessionId, string $ip): void
    {
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $sessionLifetime = 3600;
        $limitTime = (new DateTime())->setTimestamp(time() - $sessionLifetime);

        $access = $this->createQueryBuilder('a')
            ->where('a.user = :user AND a.cId = :courseId AND a.sessionId = :sessionId')
            ->andWhere('a.loginCourseDate > :limitTime')
            ->setParameters([
                'user' => $user,
                'courseId' => $courseId,
                'sessionId' => $sessionId,
                'limitTime' => $limitTime,
            ])
            ->orderBy('a.loginCourseDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if ($access) {
            $access->setLogoutCourseDate($now);
            $access->setCounter($access->getCounter() + 1);
            $this->_em->flush();
        } else {
            // No access found or existing access is outside the session lifetime
            // Insert new access record
            $newAccess = new TrackECourseAccess();
            $newAccess->setUser($user);
            $newAccess->setCId($courseId);
            $newAccess->setSessionId($sessionId);
            $newAccess->setUserIp($ip);
            $newAccess->setLoginCourseDate($now);
            $newAccess->setLogoutCourseDate($now);
            $newAccess->setCounter(1);
            $this->_em->persist($newAccess);
            $this->_em->flush();
        }
    }
}
