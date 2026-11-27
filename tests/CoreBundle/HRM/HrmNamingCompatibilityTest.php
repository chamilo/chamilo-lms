<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\HRM;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\EventSubscriber\LoginSuccessSubscriber;
use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

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
}
