<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\HRM;

use Chamilo\CoreBundle\Controller\SecurityController;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\EventSubscriber\LoginSuccessSubscriber;
use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class HrmNamingCompatibilityTest extends TestCase
{
    public function testHrmAliasesPreservePersistedValues(): void
    {
        self::assertSame(4, HRM);
        self::assertSame(HRM, DRH);
        self::assertSame(ChamiloHelper::HRM, ChamiloHelper::DRH);
        self::assertSame(1, COURSE_RELATION_TYPE_HRM);
        self::assertSame(COURSE_RELATION_TYPE_HRM, COURSE_RELATION_TYPE_RRHH);
        self::assertSame(Session::HRM, Session::DRH);
        self::assertSame(7, UserRelUser::USER_RELATION_TYPE_HRM);
        self::assertSame(UserRelUser::USER_RELATION_TYPE_HRM, UserRelUser::USER_RELATION_TYPE_RRHH);
        self::assertSame('ROLE_HR', User::getRoleFromStatus(HRM));
        self::assertSame('ROLE_HR', User::getRoleFromStatus(DRH));
    }

    public function testLegacyGlobalHrmHelpersRemainAvailable(): void
    {
        self::assertTrue(\function_exists('api_is_hrm'));
        self::assertTrue(\function_exists('api_is_drh'));
        self::assertTrue(\function_exists('api_hrm_can_access_all_session_content'));
        self::assertTrue(\function_exists('api_drh_can_access_all_session_content'));
    }

    public function testHrmSessionAccessorPreservesLegacyAlias(): void
    {
        $user = new User();

        self::assertSame($user->getHRMSessions(), $user->getDRHSessions());
    }

    public function testLegacyRepositoryRelationCheckDelegatesToHrmMethod(): void
    {
        $repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isUserFollowedByHrm'])
            ->getMock()
        ;
        $repository->expects(self::once())
            ->method('isUserFollowedByHrm')
            ->with(10, 20)
            ->willReturn(true)
        ;

        self::assertTrue($repository->isUserFollowedByDrh(10, 20));
    }

    public function testLegacyRepositoryHrmListDelegatesToCanonicalMethod(): void
    {
        $repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getHrmListFromUser'])
            ->getMock()
        ;
        $repository->expects(self::once())
            ->method('getHrmListFromUser')
            ->with(10, 1)
            ->willReturn([['id' => 20]])
        ;

        self::assertSame([['id' => 20]], $repository->getDrhListFromUser(10, 1));
    }

    /**
     * A login with ROLE_HR must use the existing HR manager setting key.
     */
    public function testHrmLoginUsesExistingAutosubscribeSetting(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_HR']);

        $subscriber = (new ReflectionClass(LoginSuccessSubscriber::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(LoginSuccessSubscriber::class, 'getAutosubscribeSettingName');

        self::assertSame('registration.hrm_autosubscribe', $method->invoke($subscriber, $user));
    }

    public function testOldRoleAliasStillUsesHrmAutosubscribeSetting(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_DRH']);
        $subscriber = (new ReflectionClass(LoginSuccessSubscriber::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(LoginSuccessSubscriber::class, 'getAutosubscribeSettingName');

        self::assertSame('registration.hrm_autosubscribe', $method->invoke($subscriber, $user));
    }

    public function testStudentBossStillUsesExistingHrmAutosubscribeSetting(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_STUDENT_BOSS']);
        $subscriber = (new ReflectionClass(LoginSuccessSubscriber::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(LoginSuccessSubscriber::class, 'getAutosubscribeSettingName');

        self::assertSame('registration.hrm_autosubscribe', $method->invoke($subscriber, $user));
    }

    /**
     * Fresh installations may use HRM, while old installations still store DRH.
     * Explicit HRM values (including empty strings) take precedence.
     */
    public function testHrmLoginRedirectPrefersCanonicalKey(): void
    {
        $settingsManager = $this->createMock(SettingsManager::class);
        $settingsManager->method('getSetting')
            ->with('registration.redirect_after_login')
            ->willReturn('{"HRM":"/reporting/learners","DRH":"/legacy"}')
        ;

        $controller = (new ReflectionClass(SecurityController::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty(SecurityController::class, 'settingsManager'))->setValue($controller, $settingsManager);
        $method = new ReflectionMethod(SecurityController::class, 'getRedirectAfterLoginPath');

        $user = new User();
        $user->setRoles(['ROLE_HR']);

        self::assertSame('/reporting/learners', $method->invoke($controller, $user));
    }

    public function testHrmLoginRedirectHonorsExplicitEmptyCanonicalKey(): void
    {
        $settingsManager = $this->createMock(SettingsManager::class);
        $settingsManager->method('getSetting')
            ->with('registration.redirect_after_login')
            ->willReturn('{"HRM":"","DRH":"/reporting"}')
        ;

        $controller = (new ReflectionClass(SecurityController::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty(SecurityController::class, 'settingsManager'))->setValue($controller, $settingsManager);
        $method = new ReflectionMethod(SecurityController::class, 'getRedirectAfterLoginPath');

        $user = new User();
        $user->setRoles(['ROLE_HR']);

        self::assertSame('/home', $method->invoke($controller, $user));
    }

    public function testHrmLoginRedirectUsesLegacyPersistedKey(): void
    {
        $settingsManager = $this->createMock(SettingsManager::class);
        $settingsManager->expects(self::once())
            ->method('getSetting')
            ->with('registration.redirect_after_login')
            ->willReturn('{"DRH":"/reporting","STUDENT":"/courses"}')
        ;

        $controller = (new ReflectionClass(SecurityController::class))->newInstanceWithoutConstructor();
        $property = new ReflectionProperty(SecurityController::class, 'settingsManager');
        $property->setValue($controller, $settingsManager);
        $method = new ReflectionMethod(SecurityController::class, 'getRedirectAfterLoginPath');

        $user = new User();
        $user->setRoles(['ROLE_HR']);

        self::assertSame('/reporting', $method->invoke($controller, $user));
    }
}
