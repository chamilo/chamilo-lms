<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\State\Forum;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

/**
 * Some courses ask each learner to give their own answer before reading the answers of the
 * others: with forum.show_thread_replies_after_participation, a learner only gets the opening
 * post of a thread until they posted in it, from every endpoint that returns post content. The
 * people who moderate the forum always read everything. forum.thread_participation_progress_posts
 * tells learners how far they are from the number of posts expected in a thread.
 */
final class ForumThreadParticipationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testLearnerOnlyReadsTheOpeningPostUntilTheyPost(): void
    {
        $this->updateSetting('forum.show_thread_replies_after_participation', 'true');
        $ctx = $this->bootstrapScenario('read');

        $data = $this->getThreadPosts($ctx['learner'], $ctx);
        $this->assertSame(['Opening question read'], $this->getPostTitles($data));
        $this->assertTrue($data['thread']['repliesHiddenUntilParticipation']);

        // A post waiting for moderation is a participation too, even though nobody reads it yet.
        $this->createPost($ctx, $ctx['learner'], 'Learner answer read', CForumPost::STATUS_WAITING_MODERATION);

        $data = $this->getThreadPosts($ctx['learner'], $ctx);
        $this->assertSame(['Opening question read', 'Classmate answer read'], $this->getPostTitles($data));
        $this->assertFalse($data['thread']['repliesHiddenUntilParticipation']);
    }

    public function testTeacherAndAdminReadEveryPostWithoutPosting(): void
    {
        $this->updateSetting('forum.show_thread_replies_after_participation', 'true');
        $ctx = $this->bootstrapScenario('moderators');

        foreach ([$ctx['teacher'], $this->createUser('forum_part_admin_moderators', '', '', 'ROLE_ADMIN')] as $user) {
            $data = $this->getThreadPosts($user, $ctx);
            $this->assertSame(['Opening question moderators', 'Classmate answer moderators'], $this->getPostTitles($data));
            $this->assertFalse($data['thread']['repliesHiddenUntilParticipation']);
        }
    }

    public function testLearnerReadsEveryPostWhenTheSettingIsOff(): void
    {
        $this->updateSetting('forum.show_thread_replies_after_participation', 'false');
        $ctx = $this->bootstrapScenario('off');

        $data = $this->getThreadPosts($ctx['learner'], $ctx);
        $this->assertSame(['Opening question off', 'Classmate answer off'], $this->getPostTitles($data));
        $this->assertFalse($data['thread']['repliesHiddenUntilParticipation']);
    }

    public function testThreadListAndSearchDoNotRevealRepliesBeforeParticipation(): void
    {
        $this->updateSetting('forum.show_thread_replies_after_participation', 'true');
        $ctx = $this->bootstrapScenario('leak');
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['learner']));
        $courseId = $ctx['course']->getId();

        $response = $client->request('GET', '/api/forum_threads?forum=/api/forums/'.$ctx['forum']->getIid().'&cid='.$courseId, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $threads = $response->toArray()['hydra:member'] ?? $response->toArray()['member'] ?? [];
        $this->assertCount(1, $threads);
        $this->assertSame('', $threads[0]['lastPostText']);

        $this->assertSame([], $this->searchPostTitles($ctx['learner'], $courseId, 'classmatesecret'));
        $this->assertSame(['Opening question leak'], $this->searchPostTitles($ctx['learner'], $courseId, 'openingsecret'));

        $this->createPost($ctx, $ctx['learner'], 'Learner answer leak', CForumPost::STATUS_VALIDATED);

        $this->assertSame(['Classmate answer leak'], $this->searchPostTitles($ctx['learner'], $courseId, 'classmatesecret'));
    }

    public function testProgressCountsTheLearnerPostsInTheThread(): void
    {
        $this->updateSetting('forum.thread_participation_progress_posts', '2');
        $ctx = $this->bootstrapScenario('progress');

        $this->assertSame(
            ['postCount' => 0, 'requiredPosts' => 2, 'percent' => 0],
            $this->getThreadPosts($ctx['learner'], $ctx)['thread']['participationProgress'],
        );

        $this->createPost($ctx, $ctx['learner'], 'Learner answer progress', CForumPost::STATUS_WAITING_MODERATION);
        $this->assertSame(
            ['postCount' => 1, 'requiredPosts' => 2, 'percent' => 50],
            $this->getThreadPosts($ctx['learner'], $ctx)['thread']['participationProgress'],
        );

        $this->createPost($ctx, $ctx['learner'], 'Learner second answer progress', CForumPost::STATUS_VALIDATED);
        $this->createPost($ctx, $ctx['learner'], 'Learner third answer progress', CForumPost::STATUS_VALIDATED);
        $this->assertSame(
            ['postCount' => 3, 'requiredPosts' => 2, 'percent' => 100],
            $this->getThreadPosts($ctx['learner'], $ctx)['thread']['participationProgress'],
        );

        $this->assertNull($this->getThreadPosts($ctx['teacher'], $ctx)['thread']['participationProgress']);
    }

    public function testNoProgressWhenNoPostCountIsRequired(): void
    {
        $this->updateSetting('forum.thread_participation_progress_posts', '0');
        $ctx = $this->bootstrapScenario('noprogress');

        $this->assertNull($this->getThreadPosts($ctx['learner'], $ctx)['thread']['participationProgress']);
    }

    public function testThreadShowsTheDescriptionOfItsOwnForumCategory(): void
    {
        $this->updateSetting('forum.show_category_description_in_thread', 'true');
        $ctx = $this->bootstrapScenario('category');

        $this->assertSame(
            ['title' => 'Category category', 'comment' => '<p>Read the case study before answering.</p>'],
            $this->getThreadPosts($ctx['learner'], $ctx)['forum']['category'],
        );

        $this->updateSetting('forum.show_category_description_in_thread', 'false');
        $this->assertNull($this->getThreadPosts($ctx['learner'], $ctx)['forum']['category']);
    }

    private function updateSetting(string $name, string $value): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting($name, $value);
    }

    /**
     * @param array<string, mixed> $ctx
     *
     * @return array<string, mixed>
     */
    private function getThreadPosts(User $user, array $ctx): array
    {
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($user));
        $response = $client->request(
            'GET',
            '/api/forum_threads/'.$ctx['thread']->getIid().'/posts?forumId='.$ctx['forum']->getIid().'&cid='.$ctx['course']->getId(),
        );
        $this->assertResponseIsSuccessful();

        return $response->toArray();
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return string[]
     */
    private function getPostTitles(array $data): array
    {
        return array_map(static fn (array $post): string => (string) $post['title'], $data['posts']);
    }

    /**
     * @return string[]
     */
    private function searchPostTitles(User $user, int $courseId, string $query): array
    {
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($user));
        $response = $client->request('GET', '/api/forum/search?q='.$query.'&cid='.$courseId);
        $this->assertResponseIsSuccessful();

        $posts = array_filter($response->toArray()['items'], static fn (array $item): bool => 'post' === $item['type']);

        return array_values(array_map(static fn (array $item): string => (string) $item['title'], $posts));
    }

    /**
     * A course with a teacher and two learners, and a forum thread opened by the teacher that a
     * classmate already answered.
     *
     * @return array<string, mixed>
     */
    private function bootstrapScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $em = $this->getEntityManager();

        $course = $this->createCourse('Forum Participation Course '.$suffix);
        $course->setVisibility(Course::REGISTERED);
        $teacher = $this->createUser('forum_part_teacher_'.$suffix);
        $learner = $this->createUser('forum_part_learner_'.$suffix);
        $classmate = $this->createUser('forum_part_classmate_'.$suffix);
        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($learner);
        $course->addUserAsStudent($classmate);
        $courseRepo->update($course);

        $category = (new CForumCategory())
            ->setTitle('Category '.$suffix)
            ->setCatComment('<p>Read the case study before answering.</p>')
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($category);

        $forum = (new CForum())
            ->setTitle('Forum Participation '.$suffix)
            ->setForumCategory($category)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($forum);

        $thread = (new CForumThread())
            ->setTitle('Thread '.$suffix)
            ->setThreadDate(new DateTime())
            ->setForum($forum)
            ->setUser($teacher)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($thread);
        $em->flush();

        $ctx = [
            'course' => $course,
            'teacher' => $teacher,
            'learner' => $learner,
            'forum' => $forum,
            'thread' => $thread,
        ];

        $this->createPost($ctx, $teacher, 'Opening question '.$suffix, CForumPost::STATUS_VALIDATED, 'openingsecret', '-2 hours');
        $reply = $this->createPost($ctx, $classmate, 'Classmate answer '.$suffix, CForumPost::STATUS_VALIDATED, 'classmatesecret', '-1 hour');
        $thread->setThreadLastPost($reply);
        $em->flush();

        return $ctx;
    }

    /**
     * @param array<string, mixed> $ctx
     */
    private function createPost(
        array $ctx,
        User $author,
        string $title,
        int $status,
        string $text = 'Answer',
        string $date = 'now',
    ): CForumPost {
        $em = $this->getEntityManager();

        // Creating an API client reboots the kernel, so the scenario entities are re-attached.
        $author = $em->getReference(User::class, $author->getId());
        $thread = $em->getReference(CForumThread::class, $ctx['thread']->getIid());
        $post = (new CForumPost())
            ->setTitle($title)
            ->setPostText('<p>'.$text.'</p>')
            ->setThread($thread)
            ->setForum($em->getReference(CForum::class, $ctx['forum']->getIid()))
            ->setUser($author)
            ->setCreator($author)
            ->setPostDate(new DateTime($date))
            ->setVisible(CForumPost::STATUS_VALIDATED === $status)
            ->setStatus($status)
            ->setParent($thread)
            ->addCourseLink($em->getReference(Course::class, $ctx['course']->getId()))
        ;
        $em->persist($post);
        $em->flush();

        return $post;
    }
}
