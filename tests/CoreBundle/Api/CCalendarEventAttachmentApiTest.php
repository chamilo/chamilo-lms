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
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * A course event can have attached files, as in the legacy agenda form. Only the editors of the
 * event add and delete them; a file is downloaded by whoever sees the event in their agenda, so an
 * event sent to one learner keeps its files away from the other learners.
 */
final class CCalendarEventAttachmentApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testTheLearnersOfTheCourseDownloadTheFileOfACourseEvent(): void
    {
        $ctx = $this->bootstrapScenario('course');
        $eventId = $this->createEvent($ctx, 'CatEveryone');

        $attachmentId = $this->upload($ctx['teacher'], $ctx['course'], $eventId, 'notes.txt');
        $this->assertResponseStatusCodeSame(201);

        $this->assertSame('Agenda attachment', $this->download($ctx['student'], $eventId, $attachmentId));
        $this->assertResponseIsSuccessful();

        // The collection lists the attachment with the event.
        $this->assertSame(['notes.txt'], array_column($this->eventInAgenda($ctx['student'], $ctx['course'], 'CatEveryone')['attachments'], 'filename'));

        $this->download($ctx['outsider'], $eventId, $attachmentId);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTheFileOfAnEventSentToOneLearnerStaysHiddenFromTheOthers(): void
    {
        $ctx = $this->bootstrapScenario('recipient');
        $eventId = $this->createEvent($ctx, 'CatToStudent', ['cid' => (int) $ctx['course']->getId(), 'uid' => (int) $ctx['student']->getId()]);

        $attachmentId = $this->upload($ctx['teacher'], $ctx['course'], $eventId, 'notes.txt');
        $this->assertResponseStatusCodeSame(201);

        $this->download($ctx['student'], $eventId, $attachmentId);
        $this->assertResponseIsSuccessful();

        $this->download($ctx['other'], $eventId, $attachmentId);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOnlyTheEditorsOfTheEventAddAndDeleteFiles(): void
    {
        $ctx = $this->bootstrapScenario('editors');
        $eventId = $this->createEvent($ctx, 'CatEditors');

        $this->upload($ctx['student'], $ctx['course'], $eventId, 'notes.txt');
        $this->assertResponseStatusCodeSame(403);

        $attachmentId = $this->upload($ctx['teacher'], $ctx['course'], $eventId, 'notes.txt');
        $this->assertResponseStatusCodeSame(201);

        $this->deleteAttachment($ctx['student'], $ctx['course'], $eventId, $attachmentId);
        $this->assertResponseStatusCodeSame(403);

        $this->deleteAttachment($ctx['teacher'], $ctx['course'], $eventId, $attachmentId);
        $this->assertResponseIsSuccessful();

        $this->download($ctx['teacher'], $eventId, $attachmentId);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAPhpFileIsStoredUnderAHarmlessName(): void
    {
        $ctx = $this->bootstrapScenario('php');
        $eventId = $this->createEvent($ctx, 'CatPhp');

        // As every upload of the platform (UploadFilenamePolicy), the file can never run as PHP.
        $this->upload($ctx['teacher'], $ctx['course'], $eventId, 'shell.php');
        $this->assertResponseStatusCodeSame(201);

        $this->assertSame(['shell.phps'], array_column($this->eventInAgenda($ctx['teacher'], $ctx['course'], 'CatPhp')['attachments'], 'filename'));
    }

    /**
     * @return array{course: Course, teacher: User, student: User, other: User, outsider: User}
     */
    private function bootstrapScenario(string $suffix): array
    {
        $course = $this->createCourse('CAT '.$suffix);
        $course->setVisibility(Course::REGISTERED);

        $teacher = $this->createUser('cat_teacher_'.$suffix);
        $student = $this->createUser('cat_student_'.$suffix);
        $other = $this->createUser('cat_other_'.$suffix);

        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        $course->addUserAsStudent($other);
        self::getContainer()->get(CourseRepository::class)->update($course);

        return [
            'course' => $course,
            'teacher' => $teacher,
            'student' => $student,
            'other' => $other,
            'outsider' => $this->createUser('cat_outsider_'.$suffix),
        ];
    }

    /**
     * @param array{course: Course, teacher: User, ...} $ctx
     * @param array<string, int>                        $link
     */
    private function createEvent(array $ctx, string $title, array $link = []): int
    {
        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'POST',
            '/api/c_calendar_events?cid='.$ctx['course']->getId(),
            [
                'json' => [
                    'title' => $title,
                    'content' => '<p>'.$title.'</p>',
                    'startDate' => '2037-06-04T11:00:00+00:00',
                    'endDate' => '2037-06-04T12:00:00+00:00',
                    'allDay' => false,
                    'collective' => false,
                    'parentResourceNode' => (int) $ctx['teacher']->getResourceNode()?->getId(),
                    'resourceLinkList' => [$link + ['visibility' => ResourceLink::VISIBILITY_PUBLISHED]],
                ],
            ],
        );
        $this->assertResponseStatusCodeSame(201);

        $em = $this->getEntityManager();
        $em->clear();

        return (int) $em->getRepository(CCalendarEvent::class)->findOneBy(['title' => $title])?->getIid();
    }

    private function upload(User $user, Course $course, int $eventId, string $filename): int
    {
        // The upload moves the file, so each request gets its own copy.
        $path = tempnam(sys_get_temp_dir(), 'cat');
        file_put_contents($path, 'Agenda attachment');

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            'POST',
            '/api/calendar-events/'.$eventId.'/attachments?cid='.$course->getId(),
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => [
                    'parameters' => ['comment' => 'Read before the event'],
                    'files' => ['files' => [new UploadedFile($path, $filename, 'text/plain', null, true)]],
                ],
            ],
        );

        if (201 !== $response->getStatusCode()) {
            return 0;
        }

        return (int) $response->toArray()['attachments'][0]['id'];
    }

    private function download(User $user, int $eventId, int $attachmentId): string
    {
        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            'GET',
            '/api/calendar-events/'.$eventId.'/attachments/'.$attachmentId.'/download',
        );

        return 200 === $response->getStatusCode() ? $response->getContent() : '';
    }

    private function deleteAttachment(User $user, Course $course, int $eventId, int $attachmentId): void
    {
        $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            'DELETE',
            '/api/calendar-events/'.$eventId.'/attachments/'.$attachmentId.'?cid='.$course->getId(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function eventInAgenda(User $user, Course $course, string $title): array
    {
        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            'GET',
            '/api/c_calendar_events?cid='.$course->getId(),
            ['headers' => ['Accept' => 'application/json']],
        );

        foreach ($response->toArray() as $event) {
            if ($title === $event['title']) {
                return $event;
            }
        }

        $this->fail('The event "'.$title.'" is not in the agenda.');
    }
}
