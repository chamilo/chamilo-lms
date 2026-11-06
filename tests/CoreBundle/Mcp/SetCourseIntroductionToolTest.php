<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\CToolIntroHelper;
use Chamilo\CoreBundle\Mcp\SetCourseIntroductionTool;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Service\Document\CourseDocumentContentService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Chamilo\Tests\ChamiloTestTrait;
use Mcp\Exception\ToolCallException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class SetCourseIntroductionToolTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testCreatesTheIntroductionThenReplacesItInPlace(): void
    {
        self::bootKernel();
        $course = $this->createCourse('CI create '.uniqid());
        $this->authenticateAsTeacherOf($course);
        $courseId = (int) $course->getId();

        $created = $this->tool()->setCourseIntroduction($courseId, '<p>Welcome</p>');

        self::assertTrue($created['created']);
        self::assertSame('<p>Welcome</p>', $this->storedIntro($course)->getIntroText());

        $replaced = $this->tool()->setCourseIntroduction($courseId, '<p>Welcome back</p>');

        self::assertFalse($replaced['created'], 'A second call must edit the same introduction, not add another one.');
        self::assertTrue($replaced['changed']);
        self::assertSame($created['intro_id'], $replaced['intro_id']);
        self::assertSame('<p>Welcome back</p>', $this->storedIntro($course)->getIntroText());
    }

    public function testRepeatingTheSameContentReportsNoChange(): void
    {
        self::bootKernel();
        $course = $this->createCourse('CI repeat '.uniqid());
        $this->authenticateAsTeacherOf($course);
        $courseId = (int) $course->getId();

        $this->tool()->setCourseIntroduction($courseId, '<p>Same</p>');

        self::assertFalse($this->tool()->setCourseIntroduction($courseId, '<p>Same</p>')['changed']);
    }

    public function testAnEmptyStringClearsTheIntroduction(): void
    {
        self::bootKernel();
        $course = $this->createCourse('CI clear '.uniqid());
        $this->authenticateAsTeacherOf($course);
        $courseId = (int) $course->getId();

        $this->tool()->setCourseIntroduction($courseId, '<p>To clear</p>');
        $this->tool()->setCourseIntroduction($courseId, '');

        self::assertSame('', $this->storedIntro($course)->getIntroText());
    }

    public function testTheHtmlIsStoredAsTheCourseDocumentSanitizerReturnsIt(): void
    {
        self::bootKernel();
        $course = $this->createCourse('CI sanitize '.uniqid());
        $this->authenticateAsTeacherOf($course);
        $html = '<h2>Hello</h2><p onclick="x()">Intro</p><script>alert(1)</script>';

        $this->tool()->setCourseIntroduction((int) $course->getId(), $html);

        // Same policy as the course documents the teacher can already write: the introduction must go
        // through that sanitizer, whatever its current rules, and never be stored raw behind its back.
        /** @var CourseDocumentContentService $documents */
        $documents = self::getContainer()->get(CourseDocumentContentService::class);
        self::assertSame($documents->sanitizeHtml($html), $this->storedIntro($course)->getIntroText());
    }

    public function testSessionIntroductionSupersedesTheBaseOneWithoutChangingIt(): void
    {
        self::bootKernel();
        $course = $this->createCourse('CI session '.uniqid());
        $session = $this->linkedSession($course);
        $this->authenticateAsTeacherOf($course);
        $courseId = (int) $course->getId();
        $sessionId = (int) $session->getId();

        $this->tool()->setCourseIntroduction($courseId, '<p>Base</p>');
        $result = $this->tool()->setCourseIntroduction($courseId, '<p>Session</p>', $sessionId);

        self::assertSame('course_session', $result['scope']);
        self::assertSame($sessionId, $result['session_id']);
        self::assertTrue($result['created'], 'The session gets its own introduction instead of editing the base one.');
        self::assertSame('<p>Base</p>', $this->storedIntro($course)->getIntroText());

        /** @var CToolIntroHelper $helper */
        $helper = self::getContainer()->get(CToolIntroHelper::class);
        $em = $this->getEntityManager();
        $em->clear();
        $course = $em->getRepository(Course::class)->find($courseId);
        $session = $em->getRepository(Session::class)->find($sessionId);
        self::assertSame(
            '<p>Session</p>',
            $helper->getCurrentIntro($course, 'course_homepage', $session)->getIntroText(),
            'Inside the session, the session introduction is the one Chamilo shows.'
        );
        self::assertSame('<p>Base</p>', $helper->getCurrentIntro($course, 'course_homepage', null)->getIntroText());
    }

    public function testSessionIntroductionIsUpdatedInPlace(): void
    {
        self::bootKernel();
        $course = $this->createCourse('CI session twice '.uniqid());
        $session = $this->linkedSession($course);
        $this->authenticateAsTeacherOf($course);
        $courseId = (int) $course->getId();
        $sessionId = (int) $session->getId();

        $first = $this->tool()->setCourseIntroduction($courseId, '<p>One</p>', $sessionId);
        $second = $this->tool()->setCourseIntroduction($courseId, '<p>Two</p>', $sessionId);

        self::assertFalse($second['created']);
        self::assertSame($first['intro_id'], $second['intro_id']);
    }

    public function testASessionNotLinkedToTheCourseIsRefused(): void
    {
        self::bootKernel();
        $course = $this->createCourse('CI unlinked '.uniqid());
        $unlinked = $this->createSession('CI unlinked session '.uniqid());
        $this->authenticateAsTeacherOf($course);

        $this->expectException(ToolCallException::class);
        $this->tool()->setCourseIntroduction((int) $course->getId(), '<p>Nope</p>', (int) $unlinked->getId());
    }

    public function testASessionOfACourseLockedInsideSessionsIsRefusedWhileTheBaseCourseStaysEditable(): void
    {
        self::bootKernel();
        $course = $this->createCourse('CI locked '.uniqid());
        $session = $this->linkedSession($course);
        $this->authenticateAsTeacherOf($course);
        $em = $this->getEntityManager();

        /** @var SettingsManager $settings */
        $settings = self::getContainer()->get(SettingsManager::class);
        $settings->updateSetting('session.session_courses_read_only_mode', 'true');
        $field = $em->getRepository(ExtraField::class)->findOneBy([
            'variable' => 'session_courses_read_only_mode',
            'itemType' => ExtraField::COURSE_FIELD_TYPE,
        ]);
        self::assertNotNull($field, 'The fixtures must provide the per-course lock field.');
        self::getContainer()->get(ExtraFieldValuesRepository::class)->updateItemData($field, $course, '1');

        try {
            $this->tool()->setCourseIntroduction((int) $course->getId(), '<p>Base still fine</p>');

            $this->expectException(ToolCallException::class);
            $this->tool()->setCourseIntroduction((int) $course->getId(), '<p>Locked</p>', (int) $session->getId());
        } finally {
            $settings->updateSetting('session.session_courses_read_only_mode', 'false');
        }
    }

    private function linkedSession(Course $course): Session
    {
        $session = $this->createSession('CI linked session '.uniqid());
        $em = $this->getEntityManager();
        $course->setVisibility(Course::REGISTERED);
        $session->addCourse($course);
        $em->persist($course);
        $em->persist($session);
        $em->flush();

        return $session;
    }

    public function testATeacherOfAnotherCourseIsDenied(): void
    {
        self::bootKernel();
        $course = $this->createCourse('CI owner '.uniqid());
        $otherCourse = $this->createCourse('CI other '.uniqid());
        $this->authenticateAsTeacherOf($otherCourse);

        $this->expectException(ToolCallException::class);
        $this->tool()->setCourseIntroduction((int) $course->getId(), '<p>Nope</p>');
    }

    private function storedIntro(Course $course): CToolIntro
    {
        $em = $this->getEntityManager();
        $em->clear();

        $intros = $em->getRepository(CToolIntro::class)->findAll();
        foreach ($intros as $intro) {
            if ($intro->getCourseTool()->getCourse()->getId() === $course->getId()
                && 'course_homepage' === $intro->getCourseTool()->getTitle()
                && null === $intro->getCourseTool()->getSession()
            ) {
                return $intro;
            }
        }

        self::fail('The course has no stored homepage introduction.');
    }

    private function tool(): SetCourseIntroductionTool
    {
        return self::getContainer()->get(SetCourseIntroductionTool::class);
    }

    private function authenticateAsTeacherOf(Course $course): void
    {
        $teacher = $this->createUser('ci_teacher_'.uniqid('', true), '', '', 'ROLE_TEACHER');

        $em = $this->getEntityManager();
        $em->persist(
            (new CourseRelUser())
                ->setCourse($course)
                ->setUser($teacher)
                ->setStatus(CourseRelUser::TEACHER)
                ->setRelationType(0)
        );
        $em->flush();
        // The course the test built still carries its teacher list from before the subscription;
        // a real request loads it fresh from the database, so the tool must too.
        $em->clear();

        self::getContainer()->get(TokenStorageInterface::class)->setToken(
            new UsernamePasswordToken($teacher, 'api', $teacher->getRoles())
        );
    }
}
