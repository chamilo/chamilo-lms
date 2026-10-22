<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\GradebookCertificateCredential;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Confirms manual, wallet-signed Sepolia transactions. Never holds wallet keys.
 * No request-supplied RPC URLs are accepted, preventing SSRF.
 */
final readonly class GradebookCertificateSepoliaVerifier
{
    // Fixed 0-ETH testnet receiver; allows calldata without sending to an internal wallet.
    // Keep in sync with GradebookCertificatesView.vue.
    private const string SEPOLIA_ANCHOR_RECEIVER = '0x000000000000000000000000000000000000dead';

    public function __construct(
        private HttpClientInterface $httpClient,
        private GradebookCertificateCredentialSigner $signer
    ) {}

    public function verifyTransaction(GradebookCertificateCredential $credential, string $txHash): bool
    {
        if (1 !== preg_match('/^0x[0-9a-fA-F]{64}$/D', $txHash)) {
            throw new RuntimeException('Invalid transaction hash.');
        }
        $url = $_SERVER['CHAMILO_CERTIFICATE_SEPOLIA_RPC_URL'] ?? $_ENV['CHAMILO_CERTIFICATE_SEPOLIA_RPC_URL'] ?? '';
        if (!\is_string($url) || '' === trim($url)) {
            throw new RuntimeException('Sepolia RPC is not configured.');
        }
        if (!str_starts_with($url, 'https://')) {
            throw new RuntimeException('Sepolia RPC must use HTTPS.');
        }
        if ('0xaa36a7' !== strtolower((string) $this->rpc($url, 'eth_chainId', []))) {
            throw new RuntimeException('RPC is not connected to Sepolia (chain ID 11155111).');
        }
        $tx = $this->rpc($url, 'eth_getTransactionByHash', [$txHash]);
        $receipt = $this->rpc($url, 'eth_getTransactionReceipt', [$txHash]);
        if (!\is_array($tx) || !\is_array($receipt) || '0x1' !== strtolower((string) ($receipt['status'] ?? ''))
            || !\is_string($tx['from'] ?? null) || !\is_string($tx['to'] ?? null)
            || !self::isExpectedRecipient($tx['from'], $tx['to'])
            || !preg_match('/^0x0+$/iD', (string) ($tx['value'] ?? ''))
            || !hash_equals(strtolower($this->signer->anchorData($credential)), strtolower((string) ($tx['input'] ?? '')))
            || strtolower((string) ($receipt['transactionHash'] ?? '')) !== strtolower($txHash)
            || !isset($receipt['blockNumber']) || null === $receipt['blockNumber']) {
            return false;
        }

        return true;
    }

    private static function isExpectedRecipient(string $from, string $to): bool
    {
        // Keep previously confirmed self-address transactions valid for other wallets.
        return 0 === strcasecmp($from, $to)
            || 0 === strcasecmp($to, self::SEPOLIA_ANCHOR_RECEIVER);
    }

    private function rpc(string $url, string $method, array $params): mixed
    {
        $response = $this->httpClient->request('POST', $url, [
            'json' => ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params],
            'timeout' => 10,
            'max_redirects' => 0,
        ]);
        if (200 !== $response->getStatusCode()) {
            throw new RuntimeException('Sepolia RPC request failed.');
        }
        $json = $response->toArray(false);
        if (isset($json['error']) || !\array_key_exists('result', $json)) {
            throw new RuntimeException('Sepolia RPC returned an error.');
        }

        return $json['result'];
    }
}
