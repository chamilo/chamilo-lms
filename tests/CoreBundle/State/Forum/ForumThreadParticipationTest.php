<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\State\Forum;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
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
 * tells learners how far they are from the number of posts expected in a thread. When the platform
 * allows it (forum.forum_participation_can_be_modified_in_course), a course turns these settings on
 * or off for itself, so that they apply to some courses only.
 */
final class ForumThreadParticipationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * The expectations below depend on these settings, so they must not come from the configuration
     * of the database the tests run on. A test changes them after this.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->updateSetting('forum.show_thread_replies_after_participation', 'false');
        $this->updateSetting('forum.show_category_description_in_thread', 'false');
        $this->updateSetting('forum.thread_participation_progress_posts', '0');
        $this->updateSetting('forum.forum_participation_can_be_modified_in_course', 'false');
        $this->updateSetting('forum.thread_max_posts_for_non_admins', '0');
    }

    /**
     * Inside a learning path, Chamilo 1.11 showed the opening post first, then the latest replies of
     * each level, so that a learner sees their reply right under the opening post. A post without a
     * shown parent goes under the opening post.
     */
    public function testLearningPathShowsTheOpeningPostThenTheLatestReplies(): void
    {
        $ctx = $this->bootstrapTreeScenario('lptree');
        $learningPath = '&origin=learnpath&view=nested';

        $data = $this->getThreadPosts($ctx['learner'], $ctx, $learningPath);
        $this->assertSame(
            ['Opening question lptree', 'Learner answer lptree', 'Classmate answer lptree', 'Teacher answer lptree', 'Reply to teacher lptree'],
            $this->getPostTitles($data),
        );
        $displayParents = array_column($data['posts'], 'displayParentId', 'title');
        $this->assertSame($ctx['opening']->getIid(), $displayParents['Classmate answer lptree'], 'A post without parent goes under the opening post.');

        $this->assertSame(
            ['Opening question lptree', 'Teacher answer lptree', 'Reply to teacher lptree', 'Classmate answer lptree', 'Learner answer lptree'],
            $this->getPostTitles($this->getThreadPosts($ctx['learner'], $ctx, '&view=nested')),
        );
    }

    /**
     * The limit applies to each reply level, in the order of the view: inside a learning path it
     * keeps the latest replies, for administrators too, as Chamilo 1.11 did for Ricky Rescue.
     */
    public function testPostLimitKeepsTheLatestRepliesInsideALearningPath(): void
    {
        $this->updateSetting('forum.thread_max_posts_for_non_admins', '2');
        $ctx = $this->bootstrapTreeScenario('lplimit');
        $admin = $this->createUser('forum_part_admin_lplimit', '', '', 'ROLE_ADMIN');

        foreach ([$ctx['learner'], $admin] as $user) {
            $this->assertSame(
                ['Opening question lplimit', 'Learner answer lplimit', 'Classmate answer lplimit'],
                $this->getPostTitles($this->getThreadPosts($user, $ctx, '&origin=learnpath&view=nested')),
            );
        }

        $this->assertSame(
            ['Opening question lplimit', 'Teacher answer lplimit', 'Reply to teacher lplimit', 'Classmate answer lplimit'],
            $this->getPostTitles($this->getThreadPosts($ctx['learner'], $ctx, '&view=nested')),
        );
        $this->assertCount(5, $this->getThreadPosts($admin, $ctx, '&view=nested')['posts'], 'Outside a learning path, administrators see every post.');
    }

    /**
     * Ricky Rescue only showed the first 49 posts of a thread to everyone but platform administrators.
     */
    public function testOnlyPlatformAdministratorsSeeThePostsBeyondTheLimit(): void
    {
        $this->updateSetting('forum.thread_max_posts_for_non_admins', '2');
        $ctx = $this->bootstrapScenario('postlimit');
        $this->createPost($ctx, $ctx['learner'], 'Learner answer postlimit', CForumPost::STATUS_VALIDATED);
        $admin = $this->createUser('forum_part_admin_postlimit', '', '', 'ROLE_ADMIN');

        foreach ([$ctx['learner'], $ctx['teacher']] as $user) {
            $data = $this->getThreadPosts($user, $ctx);
            $this->assertSame(['Opening question postlimit', 'Classmate answer postlimit'], $this->getPostTitles($data));
            $this->assertSame(2, $data['totalItems']);
        }

        $this->assertSame(
            ['Opening question postlimit', 'Classmate answer postlimit', 'Learner answer postlimit'],
            $this->getPostTitles($this->getThreadPosts($admin, $ctx)),
        );
    }

    public function testCourseTurnsOnTheRestrictionThePlatformLeavesOff(): void
    {
        $this->updateSetting('forum.forum_participation_can_be_modified_in_course', 'true');
        $ctx = $this->bootstrapScenario('courseon');
        $this->setCourseSetting($ctx, 'show_thread_replies_after_participation', 'true');

        $data = $this->getThreadPosts($ctx['learner'], $ctx);

        $this->assertSame(['Opening question courseon'], $this->getPostTitles($data));
        $this->assertTrue($data['thread']['repliesHiddenUntilParticipation']);
    }

    public function testCourseTurnsOffTheRestrictionThePlatformTurnsOn(): void
    {
        $this->updateSetting('forum.show_thread_replies_after_participation', 'true');
        $this->updateSetting('forum.forum_participation_can_be_modified_in_course', 'true');
        $ctx = $this->bootstrapScenario('courseoff');
        $this->setCourseSetting($ctx, 'show_thread_replies_after_participation', 'false');

        $data = $this->getThreadPosts($ctx['learner'], $ctx);

        $this->assertSame(['Opening question courseoff', 'Classmate answer courseoff'], $this->getPostTitles($data));
    }

    public function testCourseWithoutChoiceFollowsThePlatform(): void
    {
        $this->updateSetting('forum.show_thread_replies_after_participation', 'true');
        $this->updateSetting('forum.forum_participation_can_be_modified_in_course', 'true');
        $ctx = $this->bootstrapScenario('coursedefault');
        $this->setCourseSetting($ctx, 'show_thread_replies_after_participation', '');

        $this->assertSame(['Opening question coursedefault'], $this->getPostTitles($this->getThreadPosts($ctx['learner'], $ctx)));
    }

    public function testCourseChoiceIsIgnoredWhenCoursesCannotOverrideThePlatform(): void
    {
        $ctx = $this->bootstrapScenario('courseignored');
        $this->setCourseSetting($ctx, 'show_thread_replies_after_participation', 'true');
        $this->setCourseSetting($ctx, 'thread_participation_progress_posts', '2');

        $data = $this->getThreadPosts($ctx['learner'], $ctx);

        $this->assertSame(['Opening question courseignored', 'Classmate answer courseignored'], $this->getPostTitles($data));
        $this->assertNull($data['thread']['participationProgress']);
    }

    public function testCourseSetsTheProgressBarAndTheCategoryDescription(): void
    {
        $this->updateSetting('forum.forum_participation_can_be_modified_in_course', 'true');
        $ctx = $this->bootstrapScenario('courseextras');
        $this->setCourseSetting($ctx, 'thread_participation_progress_posts', '3');
        $this->setCourseSetting($ctx, 'show_category_description_in_thread', 'true');

        $data = $this->getThreadPosts($ctx['learner'], $ctx);

        $this->assertSame(3, $data['thread']['participationProgress']['requiredPosts']);
        $this->assertSame('Category courseextras', $data['forum']['category']['title']);
    }

    public function testTeacherChoosesTheForumParticipationSettingsOfTheirCourse(): void
    {
        $this->updateSetting('forum.forum_participation_can_be_modified_in_course', 'true');
        $ctx = $this->bootstrapScenario('teacherset');
        $courseSettingsUrl = '/api/course-settings?cid='.$ctx['course']->getId();

        $teacher = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']));
        $response = $teacher->request('GET', $courseSettingsUrl);
        $this->assertResponseIsSuccessful();
        $this->assertContains('show_thread_replies_after_participation', $this->getFieldKeys($response->toArray(), 'forum'));

        $teacher->request('POST', $courseSettingsUrl, ['json' => ['values' => [
            'show_thread_replies_after_participation' => 'true',
            'thread_participation_progress_posts' => '2',
        ]]]);
        $this->assertResponseIsSuccessful();

        $data = $this->getThreadPosts($ctx['learner'], $ctx);
        $this->assertSame(['Opening question teacherset'], $this->getPostTitles($data));
        $this->assertSame(2, $data['thread']['participationProgress']['requiredPosts']);

        $teacher = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']));
        $teacher->request('POST', $courseSettingsUrl, ['json' => ['values' => [
            'thread_participation_progress_posts' => 'two',
        ]]]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCourseSettingsHideTheChoiceWhenCoursesCannotOverrideThePlatform(): void
    {
        $ctx = $this->bootstrapScenario('coursehidden');

        $teacher = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']));
        $response = $teacher->request('GET', '/api/course-settings?cid='.$ctx['course']->getId());
        $this->assertResponseIsSuccessful();

        $this->assertNotContains('show_thread_replies_after_participation', $this->getFieldKeys($response->toArray(), 'forum'));
    }

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

    /**
     * The opening post, a reply of the teacher to it and a reply to that reply, the classmate answer
     * left without parent (as a post migrated from Chamilo 1.11 can be), and the latest reply of the
     * learner to the opening post.
     *
     * @return array<string, mixed>
     */
    private function bootstrapTreeScenario(string $suffix): array
    {
        $ctx = $this->bootstrapScenario($suffix);
        $ctx['opening'] = $this->getEntityManager()->getRepository(CForumPost::class)->findOneBy(['title' => 'Opening question '.$suffix]);
        $teacherAnswer = $this->createPost($ctx, $ctx['teacher'], 'Teacher answer '.$suffix, CForumPost::STATUS_VALIDATED, 'Answer', '-90 minutes', $ctx['opening']);
        $this->createPost($ctx, $ctx['learner'], 'Reply to teacher '.$suffix, CForumPost::STATUS_VALIDATED, 'Answer', '-30 minutes', $teacherAnswer);
        $this->createPost($ctx, $ctx['learner'], 'Learner answer '.$suffix, CForumPost::STATUS_VALIDATED, 'Answer', 'now', $ctx['opening']);

        return $ctx;
    }

    /**
     * @param array<string, mixed> $ctx
     */
    private function setCourseSetting(array $ctx, string $variable, string $value): void
    {
        $em = $this->getEntityManager();
        $em->persist(
            (new CCourseSetting())
                ->setCId((int) $ctx['course']->getId())
                ->setVariable($variable)
                ->setTitle($variable)
                ->setCategory('forum')
                ->setValue($value)
        );
        $em->flush();
    }

    /**
     * @param array<string, mixed> $configuration
     *
     * @return string[]
     */
    private function getFieldKeys(array $configuration, string $sectionKey): array
    {
        foreach ($configuration['sections'] as $section) {
            if ($sectionKey === $section['key']) {
                return array_column($section['fields'], 'key');
            }
        }

        return [];
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
    private function getThreadPosts(User $user, array $ctx, string $query = ''): array
    {
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($user));
        $response = $client->request(
            'GET',
            '/api/forum_threads/'.$ctx['thread']->getIid().'/posts?forumId='.$ctx['forum']->getIid().'&cid='.$ctx['course']->getId().$query,
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
        ?CForumPost $parent = null,
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
            ->setPostParent(null === $parent ? null : $em->getReference(CForumPost::class, $parent->getIid()))
            ->addCourseLink($em->getReference(Course::class, $ctx['course']->getId()))
        ;
        $em->persist($post);
        $em->flush();

        return $post;
    }
}
