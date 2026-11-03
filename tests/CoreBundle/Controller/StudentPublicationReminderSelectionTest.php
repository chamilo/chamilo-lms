<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationRelUser;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

/**
 * GH #8083: Selecting a subset must never send reminders to the remaining
 * students, or accept forged IDs for learners outside the current assignment.
 */
final class StudentPublicationReminderSelectionTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * @return array{course: Course, teacher: User, first: User, second: User, third: User, submitted: User, foreign: User, assignment: CStudentPublication}
     */
    private function createScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('GH8083 reminder '.$suffix);
        $teacher = $this->createUser('gh8083_teacher_'.$suffix);
        $first = $this->createUser('gh8083_first_'.$suffix);
        $second = $this->createUser('gh8083_second_'.$suffix);
        $third = $this->createUser('gh8083_third_'.$suffix);
        $submitted = $this->createUser('gh8083_submitted_'.$suffix);
        $foreign = $this->createUser('gh8083_foreign_'.$suffix);

        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($first);
        $course->addUserAsStudent($second);
        $course->addUserAsStudent($third);
        $course->addUserAsStudent($submitted);
        $courseRepo->update($course);

        $assignment = (new CStudentPublication())
            ->setTitle('GH8083 Assignment '.$suffix)
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setCreator($teacher)
            ->setUser($teacher)
            ->setActive(1)
            ->addCourseLink($course)
        ;
        $em = $this->getEntityManager();
        $em->persist($assignment);

        $submission = (new CStudentPublication())
            ->setTitle('GH8083 Submitted work '.$suffix)
            ->setDescription('Completed assignment')
            ->setPublicationParent($assignment)
            ->setParent($assignment)
            ->setFiletype('file')
            ->setWeight(100)
            ->setCreator($submitted)
            ->setUser($submitted)
            ->setActive(1)
            ->setAccepted(true)
            ->setSentDate(new DateTime())
            ->setQualification(0.0)
            ->setQualificatorId(0)
            ->addCourseLink($course)
        ;
        $em->persist($submission);
        $em->flush();

        return compact('course', 'teacher', 'first', 'second', 'third', 'submitted', 'foreign', 'assignment');
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function send(array $context, User $sender, array $payload): array
    {
        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($sender))->request(
            'POST',
            '/assignments/'.$context['assignment']->getIid().'/unsubmitted-users/email?cid='.$context['course']->getId(),
            ['json' => $payload]
        );

        return $response->toArray(false);
    }

    private function countReceived(User $user): int
    {
        $this->getEntityManager()->clear();

        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from(Message::class, 'm')
            ->innerJoin('m.receivers', 'r')
            ->where('r.receiver = :user')
            ->setParameter('user', (int) $user->getId())
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function testOnlySelectedUnsubmittedStudentsReceiveReminders(): void
    {
        $ctx = $this->createScenario('selected');
        $result = $this->send($ctx, $ctx['teacher'], [
            'userIds' => [$ctx['first']->getId(), $ctx['second']->getId(), $ctx['second']->getId()],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(2, $result['sent']);
        $this->assertSame(1, $this->countReceived($ctx['first']));
        $this->assertSame(1, $this->countReceived($ctx['second']));
        $this->assertSame(0, $this->countReceived($ctx['third']));
        $this->assertSame(0, $this->countReceived($ctx['submitted']));
        $this->assertSame(0, $this->countReceived($ctx['foreign']));
    }

    public function testLegacyEmptyPayloadStillSendsToAllUnsubmittedStudents(): void
    {
        $ctx = $this->createScenario('all');
        $result = $this->send($ctx, $ctx['teacher'], []);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(3, $result['sent']);
        $this->assertSame(1, $this->countReceived($ctx['first']));
        $this->assertSame(1, $this->countReceived($ctx['second']));
        $this->assertSame(1, $this->countReceived($ctx['third']));
        $this->assertSame(0, $this->countReceived($ctx['submitted']));
    }

    public function testAnEmptySelectionCannotFallBackToSendingEveryone(): void
    {
        $ctx = $this->createScenario('empty');
        $this->send($ctx, $ctx['teacher'], ['userIds' => []]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(0, $this->countReceived($ctx['first']));
        $this->assertSame(0, $this->countReceived($ctx['second']));
    }

    public function testMalformedSelectionCannotBecomeAnUnrestrictedSend(): void
    {
        $ctx = $this->createScenario('malformed');
        $this->send($ctx, $ctx['teacher'], ['userIds' => 'all']);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(0, $this->countReceived($ctx['first']));
    }

    public function testUnsubmittedUserListMatchesEligibleRecipients(): void
    {
        $ctx = $this->createScenario('list');
        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'GET',
            '/assignments/'.$ctx['assignment']->getIid().'/unsubmitted-users?cid='.$ctx['course']->getId(),
        );

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray(false);
        $this->assertSame(3, $data['hydra:totalItems']);
        $ids = array_map(static fn (array $user): int => (int) $user['id'], $data['hydra:member']);
        $this->assertEqualsCanonicalizing([
            $ctx['first']->getId(), $ctx['second']->getId(), $ctx['third']->getId(),
        ], $ids);
    }

    public function testInvalidSelectedIdsRejectEntireBatch(): void
    {
        $ctx = $this->createScenario('forged');
        $this->send($ctx, $ctx['teacher'], [
            'userIds' => [$ctx['first']->getId(), $ctx['foreign']->getId()],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(0, $this->countReceived($ctx['first']));
        $this->assertSame(0, $this->countReceived($ctx['foreign']));
    }

    public function testSubmittedStudentsAreNoLongerEligible(): void
    {
        $ctx = $this->createScenario('submitted');
        $this->send($ctx, $ctx['teacher'], [
            'userIds' => [$ctx['first']->getId(), $ctx['submitted']->getId()],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(0, $this->countReceived($ctx['first']));
    }

    public function testStudentCannotSendReminders(): void
    {
        $ctx = $this->createScenario('denied');
        $this->send($ctx, $ctx['first'], ['userIds' => [$ctx['second']->getId()]]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(0, $this->countReceived($ctx['second']));
    }

    public function testAssignedUserRestrictionIsEnforced(): void
    {
        $ctx = $this->createScenario('assigned');
        $relation = (new CStudentPublicationRelUser())
            ->setPublication($ctx['assignment'])
            ->setUser($ctx['first'])
        ;
        $em = $this->getEntityManager();
        $em->persist($relation);
        $em->flush();

        $this->send($ctx, $ctx['teacher'], ['userIds' => [$ctx['second']->getId()]]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(0, $this->countReceived($ctx['second']));
    }

    public function testAssignmentCannotBeUsedWithAnotherCourseContext(): void
    {
        $ctx = $this->createScenario('crosscourse');

        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $otherCourse = $this->createCourse('GH8083 Other course');
        $otherCourse->addUserAsTeacher($ctx['teacher']);
        $courseRepo->update($otherCourse);

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'POST',
            '/assignments/'.$ctx['assignment']->getIid().'/unsubmitted-users/email?cid='.$otherCourse->getId(),
            ['json' => ['userIds' => [$ctx['first']->getId()]]]
        );

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame(0, $this->countReceived($ctx['first']));
    }
}
