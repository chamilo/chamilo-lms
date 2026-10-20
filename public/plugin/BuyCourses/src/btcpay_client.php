<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

final class BuyCoursesBtcPayClient
{
    private function __construct(
        private readonly string $baseUrl,
        private readonly string $storeId,
        private readonly string $apiKey,
        private readonly string $webhookSecret,
    ) {}

    public static function isConfigured(): bool
    {
        $settings = self::environmentSettings();

        foreach ($settings as $setting) {
            if ('' === $setting) {
                return false;
            }
        }

        $url = parse_url($settings['url']);

        return is_array($url)
            && 'https' === strtolower((string) ($url['scheme'] ?? ''))
            && '' !== (string) ($url['host'] ?? '')
            && !isset($url['user'])
            && !isset($url['pass'])
            && !isset($url['query'], $url['fragment']);
    }

    public static function fromEnvironment(): self
    {
        if (!self::isConfigured()) {
            throw new RuntimeException('BTCPay Server configuration is missing or invalid.');
        }

        $settings = self::environmentSettings();

        return new self(
            rtrim($settings['url'], '/'),
            $settings['store_id'],
            $settings['api_key'],
            $settings['webhook_secret'],
        );
    }

    public function createInvoice(array $sale, string $currency, string $returnUrl): array
    {
        if ((float) $sale['price'] <= 0 || !preg_match('/^[A-Z]{3}$/', strtoupper($currency))) {
            throw new InvalidArgumentException('Invalid amount or currency for BTCPay invoice.');
        }

        $invoice = $this->request('POST', '/api/v1/stores/'.rawurlencode($this->storeId).'/invoices', [
            'amount' => number_format((float) $sale['price'], 2, '.', ''),
            'currency' => strtoupper($currency),
            'metadata' => [
                'orderId' => (string) $sale['id'],
                'orderType' => 'BuyCourses',
            ],
            'checkout' => [
                'redirectURL' => $returnUrl,
            ],
        ]);

        if (
            '' === (string) ($invoice['id'] ?? '')
            || strlen((string) $invoice['id']) > 200
            || !$this->isTrustedCheckoutLink((string) ($invoice['checkoutLink'] ?? ''))
        ) {
            throw new RuntimeException('BTCPay returned an invalid invoice.');
        }

        return $invoice;
    }

    public function getInvoice(string $invoiceId): array
    {
        if ('' === $invoiceId || strlen($invoiceId) > 200) {
            throw new InvalidArgumentException('Invalid BTCPay invoice ID.');
        }

        return $this->request('GET', '/api/v1/stores/'.rawurlencode($this->storeId).'/invoices/'.rawurlencode($invoiceId));
    }

    public function isTrustedCheckoutLink(string $checkoutLink): bool
    {
        $base = parse_url($this->baseUrl);
        $checkout = parse_url($checkoutLink);

        return is_array($base)
            && is_array($checkout)
            && 'https' === strtolower((string) ($checkout['scheme'] ?? ''))
            && strtolower((string) ($base['host'] ?? '')) === strtolower((string) ($checkout['host'] ?? ''))
            && (int) ($base['port'] ?? 443) === (int) ($checkout['port'] ?? 443)
            && !isset($checkout['user'])
            && !isset($checkout['pass'])
            && !isset($checkout['fragment']);
    }

    public function getStoreId(): string
    {
        return $this->storeId;
    }

    public function verifyWebhook(string $body, string $signature): bool
    {
        if ('' === $body || '' === $signature) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $body, $this->webhookSecret);

        return hash_equals($expected, $signature);
    }

    private static function environmentSettings(): array
    {
        $read = static function (string $key): string {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

            return is_scalar($value) ? trim((string) $value) : '';
        };

        return [
            'url' => $read('BUYCOURSES_BTCPAY_URL'),
            'store_id' => $read('BUYCOURSES_BTCPAY_STORE_ID'),
            'api_key' => $read('BUYCOURSES_BTCPAY_API_KEY'),
            'webhook_secret' => $read('BUYCOURSES_BTCPAY_WEBHOOK_SECRET'),
        ];
    }

    private function request(string $method, string $path, ?array $payload = null): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('The PHP cURL extension is required for BTCPay.');
        }

        $handle = curl_init($this->baseUrl.$path);

        if (false === $handle) {
            throw new RuntimeException('Could not initialize the BTCPay request.');
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => [
                'Authorization: token '.$this->apiKey,
                'Accept: application/json',
                'Content-Type: application/json',
            ],
        ];

        if (defined('CURLOPT_PROTOCOLS_STR')) {
            $options[CURLOPT_PROTOCOLS_STR] = 'https';
        } else {
            $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
        }

        if (null !== $payload) {
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_THROW_ON_ERROR);
        }

        curl_setopt_array($handle, $options);

        try {
            $response = curl_exec($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        } finally {
            curl_close($handle);
        }

        if (false === $response || $status < 200 || $status >= 300) {
            throw new RuntimeException('BTCPay request failed (HTTP '.$status.').');
        }

        $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new RuntimeException('BTCPay returned an invalid JSON response.');
        }

        return $decoded;
    }
}
