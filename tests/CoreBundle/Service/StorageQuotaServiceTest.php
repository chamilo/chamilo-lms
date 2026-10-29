<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\Storage\StorageQuotaService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use UnexpectedValueException;

final class StorageQuotaServiceTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private const MB = 1048576;

    /**
     * @param array<string, string> $values
     */
    private function quotaService(array $values): StorageQuotaService
    {
        $settings = $this->createMock(SettingsManager::class);
        $settings->method('getSetting')->willReturnCallback(
            static fn (string $key): string => $values[$key] ?? '0',
        );

        return new StorageQuotaService($this->getEntityManager(), $settings);
    }

    private function newUser(): User
    {
        $user = $this->createUser('quota_2282_'.bin2hex(random_bytes(5)));
        self::assertInstanceOf(User::class, $user);

        return $user;
    }

    private function newCourse(): Course
    {
        $course = $this->createCourse('Quota 2282 '.bin2hex(random_bytes(4)));
        self::assertInstanceOf(Course::class, $course);

        return $course;
    }

    public function testPersonalQuotaIsUnlimitedByDefault(): void
    {
        $user = $this->newUser();
        $quota = $this->quotaService([]);

        $quota->assertPersonalQuota($user, 10 * self::MB);
        self::assertSame(0, $quota->personalUsedBytes((int) $user->getId()));
    }

    public function testPersonalQuotaAllowsExactlyLimit(): void
    {
        $user = $this->newUser();
        $quota = $this->quotaService(['platform.personal_files_quota_mb' => '1']);

        $quota->assertPersonalQuota($user, self::MB);
        self::assertSame(1, $quota->personalQuotaMb());
    }

    public function testPersonalQuotaRejectsExcess(): void
    {
        $user = $this->newUser();
        $quota = $this->quotaService(['platform.personal_files_quota_mb' => '1']);

        $this->expectException(BadRequestHttpException::class);
        $quota->assertPersonalQuota($user, self::MB + 1);
    }

    public function testDropboxUserQuotaIsAppliedAcrossCourses(): void
    {
        $user = $this->newUser();
        $course = $this->newCourse();
        $quota = $this->quotaService(['dropbox.dropbox_user_quota_mb' => '1']);

        $this->expectException(BadRequestHttpException::class);
        $quota->assertDropboxQuotas($user, $course, self::MB + 1);
    }

    public function testDropboxCourseQuotaIsIndependentOfPersonalQuota(): void
    {
        $user = $this->newUser();
        $course = $this->newCourse();
        $quota = $this->quotaService(['dropbox.dropbox_course_quota_mb' => '2']);
        $quota->assertPersonalQuota($user, 10 * self::MB);

        $this->expectException(BadRequestHttpException::class);
        $quota->assertDropboxQuotas($user, $course, 2 * self::MB + 1);
    }

    public function testDropboxQuotaAllowsExactlyTheConfiguredLimit(): void
    {
        $user = $this->newUser();
        $course = $this->newCourse();
        $quota = $this->quotaService([
            'dropbox.dropbox_user_quota_mb' => '1',
            'dropbox.dropbox_course_quota_mb' => '1',
        ]);

        $quota->assertDropboxQuotas($user, $course, self::MB);
        self::assertSame(1, $quota->dropboxUserQuotaMb());
        self::assertSame(1, $quota->dropboxCourseQuotaMb());
    }

    public function testInvalidQuotaSettingIsNotSilentlyUnlimited(): void
    {
        $quota = $this->quotaService(['platform.personal_files_quota_mb' => '-1']);

        $this->expectException(UnexpectedValueException::class);
        $quota->personalQuotaMb();
    }

    public function testPessimisticWriteLocksForCourseAndUser(): void
    {
        // Uploads must acquire DB locks before checking quotas, including when quotas are unlimited.
        $user = $this->newUser();
        $course = $this->newCourse();
        $quota = $this->quotaService([]);

        /** @psalm-suppress InvalidArgument Psalm cannot bind the interface template T of wrapInTransaction() on the concrete EntityManager */
        $result = $this->getEntityManager()->wrapInTransaction(
            static function () use ($quota, $course, $user): bool {
                $quota->lockCourse($course);
                $quota->lockUser($user);

                return true;
            },
        );

        self::assertTrue($result);
    }

    public function testPersonalFolderCannotBelongToAnotherUser(): void
    {
        $user = $this->newUser();
        $other = $this->newUser();
        self::assertNotNull($user->getResourceNode());
        self::assertNotNull($other->getResourceNode());

        $quota = $this->quotaService([]);
        $quota->assertPersonalParent($user, (int) $user->getResourceNode()->getId());

        $this->expectException(AccessDeniedHttpException::class);
        $quota->assertPersonalParent($user, (int) $other->getResourceNode()->getId());
    }
}
