<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Mcp\ManageCourseToolVisibilityTool;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use Mcp\Exception\ToolCallException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class ManageCourseToolVisibilityToolTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testListExcludesStructuralRowsAndReflectsDefaultVisibility(): void
    {
        self::bootKernel();
        $course = $this->createCourse('CTHV list '.uniqid());
        $this->authenticateAsTeacherOf($course);

        $result = $this->tool()->listCourseTools((int) $course->getId());

        self::assertSame('base_course', $result['scope']);
        self::assertNotEmpty(
            $result['tools'],
            'A freshly created course ships with a default tool set (ToolChain::addToolsInCourse); an empty list means the query is wrong.'
        );

        $names = array_column($result['tools'], 'name');
        self::assertNotContains(
            'course_tool',
            $names,
            'course_tool is the structural row for the course-home tool itself, not a card a student would see; it must never be offered as a toggle target.'
        );
        self::assertNotContains(
            'course_homepage',
            $names,
            'course_homepage is a structural row, not a real course-home tool card.'
        );

        $document = $this->findTool($result['tools'], 'document');
        self::assertNotNull($document, 'The document tool is part of the default tool set and must be listed.');
        self::assertArrayHasKey('visible', $document);
        self::assertArrayHasKey('visibility', $document);
    }

    public function testHidingAToolMakesItInvisibleAndIsIdempotent(): void
    {
        self::bootKernel();
        $course = $this->createCourse('CTHV hide '.uniqid());
        $this->authenticateAsTeacherOf($course);
        $tool = $this->tool();
        $courseId = (int) $course->getId();

        $result = $tool->setCourseToolVisibility($courseId, false, 'document');

        self::assertTrue($result['updated']);
        self::assertFalse(
            $result['tool']['visible'],
            'Hiding a tool must flip its published ResourceLink to draft, matching the course-home eye button, or a student would still see it.'
        );
        self::assertSame(ResourceLink::VISIBILITY_DRAFT, $result['tool']['visibility']);

        $listing = $tool->listCourseTools($courseId);
        $document = $this->findTool($listing['tools'], 'document');
        self::assertNotNull($document);
        self::assertFalse(
            $document['visible'],
            'list_course_tools must read back the same persisted state that set_course_tool_visibility just wrote.'
        );

        $repeat = $tool->setCourseToolVisibility($courseId, false, 'document');
        self::assertFalse(
            $repeat['updated'],
            'Re-hiding an already-hidden tool must report updated=false instead of throwing, so an agent can assert a desired end state idempotently.'
        );
    }

    public function testShowingAToolMakesItVisibleAgain(): void
    {
        self::bootKernel();
        $course = $this->createCourse('CTHV show '.uniqid());
        $this->authenticateAsTeacherOf($course);
        $tool = $this->tool();
        $courseId = (int) $course->getId();

        $tool->setCourseToolVisibility($courseId, false, 'document');
        $result = $tool->setCourseToolVisibility($courseId, true, 'document');

        self::assertTrue($result['updated']);
        self::assertTrue($result['tool']['visible']);
        self::assertSame(ResourceLink::VISIBILITY_PUBLISHED, $result['tool']['visibility']);
    }

    public function testUnknownToolNameIsRejected(): void
    {
        self::bootKernel();
        $course = $this->createCourse('CTHV unknown '.uniqid());
        $this->authenticateAsTeacherOf($course);

        $this->expectException(ToolCallException::class);
        $this->tool()->setCourseToolVisibility((int) $course->getId(), true, 'not_a_real_tool_name');
    }

    public function testMissingToolLocatorIsRejected(): void
    {
        self::bootKernel();
        $course = $this->createCourse('CTHV missing locator '.uniqid());
        $this->authenticateAsTeacherOf($course);

        $this->expectException(ToolCallException::class);
        $this->tool()->setCourseToolVisibility((int) $course->getId(), true);
    }

    public function testNonTeacherIsDenied(): void
    {
        self::bootKernel();
        $course = $this->createCourse('CTHV student '.uniqid());
        $student = $this->createUser('cthv_student_'.uniqid('', true), '', '', 'ROLE_STUDENT');

        self::getContainer()->get(TokenStorageInterface::class)->setToken(
            new UsernamePasswordToken($student, 'api', $student->getRoles())
        );

        $this->expectException(ToolCallException::class);
        $this->tool()->listCourseTools((int) $course->getId());
    }

    public function testSessionVisibilityFollowsThePlatformSettingAndDoesNotTouchTheBaseCourseLink(): void
    {
        self::bootKernel();
        $course = $this->createCourse('CTHV session '.uniqid());
        $session = $this->createSession('CTHV session '.uniqid());

        $em = $this->getEntityManager();
        $session->addCourse($course);
        $em->persist($session);
        $em->flush();

        $this->authenticateAsTeacherOf($course);
        $tool = $this->tool();
        $courseId = (int) $course->getId();
        $sessionId = (int) $session->getId();

        /** @var SettingsManager $settingsManager */
        $settingsManager = self::getContainer()->get(SettingsManager::class);
        $allowedInSession = (bool) $settingsManager->getSetting('session.allow_edit_tool_visibility_in_session');

        if (!$allowedInSession) {
            $this->expectException(ToolCallException::class);
            $tool->setCourseToolVisibility($courseId, false, 'document', null, $sessionId);

            return;
        }

        $result = $tool->setCourseToolVisibility($courseId, false, 'document', null, $sessionId);
        self::assertTrue($result['updated']);
        self::assertSame('session', $result['tool']['visibility_source']);
        self::assertFalse($result['tool']['visible']);

        $baseListing = $tool->listCourseTools($courseId);
        $baseDocument = $this->findTool($baseListing['tools'], 'document');
        self::assertNotNull($baseDocument);
        self::assertTrue(
            $baseDocument['visible'],
            'A session-scoped visibility override must not affect the base-course ResourceLink other contexts read.'
        );

        $sessionListing = $tool->listCourseTools($courseId, $sessionId);
        $sessionDocument = $this->findTool($sessionListing['tools'], 'document');
        self::assertNotNull($sessionDocument);
        self::assertFalse($sessionDocument['visible']);
        self::assertSame('session', $sessionDocument['visibility_source']);
    }

    public function testInvalidSessionCourseContextIsRejected(): void
    {
        self::bootKernel();
        $course = $this->createCourse('CTHV invalid session '.uniqid());
        $unrelatedSession = $this->createSession('CTHV unrelated session '.uniqid());
        $this->authenticateAsTeacherOf($course);

        $this->expectException(ToolCallException::class);
        $this->tool()->listCourseTools((int) $course->getId(), (int) $unrelatedSession->getId());
    }

    private function tool(): ManageCourseToolVisibilityTool
    {
        return self::getContainer()->get(ManageCourseToolVisibilityTool::class);
    }

    private function authenticateAsTeacherOf(Course $course): User
    {
        $teacher = $this->createUser('cthv_teacher_'.uniqid('', true), '', '', 'ROLE_TEACHER');

        $em = $this->getEntityManager();
        $subscription = (new CourseRelUser())
            ->setCourse($course)
            ->setUser($teacher)
            ->setStatus(CourseRelUser::TEACHER)
            ->setRelationType(0)
        ;
        $em->persist($subscription);
        $em->flush();

        self::getContainer()->get(TokenStorageInterface::class)->setToken(
            new UsernamePasswordToken($teacher, 'api', $teacher->getRoles())
        );

        return $teacher;
    }

    /**
     * @param list<array<string, mixed>> $tools
     */
    private function findTool(array $tools, string $name): ?array
    {
        foreach ($tools as $entry) {
            if ($name === $entry['name']) {
                return $entry;
            }
        }

        return null;
    }
}
