<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * A course event has a comment, as in the legacy agenda form. The teacher writes it when creating
 * or editing the event, and the course members read it in the agenda.
 */
final class CCalendarEventCommentApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testTheCommentOfACourseEventIsShownToTheStudents(): void
    {
        $ctx = $this->bootstrapScenario('read');

        $this->createEvent($ctx, 'CcWithComment', 'Bring the lab coat');
        $this->assertResponseStatusCodeSame(201);

        $this->assertSame('Bring the lab coat', $this->commentInAgenda($ctx['student'], $ctx['course'], 'CcWithComment'));
    }

    public function testEditingTheEventChangesTheComment(): void
    {
        $ctx = $this->bootstrapScenario('edit');

        $this->createEvent($ctx, 'CcEdited', 'First comment');
        $this->assertResponseStatusCodeSame(201);

        $em = $this->getEntityManager();
        $em->clear();
        $event = $em->getRepository(CCalendarEvent::class)->findOneBy(['title' => 'CcEdited']);

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'PUT',
            '/api/c_calendar_events/'.$event?->getIid().'?cid='.$ctx['course']->getId(),
            [
                'json' => [
                    'title' => 'CcEdited',
                    'content' => '<p>CcEdited</p>',
                    'comment' => 'Second comment',
                    'startDate' => '2040-06-30 11:00:00',
                    'endDate' => '2040-06-30 15:00:00',
                    'collective' => false,
                ],
            ],
        );
        $this->assertResponseStatusCodeSame(200);

        $this->assertSame('Second comment', $this->commentInAgenda($ctx['student'], $ctx['course'], 'CcEdited'));
    }

    /**
     * @return array{course: Course, teacher: User, student: User}
     */
    private function bootstrapScenario(string $suffix): array
    {
        $course = $this->createCourse('CEC '.$suffix);
        $course->setVisibility(Course::REGISTERED);

        $teacher = $this->createUser('cec_teacher_'.$suffix);
        $student = $this->createUser('cec_student_'.$suffix);

        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        self::getContainer()->get(CourseRepository::class)->update($course);

        return ['course' => $course, 'teacher' => $teacher, 'student' => $student];
    }

    /**
     * @param array{course: Course, teacher: User} $ctx
     */
    private function createEvent(array $ctx, string $title, string $comment): void
    {
        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'POST',
            '/api/c_calendar_events?cid='.$ctx['course']->getId(),
            [
                'json' => [
                    'title' => $title,
                    'content' => '<p>'.$title.'</p>',
                    'comment' => $comment,
                    'startDate' => '2040-06-30 11:00:00',
                    'endDate' => '2040-06-30 15:00:00',
                    'allDay' => false,
                    'collective' => false,
                    'parentResourceNode' => (int) $ctx['teacher']->getResourceNode()?->getId(),
                    'resourceLinkList' => [['visibility' => ResourceLink::VISIBILITY_PUBLISHED]],
                ],
            ],
        );
    }

    private function commentInAgenda(User $user, Course $course, string $title): ?string
    {
        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            'GET',
            '/api/c_calendar_events?cid='.$course->getId(),
            ['headers' => ['Accept' => 'application/json']],
        );
        $this->assertResponseIsSuccessful();

        foreach ($response->toArray() as $event) {
            if ($title === $event['title']) {
                return $event['comment'] ?? null;
            }
        }

        $this->fail('The event "'.$title.'" is not in the agenda of '.$user->getUsername().'.');
    }
}
