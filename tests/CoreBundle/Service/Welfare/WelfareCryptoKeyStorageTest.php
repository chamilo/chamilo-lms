<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Welfare;

use Chamilo\CoreBundle\Service\Welfare\WelfareCrypto;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class WelfareCryptoKeyStorageTest extends TestCase
{
    private string $directory;
    private ?string $originalLegacyKey = null;

    protected function setUp(): void
    {
        $this->originalLegacyKey = $_SERVER['CHAMILO_WELFARE_KEY'] ?? null;
        $_SERVER['CHAMILO_WELFARE_KEY'] = '';
        $this->directory = sys_get_temp_dir().'/chamilo-welfare-key-test-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->directory.'/var', 0700, true));
    }

    protected function tearDown(): void
    {
        if (null !== $this->originalLegacyKey) {
            $_SERVER['CHAMILO_WELFARE_KEY'] = $this->originalLegacyKey;
        } else {
            unset($_SERVER['CHAMILO_WELFARE_KEY']);
        }
        $file = $this->directory.'/var/welfare-keys/master.key';
        if (is_file($file)) {
            unlink($file);
        }
        if (is_dir($this->directory.'/var/welfare-keys')) {
            rmdir($this->directory.'/var/welfare-keys');
        }
        rmdir($this->directory.'/var');
        rmdir($this->directory);
    }

    public function testFreshInstallationCreatesAndReusesProtectedKey(): void
    {
        $first = $this->crypto(null);
        $first->assertEnabled();
        $ciphertext = $first->encrypt('Example confidential report', 'subject:one');

        $file = $this->directory.'/var/welfare-keys/master.key';
        self::assertFileExists($file);
        self::assertSame(0, fileperms($file) & 0077);
        self::assertSame(0, fileperms($this->directory.'/var/welfare-keys') & 0077);
        self::assertSame('Example confidential report', $this->crypto(null)->decrypt($ciphertext, 'subject:one'));
    }

    public function testExistingRecordsImportOriginalKeyAndSurviveEnvironmentRemoval(): void
    {
        $oldKey = base64_encode(random_bytes(32));
        $old = new WelfareCrypto($oldKey);
        $encrypted = $old->encrypt('Existing report', 'subject:example');
        $row = ['reference_token' => 'example', 'subject_cipher' => $encrypted];

        $_SERVER['CHAMILO_WELFARE_KEY'] = $oldKey;
        $this->crypto($row)->assertEnabled();
        $_SERVER['CHAMILO_WELFARE_KEY'] = '';

        self::assertSame('Existing report', $this->crypto($row)->decrypt($encrypted, 'subject:example'));
    }

    public function testExistingRecordsNeverGenerateReplacementKey(): void
    {
        $oldKey = base64_encode(random_bytes(32));
        $encrypted = (new WelfareCrypto($oldKey))->encrypt('Existing report', 'subject:example');
        $row = ['reference_token' => 'example', 'subject_cipher' => $encrypted];
        $file = $this->directory.'/var/welfare-keys/master.key';

        try {
            $this->crypto($row)->assertEnabled();
            self::fail('Existing records must not trigger a replacement encryption key.');
        } catch (ServiceUnavailableHttpException) {
            self::assertFileDoesNotExist($file);
        }
    }

    public function testIncorrectLegacyKeyIsRejectedWithoutPersistingIt(): void
    {
        $encrypted = (new WelfareCrypto(base64_encode(random_bytes(32))))->encrypt('Existing report', 'subject:example');
        $row = ['reference_token' => 'example', 'subject_cipher' => $encrypted];
        $_SERVER['CHAMILO_WELFARE_KEY'] = base64_encode(random_bytes(32));

        $this->expectException(ServiceUnavailableHttpException::class);
        $this->crypto($row)->assertEnabled();
    }

    /**
     * @param array{reference_token:string,subject_cipher:string}|null $firstReport
     */
    private function crypto(?array $firstReport): WelfareCrypto
    {
        $settings = $this->createMock(SettingsManager::class);
        $settings->method('getSetting')->willReturn('true');
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAssociative')->willReturn($firstReport ?? false);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        return new WelfareCrypto(null, $settings, $this->directory, $entityManager);
    }
}
