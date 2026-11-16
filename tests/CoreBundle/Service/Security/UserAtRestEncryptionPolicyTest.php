<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Service\Security;

use Chamilo\CoreBundle\Service\Security\AtRestCrypto;
use Chamilo\CoreBundle\Service\Security\AtRestKeyVerifier;
use Chamilo\CoreBundle\Service\Security\UserAtRestEncryptionPolicy;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class UserAtRestEncryptionPolicyTest extends TestCase
{
    public function testDisabledByDefaultWhenSettingIsMissing(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('fetchOne')->willReturn(false);
        $verifier = new AtRestKeyVerifier($connection, new AtRestCrypto('/tmp'));
        $policy = new UserAtRestEncryptionPolicy($connection, $verifier);

        self::assertFalse($policy->isEnabled('phone'));
        self::assertFalse($policy->isEnabled('address'));
        self::assertSame([], $policy->enabledFields());
    }

    public function testSupportedFieldSelectionUsesOneDatabaseRead(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('fetchOne')
            ->with(self::stringContains('access_url = 1'))
            ->willReturn('biography,phone')
        ;
        $verifier = new AtRestKeyVerifier($connection, new AtRestCrypto('/tmp'));
        $policy = new UserAtRestEncryptionPolicy($connection, $verifier);

        self::assertTrue($policy->isEnabled('biography'));
        self::assertTrue($policy->isEnabled('phone'));
        self::assertFalse($policy->isEnabled('address'));
        self::assertFalse($policy->isEnabled('email'));
    }

    public function testUnsupportedFieldsAreRejected(): void
    {
        $this->expectException(RuntimeException::class);
        UserAtRestEncryptionPolicy::parseFields('biography,email');
    }

    public function testEmptySettingDisablesNewEncryption(): void
    {
        self::assertSame([], UserAtRestEncryptionPolicy::parseFields(''));
        self::assertSame([], UserAtRestEncryptionPolicy::parseFields('   '));
    }

    public function testDuplicateFieldsAreCanonicalized(): void
    {
        self::assertSame(['phone', 'address'], UserAtRestEncryptionPolicy::parseFields('phone,phone,address'));
    }
}
