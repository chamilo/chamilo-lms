<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\GradebookCertificateCredential;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateCredentialSigner;
use Chamilo\CoreBundle\Service\Gradebook\IssuerSigningKey;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

use const SODIUM_CRYPTO_SIGN_SEEDBYTES;

final class GradebookCertificateCredentialTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $temporaryDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryDirs as $root) {
            @unlink($root.'/var/certificate-issuer/ed25519.seed');
            @rmdir($root.'/var/certificate-issuer');
            @rmdir($root.'/var');
            @rmdir($root);
        }
        parent::tearDown();
    }

    public function testSignedSnapshotCanBeVerifiedAndRevoked(): void
    {
        $certificate = $this->createMock(GradebookCertificate::class);
        $payload = '{"format":"chamilo-certificate-credential-v1","subject":"QA Student"}';
        $pair = sodium_crypto_sign_keypair();
        $secret = sodium_crypto_sign_secretkey($pair);
        $public = sodium_crypto_sign_publickey($pair);
        $sig = base64_encode(sodium_crypto_sign_detached($payload, $secret));
        $credential = new GradebookCertificateCredential($certificate, str_repeat('a', 64), $payload, $sig, bin2hex($public));
        $signer = $this->signerWithTrustedKey($pair);
        self::assertTrue($signer->validSignature($credential));
        self::assertSame('0x'.bin2hex('CHAMILO-CERT-V1|').$credential->getAnchorHash(), $signer->anchorData($credential));
        self::assertNull($credential->getRevokedAt());
        $credential->revoke();
        self::assertNotNull($credential->getRevokedAt());
    }

    private function signerWithTrustedKey(string $keypair): GradebookCertificateCredentialSigner
    {
        $root = sys_get_temp_dir().'/chamilo-proof-test-'.bin2hex(random_bytes(6));
        mkdir($root.'/var/certificate-issuer', 0700, true);
        $this->temporaryDirs[] = $root;
        file_put_contents($root.'/var/certificate-issuer/ed25519.seed', bin2hex(substr(sodium_crypto_sign_secretkey($keypair), 0, SODIUM_CRYPTO_SIGN_SEEDBYTES)));

        return new GradebookCertificateCredentialSigner(
            new IssuerSigningKey($root.'/var/certificate-issuer', $this->createMock(EntityManagerInterface::class)),
            $this->createMock(GradebookCertificateRepository::class)
        );
    }

    public function testTamperedSnapshotFailsVerification(): void
    {
        $certificate = $this->createMock(GradebookCertificate::class);
        $pair = sodium_crypto_sign_keypair();
        $credential = new GradebookCertificateCredential(
            $certificate,
            str_repeat('b', 64),
            '{"altered":true}',
            base64_encode(sodium_crypto_sign_detached('{"altered":false}', sodium_crypto_sign_secretkey($pair))),
            bin2hex(sodium_crypto_sign_publickey($pair)),
        );
        $signer = $this->signerWithTrustedKey($pair);
        self::assertFalse($signer->validSignature($credential));
    }
}
