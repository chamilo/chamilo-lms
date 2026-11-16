<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Service\Security;

use Chamilo\CoreBundle\Service\Security\AtRestCrypto;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AtRestCryptoTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/chamilo-at-rest-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->directory.'/var', 0700, true));
    }

    protected function tearDown(): void
    {
        $key = $this->directory.'/var/data-at-rest/master.key';
        if (is_file($key)) {
            unlink($key);
        }
        if (is_dir($this->directory.'/var/data-at-rest')) {
            chmod($this->directory.'/var/data-at-rest', 0700);
            rmdir($this->directory.'/var/data-at-rest');
        }
        rmdir($this->directory.'/var');
        rmdir($this->directory);
    }

    public function testRoundTripDoesNotExposePlaintextAndUsesRandomNonces(): void
    {
        $crypto = $this->initializedCrypto();
        $first = $crypto->encrypt('Private biography', 'user.biography:uuid-a');
        $second = $crypto->encrypt('Private biography', 'user.biography:uuid-a');

        self::assertStringStartsWith(AtRestCrypto::PREFIX, $first);
        self::assertStringNotContainsString('Private biography', $first);
        self::assertNotSame($first, $second);
        self::assertSame('Private biography', $crypto->decrypt($first, 'user.biography:uuid-a'));
        self::assertSame(
            'Private biography',
            (new AtRestCrypto($this->directory))->decrypt($second, 'user.biography:uuid-a')
        );
    }

    public function testCiphertextCannotBeReassignedToAnotherUser(): void
    {
        $crypto = $this->initializedCrypto();
        $encrypted = $crypto->encrypt('User A', 'user.biography:uuid-a');

        $this->expectException(RuntimeException::class);
        $crypto->decrypt($encrypted, 'user.biography:uuid-b');
    }

    public function testModificationIsDetected(): void
    {
        $crypto = $this->initializedCrypto();
        $encrypted = $crypto->encrypt('Protected', 'test');
        $raw = base64_decode(substr($encrypted, \strlen(AtRestCrypto::PREFIX)), true);
        self::assertIsString($raw);
        $raw[\strlen($raw) - 1] = \chr(\ord($raw[\strlen($raw) - 1]) ^ 1);

        $this->expectException(RuntimeException::class);
        $crypto->decrypt(AtRestCrypto::PREFIX.base64_encode($raw), 'test');
    }

    public function testUnsupportedVersionFailsClosed(): void
    {
        $crypto = $this->initializedCrypto();
        self::assertTrue($crypto->isEncrypted('chamilo-encrypted:v2:anything'));
        self::assertFalse($crypto->isEncrypted('Plain biography'));

        $this->expectException(RuntimeException::class);
        $crypto->decrypt('chamilo-encrypted:v2:anything', 'test');
    }

    public function testMissingKeyFailsWithoutGeneratingReplacement(): void
    {
        $crypto = new AtRestCrypto($this->directory);

        try {
            $crypto->encrypt('Private', 'test');
            self::fail('A missing key must never be created automatically.');
        } catch (RuntimeException) {
            self::assertFileDoesNotExist($this->directory.'/var/data-at-rest/master.key');
        }
    }

    public function testInitializeCreatesPrivateKeyAndNeverOverwritesIt(): void
    {
        $crypto = $this->initializedCrypto();
        $path = $this->directory.'/var/data-at-rest/master.key';
        self::assertSame(0, fileperms($path) & 0077);
        self::assertSame(0, fileperms($this->directory.'/var/data-at-rest') & 0077);
        $old = file_get_contents($path);

        try {
            $crypto->initializeKey();
            self::fail('An existing key must not be replaced.');
        } catch (RuntimeException) {
            self::assertSame($old, file_get_contents($path));
        }
    }

    public function testInsecureKeyPermissionsAreRejected(): void
    {
        $crypto = $this->initializedCrypto();
        $path = $this->directory.'/var/data-at-rest/master.key';
        chmod($path, 0644);

        $this->expectException(RuntimeException::class);
        (new AtRestCrypto($this->directory))->encrypt('Secret', 'test');
    }

    public function testDifferentKeyCannotDecryptExistingCiphertext(): void
    {
        $crypto = $this->initializedCrypto();
        $cipher = $crypto->encrypt('Original', 'test');
        $path = $this->directory.'/var/data-at-rest/master.key';
        file_put_contents($path, base64_encode(random_bytes(32))."\n");

        $this->expectException(RuntimeException::class);
        (new AtRestCrypto($this->directory))->decrypt($cipher, 'test');
    }

    private function initializedCrypto(): AtRestCrypto
    {
        $crypto = new AtRestCrypto($this->directory);
        $crypto->initializeKey();

        return $crypto;
    }
}
