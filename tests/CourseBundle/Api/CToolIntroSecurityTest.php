<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * Regression tests for the CToolIntro PUT/PATCH advisory.
 *
 * The contextual teacher role describes the course named by `cid`, not the course the
 * edited introduction belongs to. A teacher of course A could therefore overwrite the
 * introduction of course B by sending PATCH /api/c_tool_intros/{iid}?cid=A. Authorization
 * now goes through the EDIT permission on the introduction's own resource node.
 */
final class CToolIntroSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * Builds two private courses, each with its own teacher, plus a student of course B,
     * and one introduction in course B. The introduction's creator is a separate user so
     * that the voter's "creator" shortcut never explains an allowed request.
     *
     * @return array{
     *     courseA: Course,
     *     courseB: Course,
     *     teacherA: User,
     *     teacherB: User,
     *     studentB: User,
     *     intro: CToolIntro
     * }
     */
    private function bootstrapScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $courseA = $this->createCourse('Intro Sec A '.$suffix);
        $courseA->setVisibility(Course::REGISTERED);
        $courseB = $this->createCourse('Intro Sec B '.$suffix);
        $courseB->setVisibility(Course::REGISTERED);

        $teacherA = $this->createUser('intro_sec_teacher_a_'.$suffix);
        $teacherB = $this->createUser('intro_sec_teacher_b_'.$suffix);
        $studentB = $this->createUser('intro_sec_student_b_'.$suffix);
        $creator = $this->createUser('intro_sec_creator_'.$suffix);

        $courseA->addUserAsTeacher($teacherA);
        $courseB->addUserAsTeacher($teacherB);
        $courseB->addUserAsStudent($studentB);
        $courseRepo->update($courseA);
        $courseRepo->update($courseB);

        /** @var CTool $courseTool */
        $courseTool = $courseB->getTools()->first();

        $intro = (new CToolIntro())
            ->setIntroText('Original intro')
            ->setCourseTool($courseTool)
            ->setParent($courseB)
            ->setCreator($creator)
            ->addCourseLink($courseB)
        ;
        $em = $this->getEntityManager();
        $em->persist($intro);
        $em->flush();

        return [
            'courseA' => $courseA,
            'courseB' => $courseB,
            'teacherA' => $teacherA,
            'teacherB' => $teacherB,
            'studentB' => $studentB,
            'intro' => $intro,
        ];
    }

    private function patchIntro(User $user, CToolIntro $intro, Course $requestCourse, string $text): void
    {
        $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            'PATCH',
            '/api/c_tool_intros/'.$intro->getIid().'?cid='.$requestCourse->getId(),
            [
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
                'json' => ['introText' => $text],
            ]
        );
    }

    private function freshIntroText(CToolIntro $intro): ?string
    {
        $em = $this->getEntityManager();
        $em->clear();

        return $em->getRepository(CToolIntro::class)->find($intro->getIid())?->getIntroText();
    }

    public function testTeacherOfAnotherCourseCannotPatchIntroUsingOwnCid(): void
    {
        $ctx = $this->bootstrapScenario('foreign_teacher');

        $this->patchIntro($ctx['teacherA'], $ctx['intro'], $ctx['courseA'], 'Overwritten by teacher A');

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame('Original intro', $this->freshIntroText($ctx['intro']));
    }

    public function testTeacherOfTheIntroCourseCanPatchIt(): void
    {
        $ctx = $this->bootstrapScenario('own_teacher');

        $this->patchIntro($ctx['teacherB'], $ctx['intro'], $ctx['courseB'], 'Edited by teacher B');

        $this->assertResponseIsSuccessful();
        $this->assertSame('Edited by teacher B', $this->freshIntroText($ctx['intro']));
    }

    public function testStudentOfTheIntroCourseCannotPatchIt(): void
    {
        $ctx = $this->bootstrapScenario('own_student');

        $this->patchIntro($ctx['studentB'], $ctx['intro'], $ctx['courseB'], 'Edited by student B');

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame('Original intro', $this->freshIntroText($ctx['intro']));
    }
}
