<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Update\Signature;

use Chamilo\CoreBundle\Service\Update\Dto\UpdateManifest;
use Chamilo\CoreBundle\Service\Update\Signature\MinisignSignatureVerifier;
use PHPUnit\Framework\TestCase;

/**
 * Fixtures are built directly from libsodium primitives, following the byte
 * layout documented at https://jedisct1.github.io/minisign/ and confirmed
 * against jedisct1/minisign's own sig_load()/verify() (minisign.c/minisign.h),
 * independently of MinisignSignatureVerifier's own parsing code.
 */
final class MinisignSignatureVerifierTest extends TestCase
{
    private const string KEY_ID = "\x11\x22\x33\x44\x55\x66\x77\x88";

    private string $packagePath;
    private string $signaturePath;

    /**
     * @var array{secretKey: string, publicKeyBlob: string}
     */
    private array $keypair;

    protected function setUp(): void
    {
        if (!\function_exists('sodium_crypto_sign_keypair')) {
            self::markTestSkipped('The sodium extension is required for this test.');
        }

        $this->packagePath = tempnam(sys_get_temp_dir(), 'minisign-pkg-');
        $this->signaturePath = tempnam(sys_get_temp_dir(), 'minisign-sig-');
        file_put_contents($this->packagePath, "This is the update package content.\n");

        $pair = sodium_crypto_sign_keypair();
        $this->keypair = [
            'secretKey' => sodium_crypto_sign_secretkey($pair),
            'publicKeyBlob' => base64_encode('Ed'.self::KEY_ID.sodium_crypto_sign_publickey($pair)),
        ];
    }

    protected function tearDown(): void
    {
        @unlink($this->packagePath);
        @unlink($this->signaturePath);
    }

    public function testSupportsOnlyMinisignSignatureType(): void
    {
        $verifier = new MinisignSignatureVerifier();

        self::assertTrue($verifier->supports($this->createManifest('minisign')));
        self::assertTrue($verifier->supports($this->createManifest('MINISIGN')));
        self::assertFalse($verifier->supports($this->createManifest('gpg')));
        self::assertFalse($verifier->supports($this->createManifest(null)));
    }

    public function testLegacyNonPrehashedSignatureIsAccepted(): void
    {
        $this->writeSignatureFile(prehashed: false);

        $result = (new MinisignSignatureVerifier())->verify(
            $this->packagePath,
            $this->signaturePath,
            $this->createManifest('minisign'),
            $this->keypair['publicKeyBlob'],
        );

        self::assertTrue($result->isValid(), implode(', ', $result->getErrors()));
        self::assertFalse($result->getDetails()['signature_prehashed']);
    }

    public function testPrehashedSignatureIsAccepted(): void
    {
        $this->writeSignatureFile(prehashed: true);

        $result = (new MinisignSignatureVerifier())->verify(
            $this->packagePath,
            $this->signaturePath,
            $this->createManifest('minisign'),
            $this->keypair['publicKeyBlob'],
        );

        self::assertTrue($result->isValid(), implode(', ', $result->getErrors()));
        self::assertTrue($result->getDetails()['signature_prehashed']);
    }

    public function testTamperedPackageIsRejected(): void
    {
        $this->writeSignatureFile(prehashed: true);
        file_put_contents($this->packagePath, 'tampered content');

        $result = (new MinisignSignatureVerifier())->verify(
            $this->packagePath,
            $this->signaturePath,
            $this->createManifest('minisign'),
            $this->keypair['publicKeyBlob'],
        );

        self::assertFalse($result->isValid());
    }

    public function testTamperedTrustedCommentIsRejected(): void
    {
        $this->writeSignatureFile(prehashed: true);

        $contents = file_get_contents($this->signaturePath);
        $contents = str_replace('trusted comment: test package v1', 'trusted comment: tampered', $contents);
        file_put_contents($this->signaturePath, $contents);

        $result = (new MinisignSignatureVerifier())->verify(
            $this->packagePath,
            $this->signaturePath,
            $this->createManifest('minisign'),
            $this->keypair['publicKeyBlob'],
        );

        self::assertFalse($result->isValid());
    }

    public function testSignatureFromAnotherKeyIsRejected(): void
    {
        $this->writeSignatureFile(prehashed: true);

        $otherPair = sodium_crypto_sign_keypair();
        $otherPublicKeyBlob = base64_encode('Ed'.self::KEY_ID.sodium_crypto_sign_publickey($otherPair));

        $result = (new MinisignSignatureVerifier())->verify(
            $this->packagePath,
            $this->signaturePath,
            $this->createManifest('minisign'),
            $otherPublicKeyBlob,
        );

        self::assertFalse($result->isValid());
    }

    public function testMismatchedKeyIdIsRejectedWithClearMessage(): void
    {
        $this->writeSignatureFile(prehashed: true);

        $otherKeyPublicKeyBlob = base64_encode(
            'Ed'."\x99\x99\x99\x99\x99\x99\x99\x99".sodium_crypto_sign_publickey(sodium_crypto_sign_keypair()),
        );

        $result = (new MinisignSignatureVerifier())->verify(
            $this->packagePath,
            $this->signaturePath,
            $this->createManifest('minisign'),
            $otherKeyPublicKeyBlob,
        );

        self::assertFalse($result->isValid());
        self::assertStringContainsString('does not match the trusted key id', $result->getErrors()[0]);
    }

    public function testMalformedSignatureFileIsRejected(): void
    {
        file_put_contents($this->signaturePath, "not a minisign file\n");

        $result = (new MinisignSignatureVerifier())->verify(
            $this->packagePath,
            $this->signaturePath,
            $this->createManifest('minisign'),
            $this->keypair['publicKeyBlob'],
        );

        self::assertFalse($result->isValid());
    }

    public function testMissingTrustedPublicKeyIsRejected(): void
    {
        $this->writeSignatureFile(prehashed: true);

        $result = (new MinisignSignatureVerifier())->verify(
            $this->packagePath,
            $this->signaturePath,
            $this->createManifest('minisign'),
            null,
        );

        self::assertFalse($result->isValid());
    }

    public function testUnreadableSignatureFileIsRejected(): void
    {
        $result = (new MinisignSignatureVerifier())->verify(
            $this->packagePath,
            $this->signaturePath.'-does-not-exist',
            $this->createManifest('minisign'),
            $this->keypair['publicKeyBlob'],
        );

        self::assertFalse($result->isValid());
    }

    private function writeSignatureFile(bool $prehashed): void
    {
        $fileContents = (string) file_get_contents($this->packagePath);
        $message = $prehashed ? sodium_crypto_generichash($fileContents, '', 64) : $fileContents;

        $signature = sodium_crypto_sign_detached($message, $this->keypair['secretKey']);
        $signatureBlob = ($prehashed ? 'ED' : 'Ed').self::KEY_ID.$signature;

        $trustedComment = 'test package v1';
        $globalSignature = sodium_crypto_sign_detached($signature.$trustedComment, $this->keypair['secretKey']);

        $minisig = implode("\n", [
            'untrusted comment: minisign public key TEST',
            base64_encode($signatureBlob),
            'trusted comment: '.$trustedComment,
            base64_encode($globalSignature),
        ])."\n";

        file_put_contents($this->signaturePath, $minisig);
    }

    private function createManifest(?string $signatureType): UpdateManifest
    {
        return new UpdateManifest(
            'stable',
            '3.0.1',
            '2026-09-05T22:33:19+00:00',
            'https://updates.chamilo.org/assets/chamilo-3.0.1.zip',
            str_repeat('a', 64),
            $signatureType,
            'https://updates.chamilo.org/sign/chamilo-3.0.1.zip.minisig',
            null,
            ['php' => '>=8.3'],
        );
    }
}
