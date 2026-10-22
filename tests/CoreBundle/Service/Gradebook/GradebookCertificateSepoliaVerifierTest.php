<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\GradebookCertificateCredential;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateCredentialSigner;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateSepoliaVerifier;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

use const JSON_THROW_ON_ERROR;

final class GradebookCertificateSepoliaVerifierTest extends TestCase
{
    /**
     * A Sepolia fingerprint transaction must have zero value, valid input,
     * and either the shared test receiver or the original legacy self-recipient.
     *
     * @dataProvider transactionRecipients
     */
    public function testTransactionRecipientValidation(string $recipient, string $value, bool $expected): void
    {
        $certificate = $this->createMock(GradebookCertificate::class);
        $credential = new GradebookCertificateCredential(
            $certificate,
            str_repeat('a', 64),
            '{"course":"QA course"}',
            'QA signature',
            str_repeat('b', 64),
        );
        $signer = new GradebookCertificateCredentialSigner(
            __DIR__,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(GradebookCertificateRepository::class),
        );
        $sender = '0x'.str_repeat('1', 40);
        $txHash = '0x'.str_repeat('a', 64);
        $responses = [
            new MockResponse(json_encode(['jsonrpc' => '2.0', 'id' => 1, 'result' => '0xaa36a7'], JSON_THROW_ON_ERROR)),
            new MockResponse(json_encode(['jsonrpc' => '2.0', 'id' => 1, 'result' => [
                'from' => $sender,
                'to' => 'self' === $recipient ? $sender : $recipient,
                'input' => $signer->anchorData($credential),
                'value' => $value,
            ]], JSON_THROW_ON_ERROR)),
            new MockResponse(json_encode(['jsonrpc' => '2.0', 'id' => 1, 'result' => [
                'status' => '0x1',
                'transactionHash' => $txHash,
                'blockNumber' => '0x123',
            ]], JSON_THROW_ON_ERROR)),
        ];
        $verifier = new GradebookCertificateSepoliaVerifier(new MockHttpClient($responses), $signer);
        $key = 'CHAMILO_CERTIFICATE_SEPOLIA_RPC_URL';
        $original = $_SERVER[$key] ?? null;
        $_SERVER[$key] = 'https://rpc.example.test';

        try {
            self::assertSame($expected, $verifier->verifyTransaction($credential, $txHash));
        } finally {
            if (null === $original) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $original;
            }
        }
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function transactionRecipients(): iterable
    {
        yield 'fixed Sepolia receiver' => ['0x000000000000000000000000000000000000dEaD', '0x0', true];

        yield 'legacy self transaction' => ['self', '0x0', true];

        yield 'untrusted other receiver' => ['0x'.str_repeat('2', 40), '0x0', false];

        yield 'nonzero transfer' => ['0x000000000000000000000000000000000000dEaD', '0x1', false];
    }
}
