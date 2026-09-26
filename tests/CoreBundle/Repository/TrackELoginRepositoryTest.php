<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Repository\TrackELoginRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use DateTimeZone;

/**
 * The time spent on the platform is the sum of logout_date - login_date over track_e_login.
 * Most users never click "logout", so a connection must be kept up to date while the user is
 * active (as every page view did in 1.11.x); otherwise it stays NULL and counts as zero.
 */
class TrackELoginRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testNewConnectionStartsWithAZeroDurationInsteadOfNull(): void
    {
        $repo = self::getContainer()->get(TrackELoginRepository::class);
        $user = $this->createUser('tel_new_'.uniqid());
        $loginDate = new DateTime('2026-09-01 08:00:00', new DateTimeZone('UTC'));

        $record = $repo->createLoginRecord($user, $loginDate, '127.0.0.1');

        $this->assertEquals($loginDate, $record->getLogoutDate());
    }

    public function testActivityExtendsTheCurrentConnection(): void
    {
        $repo = self::getContainer()->get(TrackELoginRepository::class);
        $user = $this->createUser('tel_touch_'.uniqid());
        $record = $repo->createLoginRecord($user, new DateTime('-10 minutes', new DateTimeZone('UTC')), '127.0.0.1');

        $repo->touchLastConnection($user, '127.0.0.1', 1440);

        $this->assertCount(1, $repo->findBy(['user' => $user->getId()]));
        $this->assertEqualsWithDelta(time(), $record->getLogoutDate()->getTimestamp(), 5);
    }

    public function testActivityAfterALongInactivityOpensANewConnection(): void
    {
        $repo = self::getContainer()->get(TrackELoginRepository::class);
        $user = $this->createUser('tel_idle_'.uniqid());
        $old = $repo->createLoginRecord($user, new DateTime('-3 hours', new DateTimeZone('UTC')), '127.0.0.1');
        $lastActivity = clone $old->getLogoutDate();

        // e.g. back the next day through "remember me": the idle gap must not be counted.
        $repo->touchLastConnection($user, '127.0.0.1', 1440);

        $this->assertCount(2, $repo->findBy(['user' => $user->getId()]));
        $this->assertEquals($lastActivity, $old->getLogoutDate(), 'The old connection keeps its last activity.');
    }

    public function testLogoutClosesTheLatestConnection(): void
    {
        $repo = self::getContainer()->get(TrackELoginRepository::class);
        $user = $this->createUser('tel_logout_'.uniqid());
        $repo->createLoginRecord($user, new DateTime('-2 hours', new DateTimeZone('UTC')), '127.0.0.1');
        $latest = $repo->createLoginRecord($user, new DateTime('-5 minutes', new DateTimeZone('UTC')), '127.0.0.1');
        $logoutDate = new DateTime('now', new DateTimeZone('UTC'));

        $repo->updateLastLoginLogoutDate((int) $user->getId(), $logoutDate);

        /** @var TrackELogin $reloaded */
        $reloaded = $repo->find($latest->getLoginId());
        $this->assertEquals($logoutDate, $reloaded->getLogoutDate());
    }
}
