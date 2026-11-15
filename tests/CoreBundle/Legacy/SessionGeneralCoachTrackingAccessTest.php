<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use PHPUnit\Framework\TestCase;
use SessionManager;

/**
 * A session general coach may open access-details tracking for their own
 * session, and only that session (issue #4981).
 *
 * The helper short-circuits on zero ids without touching the database. The
 * session-scoped grant itself is Session::hasUserAsGeneralCoach(), pinned
 * here in memory. The three tracking entry points are pinned by source so a
 * later edit cannot drop the grant or widen it to a session the coach does
 * not own.
 */
final class SessionGeneralCoachTrackingAccessTest extends TestCase
{
    public function testZeroOrNegativeIdsNeverGrantAccess(): void
    {
        $this->assertFalse(SessionManager::sessionHasGeneralCoach(0, 1));
        $this->assertFalse(SessionManager::sessionHasGeneralCoach(1, 0));
        $this->assertFalse(SessionManager::sessionHasGeneralCoach(-4, 5));
        $this->assertFalse(SessionManager::sessionHasGeneralCoach(8, -1));
    }

    public function testHasUserAsGeneralCoachIsScopedToThatUser(): void
    {
        $session = new Session();
        $coach = new User();
        $other = new User();
        $student = new User();

        $session->addGeneralCoach($coach);

        $this->assertTrue($session->hasUserAsGeneralCoach($coach));
        $this->assertFalse($session->hasUserAsGeneralCoach($other));
        $this->assertFalse($session->hasUserAsGeneralCoach($student));
    }

    public function testASecondSessionDoesNotInheritTheFirstSessionsCoach(): void
    {
        $coachA = new User();
        $coachB = new User();
        $sessionA = new Session();
        $sessionB = new Session();

        $sessionA->addGeneralCoach($coachA);
        $sessionB->addGeneralCoach($coachB);

        $this->assertTrue($sessionA->hasUserAsGeneralCoach($coachA));
        $this->assertFalse($sessionA->hasUserAsGeneralCoach($coachB));
        $this->assertTrue($sessionB->hasUserAsGeneralCoach($coachB));
        $this->assertFalse($sessionB->hasUserAsGeneralCoach($coachA));
    }

    /**
     * @dataProvider trackingPages
     *
     * @param list<string> $needles
     */
    public function testTheTrackingPagesKeepTheGeneralCoachGrant(string $file, array $needles): void
    {
        $source = (string) file_get_contents(__DIR__.'/../../../'.$file);
        $this->assertNotSame('', $source, $file.' is missing');
        foreach ($needles as $needle) {
            $this->assertStringContainsString($needle, $source, $file.' must keep: '.$needle);
        }
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function trackingPages(): array
    {
        return [
            'access_details.php is scoped to the requested session' => [
                'public/main/my_space/access_details.php',
                [
                    'SessionManager::sessionHasGeneralCoach($session_id, api_get_user_id())',
                    'Tracking::is_allowed_to_coach_student(api_get_user_id(), $user_id)',
                ],
            ],
            'access_details_session.php allows a coach of that student' => [
                'public/main/my_space/access_details_session.php',
                [
                    'Tracking::is_allowed_to_coach_student(api_get_user_id(), $userId)',
                ],
            ],
            'myspace.ajax.php prefers the requested session id' => [
                'public/main/inc/ajax/myspace.ajax.php',
                [
                    'SessionManager::sessionHasGeneralCoach($sessionIdForAuth, $currentUserId)',
                    'Tracking::is_allowed_to_coach_student($currentUserId, $studentIdForAuth)',
                    'if ($sessionIdForAuth > 0)',
                ],
            ],
        ];
    }
}
