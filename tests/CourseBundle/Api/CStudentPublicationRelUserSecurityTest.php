<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationRelUser;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * The user restrictions of an assignment decide which learners see it. A teacher
 * of another course must not change them by sending the cid of their own course,
 * where they do hold the teacher role.
 */
final class CStudentPublicationRelUserSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testTeacherOfAnotherCourseCannotAddRestrictionWithOwnCid(): void
    {
        $ctx = $this->bootstrapScenario('post_cross');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacherA']))->request(
            'POST',
            '/api/c_student_publication_rel_users?cid='.$ctx['courseA']->getId(),
            [
                'json' => [
                    'publication' => '/api/c_student_publications/'.$ctx['assignmentB']->getIid(),
                    'user' => '/api/users/'.$ctx['otherStudentB']->getId(),
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(1, $this->countRestrictions($ctx['assignmentB']));
    }

    public function testTeacherOfAnotherCourseCannotRemoveRestrictionWithOwnCid(): void
    {
        $ctx = $this->bootstrapScenario('delete_cross');

        // Removing the last restriction would publish the assignment to every learner of course B.
        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacherA']))->request(
            'DELETE',
            '/api/c_student_publication_rel_users/'.$ctx['relB']->getIid().'?cid='.$ctx['courseA']->getId()
        );

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(1, $this->countRestrictions($ctx['assignmentB']));
    }

    public function testCollectionExcludesRestrictionsOfAnotherCourse(): void
    {
        $ctx = $this->bootstrapScenario('collection_cross');
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacherA']));

        $response = $client->request(
            'GET',
            '/api/c_student_publication_rel_users?cid='.$ctx['courseA']->getId()
        );

        $this->assertResponseStatusCodeSame(200);
        $ids = $this->memberIds($response->toArray());
        $this->assertContains($this->relationIri($ctx['relA']), $ids);
        $this->assertNotContains($this->relationIri($ctx['relB']), $ids);

        $response = $client->request(
            'GET',
            '/api/c_student_publication_rel_users?cid='.$ctx['courseA']->getId()
                .'&publication=/api/c_student_publications/'.$ctx['assignmentB']->getIid()
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([], $this->memberIds($response->toArray()));
    }

    public function testTeacherOfAssignmentCourseCanManageRestrictions(): void
    {
        $ctx = $this->bootstrapScenario('own_course');
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacherB']));
        $cid = '?cid='.$ctx['courseB']->getId();

        $client->request(
            'POST',
            '/api/c_student_publication_rel_users'.$cid,
            [
                'json' => [
                    'publication' => '/api/c_student_publications/'.$ctx['assignmentB']->getIid(),
                    'user' => '/api/users/'.$ctx['otherStudentB']->getId(),
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame(2, $this->countRestrictions($ctx['assignmentB']));

        $client->request('DELETE', '/api/c_student_publication_rel_users/'.$ctx['relB']->getIid().$cid);

        $this->assertResponseStatusCodeSame(204);
        $this->assertSame(1, $this->countRestrictions($ctx['assignmentB']));
    }

    /**
     * Two private courses, each with an assignment restricted to one learner.
     * The assignment of course B is created by its own teacher, so the teacher of
     * course A is neither its creator nor a teacher of its course.
     *
     * @return array{
     *     courseA: Course,
     *     courseB: Course,
     *     teacherA: User,
     *     teacherB: User,
     *     otherStudentB: User,
     *     assignmentB: CStudentPublication,
     *     relA: CStudentPublicationRelUser,
     *     relB: CStudentPublicationRelUser
     * }
     */
    private function bootstrapScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $courseA = $this->createCourse('Rel User Course A '.$suffix);
        $courseA->setVisibility(Course::REGISTERED);
        $courseB = $this->createCourse('Rel User Course B '.$suffix);
        $courseB->setVisibility(Course::REGISTERED);

        $teacherA = $this->createUser('rel_user_teacher_a_'.$suffix);
        $studentA = $this->createUser('rel_user_student_a_'.$suffix);
        $teacherB = $this->createUser('rel_user_teacher_b_'.$suffix);
        $studentB = $this->createUser('rel_user_student_b_'.$suffix);
        $otherStudentB = $this->createUser('rel_user_other_b_'.$suffix);

        $courseA->addUserAsTeacher($teacherA);
        $courseA->addUserAsStudent($studentA);
        $courseB->addUserAsTeacher($teacherB);
        $courseB->addUserAsStudent($studentB);
        $courseB->addUserAsStudent($otherStudentB);
        $courseRepo->update($courseA);
        $courseRepo->update($courseB);

        $assignmentA = $this->createAssignment($courseA, $teacherA, 'Assignment A '.$suffix);
        $assignmentB = $this->createAssignment($courseB, $teacherB, 'Assignment B '.$suffix);

        $em = $this->getEntityManager();
        $relA = (new CStudentPublicationRelUser())->setPublication($assignmentA)->setUser($studentA);
        $relB = (new CStudentPublicationRelUser())->setPublication($assignmentB)->setUser($studentB);
        $em->persist($relA);
        $em->persist($relB);
        $em->flush();

        return [
            'courseA' => $courseA,
            'courseB' => $courseB,
            'teacherA' => $teacherA,
            'teacherB' => $teacherB,
            'otherStudentB' => $otherStudentB,
            'assignmentB' => $assignmentB,
            'relA' => $relA,
            'relB' => $relB,
        ];
    }

    private function createAssignment(Course $course, User $creator, string $title): CStudentPublication
    {
        $assignment = (new CStudentPublication())
            ->setTitle($title)
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setCreator($creator)
            ->setActive(1)
            ->addCourseLink($course)
        ;

        $em = $this->getEntityManager();
        $em->persist($assignment);
        $em->flush();

        return $assignment;
    }

    private function countRestrictions(CStudentPublication $assignment): int
    {
        $em = $this->getEntityManager();
        $em->clear();

        return $em->getRepository(CStudentPublicationRelUser::class)
            ->count(['publication' => $assignment->getIid()])
        ;
    }

    /**
     * @param array<string, mixed> $collection
     *
     * @return list<string>
     */
    private function memberIds(array $collection): array
    {
        return array_column($collection['hydra:member'], '@id');
    }

    private function relationIri(CStudentPublicationRelUser $relation): string
    {
        return '/api/c_student_publication_rel_users/'.$relation->getIid();
    }
}
