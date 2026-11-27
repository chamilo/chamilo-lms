<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Security;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Helpers\PermissionHelper;
use Chamilo\CoreBundle\Helpers\SessionVisibilityHelper;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\TrackECourseAccessRepository;
use Chamilo\CoreBundle\Security\LoginRedirectResolver;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

final class LoginRedirectResolverTest extends TestCase
{
    /**
     * An administrator writes this setting, but the result goes into a Location header:
     * only a local path of the user's own profile may come out, else the home page.
     *
     * @dataProvider redirectProvider
     */
    public function testOnlyConfiguredLocalProfileDestinationsAreUsed(array $roles, mixed $setting, string $expected): void
    {
        $user = (new User())->setRoles($roles);

        self::assertSame($expected, $this->createResolver($setting, 'false', [])->resolve($user));
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

        yield 'missing configuration' => [['ROLE_STUDENT'], null, '/'];

        yield 'invalid JSON' => [['ROLE_STUDENT'], '{', '/'];

        yield 'invalid value type' => [['ROLE_STUDENT'], '{"STUDENT":["sessions"]}', '/'];

        yield 'no profile configured' => [['ROLE_STUDENT'], '{"ADMIN":"admin"}', '/'];

        yield 'user without a profile role' => [['ROLE_USER'], '{"STUDENT":"sessions"}', '/'];

        yield 'absolute URL' => [['ROLE_STUDENT'], '{"STUDENT":"https://example.invalid"}', '/'];

        yield 'network-path reference' => [['ROLE_STUDENT'], '{"STUDENT":"//example.invalid"}', '/'];

        yield 'backslash reference' => [['ROLE_STUDENT'], json_encode(['STUDENT' => '/\example.invalid']), '/'];

        yield 'encoded authority' => [['ROLE_STUDENT'], '{"STUDENT":"/%2fexample.invalid"}', '/'];

        yield 'header injection' => [['ROLE_STUDENT'], json_encode(['STUDENT' => "/sessions\r\nLocation: //example.invalid"]), '/'];

        yield 'encoded control' => [['ROLE_STUDENT'], '{"STUDENT":"/sessions%0d%0a"}', '/'];
    }

    /**
     * go_to_course_after_login sends a user with exactly one course straight into it,
     * before the profile destination, as the password login always did.
     */
    public function testTheOnlyCourseTakesPrecedenceOverTheProfileDestination(): void
    {
        $user = (new User())->setRoles(['ROLE_STUDENT']);
        $resolver = $this->createResolver('{"STUDENT":"sessions"}', 'true', [['cid' => 7, 'sid' => 3]]);

        self::assertSame('/course/7/home?sid=3', $resolver->resolve($user));
    }

    /**
     * With several courses there is no single course to enter: the profile destination applies.
     */
    public function testSeveralCoursesFallBackToTheProfileDestination(): void
    {
        $user = (new User())->setRoles(['ROLE_STUDENT']);
        $resolver = $this->createResolver('{"STUDENT":"sessions"}', 'true', [['cid' => 7, 'sid' => 0], ['cid' => 8, 'sid' => 0]]);

        self::assertSame('/sessions', $resolver->resolve($user));
    }

    private function createResolver(mixed $redirectSetting, string $goToCourse, array $courses): LoginRedirectResolver
    {
        $settings = $this->createMock(SettingsManager::class);
        $settings->method('getSetting')->willReturnMap([
            ['registration.redirect_after_login', true, $redirectSetting],
            ['workflows.go_to_course_after_login', false, $goToCourse],
        ]);

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturnCallback(
            static fn (string $name, array $parameters = []): string => 'index' === $name
                ? '/'
                : '/course/'.$parameters['cid'].'/home?sid='.$parameters['sid']
        );

        $courseRepository = $this->createMock(CourseRepository::class);
        $courseRepository->method('getPersonalSessionCourses')->willReturn($courses);

        // Both helpers are readonly classes PHPUnit 9 cannot double, so they are built for real.
        // Under the CLI SAPI the current access URL is the first one.
        $accessUrlRepository = $this->createMock(AccessUrlRepository::class);
        $accessUrlRepository->method('getFirstId')->willReturn(1);
        $accessUrlRepository->method('find')->willReturn(new AccessUrl());

        $security = $this->createMock(Security::class);

        return new LoginRedirectResolver(
            $settings,
            $router,
            $courseRepository,
            new AccessUrlHelper($accessUrlRepository, new RequestStack()),
            new IsAllowedToEditHelper(
                $settings,
                $security,
                new RequestStack(),
                $this->createMock(CidReqHelper::class),
                new SessionVisibilityHelper($this->createMock(TrackECourseAccessRepository::class), $settings),
                $this->createMock(StudentViewHelper::class),
                new UserHelper($security),
                $this->createMock(ExtraFieldValuesRepository::class),
                $this->createMock(PermissionHelper::class),
            ),
        );
    }
}
