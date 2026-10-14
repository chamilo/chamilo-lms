<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CToolIntroHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
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
 * now requires the teacher role and that the introduction's own course (and session) match
 * the request, so neither the creator shortcut of the voter nor a role from another course
 * is enough.
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

    /**
     * Adds a session of the given course with its own introduction, and a session coach.
     *
     * @return array{session: Session, coach: User, intro: CToolIntro}
     */
    private function createSessionIntro(Course $course, string $suffix): array
    {
        $coach = $this->createUser('intro_sec_coach_'.$suffix);

        $session = $this->createSession('Intro Sec Session '.$suffix);
        $session->addCourse($course);
        $session->addGeneralCoach($coach);
        self::getContainer()->get(SessionRepository::class)->update($session);

        $courseTool = self::getContainer()->get(CToolIntroHelper::class)
            ->getOrCreateCourseTool($course, 'course_homepage', $session)
        ;
        $this->assertNotNull($courseTool);

        $intro = (new CToolIntro())
            ->setIntroText('Original session intro')
            ->setCourseTool($courseTool)
            ->setParent($course)
            ->setCreator($this->createUser('intro_sec_session_creator_'.$suffix))
            ->addCourseLink($course, $session)
        ;
        $em = $this->getEntityManager();
        $em->persist($intro);
        $em->flush();

        return ['session' => $session, 'coach' => $coach, 'intro' => $intro];
    }

    private function patchSessionIntro(User $user, CToolIntro $intro, Course $course, Session $session, string $text): void
    {
        $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            'PATCH',
            '/api/c_tool_intros/'.$intro->getIid().'?cid='.$course->getId().'&sid='.$session->getId(),
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

    public function testFormerTeacherCreatorCannotPatchIntroOfTheCourseTheyLeft(): void
    {
        $ctx = $this->bootstrapScenario('former_own_cid');
        $creator = $this->getEntityManager()->getRepository(User::class)->findOneBy(['username' => 'intro_sec_creator_former_own_cid']);

        $this->patchIntro($creator, $ctx['intro'], $ctx['courseB'], 'Edited by former teacher');

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame('Original intro', $this->freshIntroText($ctx['intro']));
    }

    public function testFormerTeacherCreatorCannotPatchIntroUsingTheCidOfAnotherCourse(): void
    {
        $ctx = $this->bootstrapScenario('former_other_cid');
        $creator = $this->getEntityManager()->getRepository(User::class)->findOneBy(['username' => 'intro_sec_creator_former_other_cid']);
        $courseA = $ctx['courseA'];
        $courseA->addUserAsTeacher($creator);
        self::getContainer()->get(CourseRepository::class)->update($courseA);

        $this->patchIntro($creator, $ctx['intro'], $courseA, 'Edited by former teacher');

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame('Original intro', $this->freshIntroText($ctx['intro']));
    }

    public function testSessionCoachCanPatchTheIntroOfTheirSession(): void
    {
        $ctx = $this->bootstrapScenario('session_own');
        $sessionCtx = $this->createSessionIntro($ctx['courseB'], 'session_own');

        $this->patchSessionIntro($sessionCtx['coach'], $sessionCtx['intro'], $ctx['courseB'], $sessionCtx['session'], 'Edited by coach');

        $this->assertResponseIsSuccessful();
        $this->assertSame('Edited by coach', $this->freshIntroText($sessionCtx['intro']));
    }

    public function testSessionCoachCannotPatchTheIntroOfAnotherSessionOfTheSameCourse(): void
    {
        $ctx = $this->bootstrapScenario('session_other');
        $mine = $this->createSessionIntro($ctx['courseB'], 'session_other_mine');
        $other = $this->createSessionIntro($ctx['courseB'], 'session_other_theirs');

        // The coach holds the session teacher role for their own session, and names it in sid.
        $this->patchSessionIntro($mine['coach'], $other['intro'], $ctx['courseB'], $mine['session'], 'Edited by wrong coach');

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame('Original session intro', $this->freshIntroText($other['intro']));
    }

    public function testSessionCoachCannotPatchTheBaseCourseIntro(): void
    {
        $ctx = $this->bootstrapScenario('session_coach_base');
        $sessionCtx = $this->createSessionIntro($ctx['courseB'], 'session_coach_base');

        // The coach only holds the session teacher role, which must not reach the base course intro.
        $this->patchSessionIntro($sessionCtx['coach'], $ctx['intro'], $ctx['courseB'], $sessionCtx['session'], 'Edited by coach');

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame('Original intro', $this->freshIntroText($ctx['intro']));
    }
}
