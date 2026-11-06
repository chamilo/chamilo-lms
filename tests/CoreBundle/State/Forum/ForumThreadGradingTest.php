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
 * A teacher grades the participation of each learner in a forum thread, as forumqualify.php did in
 * Chamilo 1.11: the grading dialog of the thread list reads the thread grading, lists the learners
 * who posted in it, and saves a score per learner. Learners cannot grade unless peer grading is on.
 */
final class ForumThreadGradingTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testTeacherGradesTheLearnersWhoPostedInTheThread(): void
    {
        $ctx = $this->bootstrapScenario('grade');
        $teacher = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']));
        $gradingUrl = '/api/forum_threads/'.$ctx['thread']->getIid().'/grading?cid='.$ctx['course']->getId();

        // The grading dialog could not open at all: the read answered 404 for every thread
        $grading = $teacher->request('GET', $gradingUrl)->toArray();
        $this->assertFalse($grading['enabled']);
        $this->assertSame(
            [$ctx['learner']->getId()],
            array_column($grading['students'], 'userId'),
            'Only the learners who posted are listed, not the teacher who opened the thread.',
        );
        $this->assertNotEmpty($grading['categories']);

        $teacher->request('PATCH', $gradingUrl, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'enabled' => true,
                'categoryId' => $grading['categories'][0]['id'],
                'title' => 'Participation',
                'maxScore' => 10,
                'weight' => 1,
                'peerQualify' => false,
            ],
        ]);
        $this->assertResponseIsSuccessful();

        $teacher->request('PATCH', '/api/forum_threads/'.$ctx['thread']->getIid().'/grading/score?cid='.$ctx['course']->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['userId' => $ctx['learner']->getId(), 'score' => 7],
        ]);
        $this->assertResponseIsSuccessful();

        $grading = $teacher->request('GET', $gradingUrl)->toArray();
        $this->assertTrue($grading['enabled']);
        $this->assertEquals(7, $grading['students'][0]['score']);
    }

    /**
     * Before giving a score, the teacher reads what the learner wrote and how much of the thread it
     * is, as on the 1.11 grading page of one learner.
     */
    public function testTeacherReadsTheParticipationOfOneLearner(): void
    {
        $ctx = $this->bootstrapScenario('detail');
        $teacher = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']));
        $gradingUrl = '/api/forum_threads/'.$ctx['thread']->getIid().'/grading?cid='.$ctx['course']->getId();

        $participant = $teacher->request('GET', $gradingUrl.'&userId='.$ctx['learner']->getId())->toArray()['participant'];
        $this->assertSame('Thread detail', $participant['threadTitle']);
        $this->assertSame(2, $participant['courseUserCount']);
        $this->assertSame(2, $participant['threadPostCount']);
        $this->assertSame(1, $participant['userPostCount']);
        $this->assertSame(50, $participant['postShare']);
        $this->assertSame(['Learner answer detail'], array_column($participant['posts'], 'title'));
        // The teacher can remove an off-topic post before grading, from the same window
        $this->assertTrue($participant['posts'][0]['canDelete']);

        $this->assertArrayNotHasKey('participant', $teacher->request('GET', $gradingUrl)->toArray());

        // Only the learners of the list can be detailed, not the teacher who opened the thread
        $teacher->request('GET', $gradingUrl.'&userId='.$ctx['teacher']->getId());
        $this->assertResponseStatusCodeSame(404);
    }

    public function testLearnerCannotGradeWithoutPeerGrading(): void
    {
        $ctx = $this->bootstrapScenario('learner');
        $learner = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['learner']));

        $learner->request('GET', '/api/forum_threads/'.$ctx['thread']->getIid().'/grading?cid='.$ctx['course']->getId());
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * A course with a teacher and a learner, and a forum thread opened by the teacher that the
     * learner answered.
     *
     * @return array<string, mixed>
     */
    private function bootstrapScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $em = $this->getEntityManager();

        $course = $this->createCourse('Forum Grading Course '.$suffix);
        $course->setVisibility(Course::REGISTERED);
        $teacher = $this->createUser('forum_grading_teacher_'.$suffix);
        $learner = $this->createUser('forum_grading_learner_'.$suffix);
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
            ->setTitle('Forum Grading '.$suffix)
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

        $this->createPost($course, $forum, $thread, $teacher, 'Opening question '.$suffix);
        $this->createPost($course, $forum, $thread, $learner, 'Learner answer '.$suffix);
        $em->flush();

        return [
            'course' => $course,
            'teacher' => $teacher,
            'learner' => $learner,
            'thread' => $thread,
        ];
    }

    private function createPost(Course $course, CForum $forum, CForumThread $thread, User $author, string $title): void
    {
        $post = (new CForumPost())
            ->setTitle($title)
            ->setPostText('<p>'.$title.'</p>')
            ->setThread($thread)
            ->setForum($forum)
            ->setUser($author)
            ->setCreator($author)
            ->setPostDate(new DateTime())
            ->setVisible(true)
            ->setStatus(CForumPost::STATUS_VALIDATED)
            ->setParent($thread)
            ->addCourseLink($course)
        ;
        $this->getEntityManager()->persist($post);
    }
}
