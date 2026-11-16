<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Command;

use Chamilo\CoreBundle\Command\EncryptUserBiographiesCommand;
use Chamilo\CoreBundle\Service\Security\AtRestCrypto;
use Chamilo\CoreBundle\Service\Security\AtRestKeyVerifier;
use Chamilo\CoreBundle\Service\Security\UserAtRestEncryptionPolicy;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class EncryptUserBiographiesCommandTest extends TestCase
{
    public function testApplyWithNoPendingBiographiesSucceedsWithoutWriting(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::exactly(2))
            ->method('fetchOne')
            ->willReturnOnConsecutiveCalls(0, 1)
        ;
        $connection->expects(self::never())->method('fetchAllAssociative');
        $connection->expects(self::never())->method('executeStatement');

        $crypto = new AtRestCrypto('/tmp');
        $verifier = new AtRestKeyVerifier($connection, $crypto);
        $policy = new UserAtRestEncryptionPolicy($connection, $verifier);
        $command = new EncryptUserBiographiesCommand($connection, $crypto, $policy);
        $tester = new CommandTester($command);

        self::assertSame(Command::SUCCESS, $tester->execute(['--apply' => true]));
        self::assertStringContainsString('Pending: 0; encrypted/versioned values: 1.', $tester->getDisplay());
        self::assertStringContainsString('Nothing to encrypt; no records were changed.', $tester->getDisplay());
    }
}
