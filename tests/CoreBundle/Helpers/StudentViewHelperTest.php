<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * StudentViewHelper is the only sanctioned reader of the student view state.
 *
 * The state lives in the `studentview` session key, written once per request by
 * LegacyListener (which honours course.student_view_enabled) and by the
 * /toggle_student_view endpoint. These cases lock the two properties every
 * caller depends on: the state is a plain platform-wide flag — never per
 * course — and turning the setting off hides it, so a stale session key cannot
 * keep a whole tool read-only after an administrator disabled the feature.
 */
final class StudentViewHelperTest extends TestCase
{
    public function testStudentViewKeyMeansActive(): void
    {
        self::assertTrue($this->helper('studentview')->isActive());
    }

    public function testTeacherViewKeyMeansInactive(): void
    {
        self::assertFalse($this->helper('teacherview')->isActive());
    }

    public function testAnyOtherValueMeansInactive(): void
    {
        // The listener normalizes to exactly two values; anything else is not a
        // state this helper invented a meaning for.
        self::assertFalse($this->helper('1')->isActive());
    }

    public function testMissingKeyMeansInactive(): void
    {
        self::assertFalse($this->helper(null)->isActive());
    }

    public function testDisabledSettingHidesAStaleSessionKey(): void
    {
        // The setting is the kill switch. Reading the session without it would
        // leave every tool read-only for users whose session still carries the
        // key from before an administrator turned the feature off.
        self::assertFalse($this->helper('studentview', enabled: false)->isActive());
    }

    public function testRequestWithoutSessionMeansInactive(): void
    {
        // CLI commands and messenger workers have no session; failing open here
        // would be wrong, but so would crashing.
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/'));

        self::assertFalse($this->helperWith($requestStack)->isActive());
    }

    public function testNoRequestMeansInactive(): void
    {
        self::assertFalse($this->helperWith(new RequestStack())->isActive());
    }

    private function helper(?string $studentView, bool $enabled = true): StudentViewHelper
    {
        $request = Request::create('/');
        $session = new Session(new MockArraySessionStorage());

        if (null !== $studentView) {
            $session->set('studentview', $studentView);
        }

        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $this->helperWith($requestStack, $enabled);
    }

    private function helperWith(RequestStack $requestStack, bool $enabled = true): StudentViewHelper
    {
        $settingsManager = $this->createMock(SettingsManager::class);
        $settingsManager
            ->method('getSetting')
            ->with('course.student_view_enabled')
            ->willReturn($enabled ? 'true' : 'false')
        ;

        return new StudentViewHelper($requestStack, $settingsManager);
    }
}
