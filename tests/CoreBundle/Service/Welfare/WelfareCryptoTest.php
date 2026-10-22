<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Welfare;

use Chamilo\CoreBundle\Service\Welfare\WelfareCrypto;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class WelfareCryptoTest extends TestCase
{
    public function testDifferentContextsCannotDecryptTheSameCiphertext(): void
    {
        $crypto = $this->testCrypto();
        $ciphertext = $crypto->encrypt('Private report details', 'report:a');

        self::assertSame('Private report details', $crypto->decrypt($ciphertext, 'report:a'));
        self::assertStringNotContainsString('Private report details', $ciphertext);

        $this->expectException(RuntimeException::class);
        $crypto->decrypt($ciphertext, 'report:b');
    }

    public function testTamperingWithCiphertextFailsAuthentication(): void
    {
        $crypto = $this->testCrypto();
        $ciphertext = $crypto->encrypt('A confidential account', 'report:a');
        $raw = base64_decode(substr($ciphertext, 3), true);
        self::assertIsString($raw);
        $raw[\strlen($raw) - 1] = \chr(\ord($raw[\strlen($raw) - 1]) ^ 1);

        $this->expectException(RuntimeException::class);
        $crypto->decrypt('v1:'.base64_encode($raw), 'report:a');
    }

    public function testEncryptionUsesFreshRandomNonces(): void
    {
        $crypto = $this->testCrypto();

        self::assertNotSame($crypto->encrypt('Same message', 'a'), $crypto->encrypt('Same message', 'a'));
    }

    public function testBlindIndexIsStableButPortalAndUserScoped(): void
    {
        $crypto = $this->testCrypto();

        self::assertSame($crypto->reporterDigest(1, 17), $crypto->reporterDigest(1, 17));
        self::assertNotSame($crypto->reporterDigest(1, 17), $crypto->reporterDigest(1, 18));
        self::assertNotSame($crypto->reporterDigest(1, 17), $crypto->reporterDigest(2, 17));
    }

    public function testFeatureIsDisabledByDefault(): void
    {
        $this->expectException(ServiceUnavailableHttpException::class);
        (new WelfareCrypto())->assertEnabled();
    }

    public function testReadinessIsFalseWhenTheFeatureIsDisabled(): void
    {
        self::assertFalse((new WelfareCrypto())->isReady());
    }

    public function testReadinessIsTrueWhenKeyIsConfigured(): void
    {
        self::assertTrue($this->testCrypto()->isReady());
    }

    public function testDifferentKeysCannotDecryptContent(): void
    {
        $original = $this->testCrypto();
        $another = $this->testCrypto();
        $ciphertext = $original->encrypt('Evidence from a test case', 'case:test');

        $this->expectException(RuntimeException::class);
        $another->decrypt($ciphertext, 'case:test');
    }

    public function testInvalidKeyFailsClosed(): void
    {
        $this->expectException(ServiceUnavailableHttpException::class);
        (new WelfareCrypto('invalid-key'))->assertEnabled();
    }

    private function testCrypto(): WelfareCrypto
    {
        return new WelfareCrypto(base64_encode(random_bytes(32)));
    }
}
