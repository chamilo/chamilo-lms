<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Event;

use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\UserDeletedEvent;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use UserManager;

/**
 * StudentFollowUp deletes its sfu_post rows from a USER_DELETED listener, and those
 * rows hold a RESTRICT foreign key on user. Two things make that work: the event
 * reaches listeners while the user row is still there, and it tells them whether the
 * deletion is hard -- a soft delete keeps the user restorable, so its posts must stay.
 */
class UserDeletedEventTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private array $seen = [];

    public function testHardDeleteDispatchesEventWhileUserStillExists(): void
    {
        $userId = $this->createUser('user_hard_deleted')->getId();
        $repo = $this->bootLegacyContext();

        $this->recordPreEvents($repo);

        $this->assertTrue(UserManager::delete_user($userId, true));

        $seen = $this->seen;
        $this->assertCount(1, $seen);
        $this->assertSame($userId, $seen[0]['id']);
        $this->assertTrue($seen[0]['hard'], 'A destroying delete must be announced as hard.');
        $this->assertTrue(
            $seen[0]['readable'],
            'The user must still be readable when the event fires, or listeners cannot clean up rows keyed on it.'
        );
        $this->assertNull($repo->find($userId));
    }

    public function testSoftDeleteIsNotReportedAsHard(): void
    {
        $userId = $this->createUser('user_soft_deleted')->getId();
        $repo = $this->bootLegacyContext();

        $this->recordPreEvents($repo);

        $this->assertTrue(UserManager::delete_user($userId));

        $seen = $this->seen;
        $this->assertCount(1, $seen);
        $this->assertFalse(
            $seen[0]['hard'],
            'A soft delete must not look hard, or listeners would destroy data that is still restorable.'
        );
        $this->assertNotNull($repo->find($userId), 'A soft delete keeps the user row.');
    }

    /**
     * UserManager::delete_user() is legacy: it reads the current user through the
     * session and queries through the legacy Database wrapper, both of which a
     * request normally sets up in LegacyListener.
     */
    private function bootLegacyContext(): UserRepository
    {
        $request = new Request();
        $request->setSession(new HttpSession(new MockArraySessionStorage()));
        self::getContainer()->get('request_stack')->push($request);
        Container::setContainer(self::getContainer());
        Database::setManager($this->getEntityManager());

        return self::getContainer()->get(UserRepository::class);
    }

    private function recordPreEvents(UserRepository $repo): void
    {
        self::getContainer()->get('event_dispatcher')->addListener(
            Events::USER_DELETED,
            function (UserDeletedEvent $event) use ($repo): void {
                if (AbstractEvent::TYPE_PRE !== $event->getType()) {
                    return;
                }

                $userId = $event->getUser()?->getId();

                $this->seen[] = [
                    'id' => $userId,
                    'hard' => $event->isHardDelete(),
                    'readable' => null !== $repo->find($userId),
                ];
            }
        );
    }
}
