<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\LoginRedirectPathResolver;
use Generator;
use PHPUnit\Framework\TestCase;

final class LoginRedirectPathResolverTest extends TestCase
{
    /**
     * @dataProvider redirectProvider
     */
    public function testOnlyConfiguredLocalProfileDestinationsAreUsed(array $roles, mixed $setting, string $expected): void
    {
        $user = (new User())->setRoles($roles);

        self::assertSame($expected, LoginRedirectPathResolver::resolve($user, $setting, '/'));
    }

    public static function redirectProvider(): Generator
    {
        yield 'student' => [['ROLE_STUDENT'], '{"STUDENT":"sessions"}', '/sessions'];

        yield 'teacher takes precedence over student' => [['ROLE_TEACHER', 'ROLE_STUDENT'], '{"STUDENT":"sessions","COURSEMANAGER":"courses"}', '/courses'];

        yield 'admin takes precedence over teacher' => [['ROLE_ADMIN', 'ROLE_TEACHER'], '{"ADMIN":"admin","COURSEMANAGER":"courses"}', '/admin'];

        yield 'session manager' => [['ROLE_SESSION_MANAGER'], '{"SESSIONADMIN":"admin-dashboard"}', '/admin-dashboard'];

        yield 'student boss' => [['ROLE_STUDENT_BOSS'], '{"STUDENT_BOSS":"main/my_space/student.php"}', '/main/my_space/student.php'];

        yield 'invitee' => [['ROLE_INVITEE'], '{"INVITEE":"courses"}', '/courses'];

        yield 'legacy HRM key' => [['ROLE_HR'], '{"DRH":"reporting"}', '/reporting'];

        yield 'canonical HRM key' => [['ROLE_HR'], '{"HRM":"reporting/learners","DRH":"reporting"}', '/reporting/learners'];

        yield 'empty canonical HRM key' => [['ROLE_HR'], '{"HRM":"","DRH":"reporting"}', '/'];

        yield 'legacy home' => [['ROLE_STUDENT'], '{"STUDENT":"user_portal.php"}', '/home'];

        yield 'legacy courses' => [['ROLE_STUDENT'], '{"STUDENT":"/main/auth/courses.php"}', '/courses'];

        yield 'query and fragment' => [['ROLE_STUDENT'], '{"STUDENT":"sessions?page=2#current"}', '/sessions?page=2#current'];

        yield 'empty configuration' => [['ROLE_STUDENT'], '', '/'];

        yield 'invalid JSON' => [['ROLE_STUDENT'], '{', '/'];

        yield 'invalid value type' => [['ROLE_STUDENT'], '{"STUDENT":["sessions"]}', '/'];

        yield 'no profile configured' => [['ROLE_STUDENT'], '{"ADMIN":"admin"}', '/'];

        yield 'absolute URL' => [['ROLE_STUDENT'], '{"STUDENT":"https://example.invalid"}', '/'];

        yield 'network-path reference' => [['ROLE_STUDENT'], '{"STUDENT":"//example.invalid"}', '/'];

        yield 'backslash reference' => [['ROLE_STUDENT'], json_encode(['STUDENT' => '/\example.invalid']), '/'];

        yield 'encoded authority' => [['ROLE_STUDENT'], '{"STUDENT":"/%2fexample.invalid"}', '/'];

        yield 'header injection' => [['ROLE_STUDENT'], json_encode(['STUDENT' => "/sessions\r\nLocation: //example.invalid"]), '/'];

        yield 'encoded control' => [['ROLE_STUDENT'], '{"STUDENT":"/sessions%0d%0a"}', '/'];
    }
}
