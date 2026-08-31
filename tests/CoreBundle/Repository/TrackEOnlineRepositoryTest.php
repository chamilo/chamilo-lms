<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Repository\TrackEOnlineRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * track_e_online holds current presence, not login history: a unique index on
 * (login_user_id, access_url_id) guarantees at most one row per pair, and
 * TrackEOnlineRepository::touchOnlineSession() upserts into that single row instead of the
 * previous fetch-everything-and-delete-duplicates behaviour. These tests pin that a heartbeat
 * never creates more than one row per user/portal, and that course/session context set by an
 * earlier call survives a later context-less heartbeat.
 */
class TrackEOnlineRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testTouchOnlineSessionCreatesExactlyOneRow(): void
    {
        /** @var TrackEOnlineRepository $repo */
        $repo = self::getContainer()->get(TrackEOnlineRepository::class);
        $accessUrl = $this->getAccessUrl();
        $user = $this->createUser('teo_single_'.uniqid());

        $repo->touchOnlineSession($user, '127.0.0.1', null, null, (int) $accessUrl->getId());

        $rows = $repo->findBy(['loginUserId' => $user->getId(), 'accessUrlId' => $accessUrl->getId()]);
        $this->assertCount(1, $rows);
    }

    public function testTouchOnlineSessionUpdatesTheSameRowInsteadOfCreatingDuplicates(): void
    {
        /** @var TrackEOnlineRepository $repo */
        $repo = self::getContainer()->get(TrackEOnlineRepository::class);
        $accessUrl = $this->getAccessUrl();
        $user = $this->createUser('teo_repeat_'.uniqid());

        $repo->touchOnlineSession($user, '127.0.0.1', null, null, (int) $accessUrl->getId());
        $firstRow = $repo->findOneBy(['loginUserId' => $user->getId(), 'accessUrlId' => $accessUrl->getId()]);

        $repo->touchOnlineSession($user, '127.0.0.1', null, null, (int) $accessUrl->getId());

        $rows = $repo->findBy(['loginUserId' => $user->getId(), 'accessUrlId' => $accessUrl->getId()]);
        $this->assertCount(1, $rows);
        $this->assertSame($firstRow->getLoginId(), $rows[0]->getLoginId());
    }

    public function testTouchOnlineSessionPreservesLastKnownContextWhenNotProvided(): void
    {
        /** @var TrackEOnlineRepository $repo */
        $repo = self::getContainer()->get(TrackEOnlineRepository::class);
        $accessUrl = $this->getAccessUrl();
        $user = $this->createUser('teo_context_'.uniqid());

        // A page load with real course/session context.
        $repo->touchOnlineSession($user, '127.0.0.1', 5, 7, (int) $accessUrl->getId());

        // A later global SPA heartbeat has no course/session context (null, null) and must not
        // reset the ones already recorded.
        $repo->touchOnlineSession($user, '127.0.0.1', null, null, (int) $accessUrl->getId());

        $row = $repo->findOneBy(['loginUserId' => $user->getId(), 'accessUrlId' => $accessUrl->getId()]);
        $this->assertSame(5, $row->getCId());
        $this->assertSame(7, $row->getSessionId());
    }

    public function testTouchOnlineSessionOverwritesContextWhenExplicitlyProvided(): void
    {
        /** @var TrackEOnlineRepository $repo */
        $repo = self::getContainer()->get(TrackEOnlineRepository::class);
        $accessUrl = $this->getAccessUrl();
        $user = $this->createUser('teo_overwrite_'.uniqid());

        $repo->touchOnlineSession($user, '127.0.0.1', 5, 7, (int) $accessUrl->getId());
        $repo->touchOnlineSession($user, '127.0.0.1', 9, 11, (int) $accessUrl->getId());

        $row = $repo->findOneBy(['loginUserId' => $user->getId(), 'accessUrlId' => $accessUrl->getId()]);
        $this->assertSame(9, $row->getCId());
        $this->assertSame(11, $row->getSessionId());
    }
}
