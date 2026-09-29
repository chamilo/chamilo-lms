<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Event;

use Chamilo\CoreBundle\Entity\SessionCategory;
use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\SessionDeletedEvent;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use SessionManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Plugins clean up what they attached to a session (BBB recordings on the remote
 * server, EmbedRegistry rows whose foreign key would block the deletion) from a
 * SESSION_DELETED listener. What they depend on is that the event reaches them
 * while the session row is still readable: dispatching it after the removal, or
 * on a path that forgets to dispatch, leaves those rows behind with no error.
 */
class SessionDeletedEventTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testApiDeleteDispatchesEventWhileSessionStillExists(): void
    {
        $sessionId = $this->createSession('session to delete')->getId();

        $client = $this->createClientWithCredentials($this->getUserToken());

        $repo = self::getContainer()->get(SessionRepository::class);
        $seen = [];

        self::getContainer()->get('event_dispatcher')->addListener(
            Events::SESSION_DELETED,
            function (SessionDeletedEvent $event) use (&$seen, $repo): void {
                $seen[] = [
                    'id' => $event->getSessionId(),
                    'type' => $event->getType(),
                    'readable' => null !== $repo->find($event->getSessionId()),
                ];
            }
        );

        $client->request('DELETE', '/api/sessions/'.$sessionId);

        $this->assertResponseStatusCodeSame(204);
        $this->assertCount(1, $seen, 'Deleting a session must notify SESSION_DELETED listeners exactly once.');
        $this->assertSame($sessionId, $seen[0]['id']);
        $this->assertSame(AbstractEvent::TYPE_PRE, $seen[0]['type']);
        $this->assertTrue(
            $seen[0]['readable'],
            'The session must still be readable when the event fires, or listeners cannot find the rows to clean up.'
        );
        $this->assertNull($repo->find($sessionId), 'The session must be gone once the request completes.');
    }

    public function testAdminSessionListDeleteDispatchesEvent(): void
    {
        $sessionId = $this->createSession('session deleted from the admin list')->getId();

        $client = $this->getClientWithGuiCredentials('admin', 'admin');
        // The login above already consumed one request; without this the kernel is
        // rebooted before the next one and the listener registered below is lost.
        $client->getKernelBrowser()->disableReboot();

        $repo = self::getContainer()->get(SessionRepository::class);
        $seen = [];

        self::getContainer()->get('event_dispatcher')->addListener(
            Events::SESSION_DELETED,
            function (SessionDeletedEvent $event) use (&$seen, $repo): void {
                $seen[] = [
                    'id' => $event->getSessionId(),
                    'readable' => null !== $repo->find($event->getSessionId()),
                ];
            }
        );

        $client->request(
            'POST',
            '/admin/session-list-data-action',
            [
                'extra' => [
                    'parameters' => [
                        'action' => 'delete',
                        'sessionIds' => [$sessionId],
                    ],
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $seen, 'The admin session list is the path the SPA uses; it must notify listeners too.');
        $this->assertSame($sessionId, $seen[0]['id']);
        $this->assertTrue($seen[0]['readable']);
        $this->assertNull($repo->find($sessionId));
    }

    /**
     * Deleting a session category with "delete sessions too" removes every session
     * in it through SessionManager::delete(), so each one must reach listeners.
     * Nothing dispatches on the category itself -- adding a dispatch there would
     * fire the event twice per session.
     */
    public function testSessionCategoryDeleteDispatchesEventForEachSession(): void
    {
        $em = $this->getEntityManager();
        $session = $this->createSession('session inside a category');

        $category = (new SessionCategory())
            ->setTitle('category to delete')
            ->setUrl($this->getAccessUrl())
        ;
        $em->persist($category);
        $session->setCategory($category);
        $em->flush();

        $sessionId = $session->getId();
        $categoryId = $category->getId();

        // The legacy page that reaches this code is served outside the kernel, so
        // the call is made directly -- it only needs a request with a session for
        // api_get_user_id(), and $fromWs to skip the interactive permission check.
        $request = new Request();
        $request->setSession(new HttpSession(new MockArraySessionStorage()));
        self::getContainer()->get('request_stack')->push($request);
        Container::setContainer(self::getContainer());
        Database::setManager($em);

        $seen = [];

        self::getContainer()->get('event_dispatcher')->addListener(
            Events::SESSION_DELETED,
            function (SessionDeletedEvent $event) use (&$seen): void {
                $seen[] = $event->getSessionId();
            }
        );

        SessionManager::delete_session_category($categoryId, true, true);

        $this->assertSame([$sessionId], $seen);
        $this->assertNull(
            self::getContainer()->get(SessionRepository::class)->find($sessionId),
            'The session must be deleted along with its category, not just detached.'
        );
    }
}
