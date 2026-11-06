<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\State\Forum;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

/**
 * A thread lists its posts from the oldest. On a long thread, a teacher can list them from the
 * newest to follow its latest activity; learners keep reading the conversation in its order.
 */
final class ForumThreadPostsOrderTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testTeacherListsThePostsFromTheNewest(): void
    {
        $ctx = $this->bootstrapScenario('teacher');
        $teacher = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']));

        $this->assertSame(['Opening teacher', 'Answer teacher', 'Follow-up teacher'], $this->getPostTitles($teacher, $ctx, ''));
        $this->assertSame(['Follow-up teacher', 'Answer teacher', 'Opening teacher'], $this->getPostTitles($teacher, $ctx, 'desc'));
    }

    public function testLearnerKeepsTheChronologicalOrder(): void
    {
        $ctx = $this->bootstrapScenario('learner');
        $learner = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['learner']));

        $this->assertSame(['Opening learner', 'Answer learner', 'Follow-up learner'], $this->getPostTitles($learner, $ctx, 'desc'));
    }

    /**
     * @param array<string, mixed> $ctx
     *
     * @return string[]
     */
    private function getPostTitles(mixed $client, array $ctx, string $order): array
    {
        $posts = $client->request(
            'GET',
            '/api/forum_threads/'.$ctx['thread']->getIid().'/posts?view=flat&forumId='.$ctx['forum']->getIid()
            .'&cid='.$ctx['course']->getId().('' === $order ? '' : '&order='.$order),
        )->toArray()['posts'];

        return array_column($posts, 'title');
    }

    /**
     * A course with a teacher and a learner, and a thread of three posts written one day apart.
     *
     * @return array<string, mixed>
     */
    private function bootstrapScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $em = $this->getEntityManager();

        $course = $this->createCourse('Forum Order Course '.$suffix);
        $course->setVisibility(Course::REGISTERED);
        $teacher = $this->createUser('forum_order_teacher_'.$suffix);
        $learner = $this->createUser('forum_order_learner_'.$suffix);
        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($learner);
        $courseRepo->update($course);

        $category = (new CForumCategory())
            ->setTitle('Category '.$suffix)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($category);

        $forum = (new CForum())
            ->setTitle('Forum Order '.$suffix)
            ->setForumCategory($category)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($forum);

        $thread = (new CForumThread())
            ->setTitle('Thread '.$suffix)
            ->setThreadDate(new DateTime('-3 days'))
            ->setForum($forum)
            ->setUser($teacher)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($thread);

        $this->createPost($course, $forum, $thread, $teacher, 'Opening '.$suffix, '-3 days');
        $this->createPost($course, $forum, $thread, $learner, 'Answer '.$suffix, '-2 days');
        $this->createPost($course, $forum, $thread, $teacher, 'Follow-up '.$suffix, '-1 day');
        $em->flush();

        return [
            'course' => $course,
            'teacher' => $teacher,
            'learner' => $learner,
            'forum' => $forum,
            'thread' => $thread,
        ];
    }

    private function createPost(Course $course, CForum $forum, CForumThread $thread, User $author, string $title, string $date): void
    {
        $post = (new CForumPost())
            ->setTitle($title)
            ->setPostText('<p>'.$title.'</p>')
            ->setThread($thread)
            ->setForum($forum)
            ->setUser($author)
            ->setCreator($author)
            ->setPostDate(new DateTime($date))
            ->setVisible(true)
            ->setStatus(CForumPost::STATUS_VALIDATED)
            ->setParent($thread)
            ->addCourseLink($course)
        ;
        $this->getEntityManager()->persist($post);
    }
}
