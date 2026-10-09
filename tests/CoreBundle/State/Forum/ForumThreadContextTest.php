<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\State\Forum;

use ApiPlatform\Symfony\Bundle\Test\Client;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

/**
 * Forum categories and forums are shared by the base course and all its sessions, but a thread
 * only belongs to the context it was created in: a thread started in the base course must not
 * show in its sessions, and a thread started in one session must not show in the base course
 * or in another session.
 */
final class ForumThreadContextTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testThreadListOnlyShowsTheThreadsOfTheCurrentContext(): void
    {
        $ctx = $this->bootstrapScenario('list');
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['admin']));

        $this->assertSame(
            ['Base thread list'],
            $this->getThreadTitles($client, $ctx['forum'], $ctx['course'], null),
        );
        $this->assertSame(
            ['Session one thread list'],
            $this->getThreadTitles($client, $ctx['forum'], $ctx['course'], $ctx['sessionOne']),
        );
        $this->assertSame(
            ['Session two thread list'],
            $this->getThreadTitles($client, $ctx['forum'], $ctx['course'], $ctx['sessionTwo']),
        );
    }

    public function testThreadOfAnotherContextCannotBeOpened(): void
    {
        $ctx = $this->bootstrapScenario('open');
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['admin']));
        $forumId = $ctx['forum']->getIid();
        $courseId = $ctx['course']->getId();
        $sessionOneId = $ctx['sessionOne']->getId();

        $client->request('GET', "/api/forum_threads/{$ctx['baseThread']->getIid()}/posts?forumId=$forumId&cid=$courseId&sid=$sessionOneId");
        $this->assertResponseStatusCodeSame(404);

        $client->request('GET', "/api/forum_threads/{$ctx['sessionTwoThread']->getIid()}/posts?forumId=$forumId&cid=$courseId&sid=$sessionOneId");
        $this->assertResponseStatusCodeSame(404);

        $client->request('GET', "/api/forum_threads/{$ctx['sessionOneThread']->getIid()}/posts?forumId=$forumId&cid=$courseId");
        $this->assertResponseStatusCodeSame(404);

        $client->request('GET', "/api/forum_threads/{$ctx['sessionOneThread']->getIid()}/posts?forumId=$forumId&cid=$courseId&sid=$sessionOneId");
        $this->assertResponseIsSuccessful();
    }

    public function testThreadOfAnotherContextCannotBeManaged(): void
    {
        $ctx = $this->bootstrapScenario('manage');
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['admin']));
        $context = '?cid='.$ctx['course']->getId().'&sid='.$ctx['sessionOne']->getId();
        $options = ['headers' => ['Content-Type' => 'application/merge-patch+json'], 'body' => '{}'];

        $client->request('PATCH', '/api/forum_threads/'.$ctx['baseThread']->getIid().'/toggle-sticky'.$context, $options);
        $this->assertResponseStatusCodeSame(404);

        $client->request('PATCH', '/api/forum_threads/'.$ctx['sessionOneThread']->getIid().'/toggle-sticky'.$context, $options);
        $this->assertResponseIsSuccessful();
    }

    public function testPostOfAnotherContextCannotBeManaged(): void
    {
        $ctx = $this->bootstrapScenario('post');
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['admin']));
        $em = $this->getEntityManager();

        $post = (new CForumPost())
            ->setTitle('Base post')
            ->setPostText('Base post text')
            ->setThread($ctx['baseThread'])
            ->setForum($ctx['forum'])
            ->setUser($ctx['admin'])
            ->setPostDate(new DateTime())
            ->setVisible(true)
            ->setStatus(CForumPost::STATUS_VALIDATED)
            ->setParent($ctx['baseThread'])
            ->addCourseLink($ctx['course'])
        ;
        $em->persist($post);
        $em->flush();

        $courseContext = '?cid='.$ctx['course']->getId();
        $options = ['headers' => ['Content-Type' => 'application/merge-patch+json'], 'body' => '{}'];

        $client->request('PATCH', '/api/forum_posts/'.$post->getIid().'/toggle-visibility'.$courseContext.'&sid='.$ctx['sessionOne']->getId(), $options);
        $this->assertResponseStatusCodeSame(404);

        $client->request('PATCH', '/api/forum_posts/'.$post->getIid().'/toggle-visibility'.$courseContext, $options);
        $this->assertResponseIsSuccessful();
    }

    /**
     * @return string[]
     */
    private function getThreadTitles(Client $client, CForum $forum, Course $course, ?Session $session): array
    {
        $url = '/api/forum_threads?forum=/api/forums/'.$forum->getIid().'&cid='.$course->getId();
        if (null !== $session) {
            $url .= '&sid='.$session->getId();
        }

        $response = $client->request('GET', $url, ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        return array_map(
            static fn (array $thread): string => (string) $thread['title'],
            $data['hydra:member'] ?? $data['member'] ?? [],
        );
    }

    private function bootstrapScenario(string $suffix): array
    {
        $sessionRepo = self::getContainer()->get(SessionRepository::class);
        $em = $this->getEntityManager();

        $admin = $this->createUser('forum_ctx_admin_'.$suffix, '', '', 'ROLE_ADMIN');
        $course = $this->createCourse('Forum Context Course '.$suffix);

        $sessionOne = $this->createSession('Forum Context Session One '.$suffix);
        $sessionTwo = $this->createSession('Forum Context Session Two '.$suffix);
        $sessionOne->addCourse($course);
        $sessionTwo->addCourse($course);
        $sessionRepo->update($sessionOne);
        $sessionRepo->update($sessionTwo);

        // The forum is created in the base course, so it shows in every session too.
        $forum = (new CForum())
            ->setTitle('Forum Context '.$suffix)
            ->setParent($course)
            ->setCreator($admin)
            ->addCourseLink($course)
        ;
        $em->persist($forum);
        $em->flush();

        $baseThread = $this->createThread($forum, $course, null, 'Base thread '.$suffix, $admin);
        $sessionOneThread = $this->createThread($forum, $course, $sessionOne, 'Session one thread '.$suffix, $admin);
        $sessionTwoThread = $this->createThread($forum, $course, $sessionTwo, 'Session two thread '.$suffix, $admin);

        return [
            'admin' => $admin,
            'course' => $course,
            'sessionOne' => $sessionOne,
            'sessionTwo' => $sessionTwo,
            'forum' => $forum,
            'baseThread' => $baseThread,
            'sessionOneThread' => $sessionOneThread,
            'sessionTwoThread' => $sessionTwoThread,
        ];
    }

    private function createThread(CForum $forum, Course $course, ?Session $session, string $title, User $creator): CForumThread
    {
        $em = $this->getEntityManager();

        $thread = (new CForumThread())
            ->setTitle($title)
            ->setThreadDate(new DateTime())
            ->setForum($forum)
            ->setParent($course)
            ->setCreator($creator)
            ->addCourseLink($course, $session)
        ;
        $em->persist($thread);
        $em->flush();

        return $thread;
    }
}
