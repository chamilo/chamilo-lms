<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

final class BuyCoursesBtcPayClient
{
    private const FULFILLABLE_ADDITIONAL_STATUSES = ['None', 'PaidOver', 'Marked'];

    private function __construct(
        private readonly string $baseUrl,
        private readonly string $storeId,
        private readonly string $apiKey,
        private readonly string $webhookSecret,
    ) {}

    public static function isConfigured(BuyCoursesPlugin $plugin): bool
    {
        return self::settingsAreValid($plugin->getBtcPayParams());
    }

    public static function fromPlugin(BuyCoursesPlugin $plugin): self
    {
        return self::fromSettings($plugin->getBtcPayParams());
    }

    public static function fromSettings(array $settings): self
    {
        $settings = self::normalizeSettings($settings);

        if (!self::settingsAreValid($settings)) {
            throw new RuntimeException('BTCPay Server configuration is missing or invalid.');
        }

        return self::createFromNormalizedSettings($settings);
    }

    public static function fromApiSettings(array $settings): self
    {
        $settings = self::normalizeSettings($settings);

        if (!self::apiSettingsAreValid($settings)) {
            throw new RuntimeException('BTCPay Server URL, Store ID or API key is missing or invalid.');
        }

        return self::createFromNormalizedSettings($settings);
    }

    public static function apiSettingsAreValid(array $settings): bool
    {
        $settings = self::normalizeSettings($settings);

        foreach (['server_url', 'store_id', 'api_key'] as $key) {
            if ('' === $settings[$key]) {
                return false;
            }
        }

        return self::isValidServerUrl($settings['server_url']);
    }

    public static function settingsAreValid(array $settings): bool
    {
        $settings = self::normalizeSettings($settings);

        return self::apiSettingsAreValid($settings)
            && '' !== $settings['webhook_secret'];
    }

    public static function isValidServerUrl(string $serverUrl): bool
    {
        $url = parse_url(trim($serverUrl));

        return is_array($url)
            && 'https' === strtolower((string) ($url['scheme'] ?? ''))
            && '' !== (string) ($url['host'] ?? '')
            && !isset($url['user'])
            && !isset($url['pass'])
            && !isset($url['query'], $url['fragment']);
    }

    public function testConnection(): array
    {
        $store = $this->request(
            'GET',
            '/api/v1/stores/'.rawurlencode($this->storeId)
        );

        if ($this->storeId !== (string) ($store['id'] ?? '')) {
            throw new RuntimeException('BTCPay returned a different store than the configured Store ID.');
        }

        return $store;
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

        return $this->request(
            'GET',
            '/api/v1/stores/'.rawurlencode($this->storeId).'/invoices/'.rawurlencode($invoiceId)
        );
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

        return hash_equals($expected, trim($signature));
    }

    public function invoiceBelongsToSale(array $invoice, array $sale, string $currency): bool
    {
        $invoiceId = trim((string) ($invoice['id'] ?? ''));
        $metadata = $invoice['metadata'] ?? null;

        if ('' === $invoiceId || !is_array($metadata)) {
            return false;
        }

        return $this->storeId === (string) ($invoice['storeId'] ?? '')
            && 'btcpay:'.$invoiceId === (string) ($sale['reference'] ?? '')
            && (string) ($sale['id'] ?? '') === (string) ($metadata['orderId'] ?? '')
            && 'BuyCourses' === (string) ($metadata['orderType'] ?? '')
            && strtoupper($currency) === strtoupper((string) ($invoice['currency'] ?? ''))
            && number_format((float) ($sale['price'] ?? 0), 2, '.', '')
                === number_format((float) ($invoice['amount'] ?? 0), 2, '.', '');
    }

    public function isInvoiceReadyForFulfillment(array $invoice): bool
    {
        // `Marked` means a store administrator explicitly marked the invoice as
        // settled in BTCPay Server. Treat that administrative settlement the
        // same as a regular or overpaid settled invoice, while keeping late,
        // partial and invalid exceptional states in manual review.
        return 'Settled' === (string) ($invoice['status'] ?? '')
            && in_array(
                (string) ($invoice['additionalStatus'] ?? ''),
                self::FULFILLABLE_ADDITIONAL_STATUSES,
                true
            );
    }

    public function isInvoicePayableOrProcessing(array $invoice): bool
    {
        return in_array((string) ($invoice['status'] ?? ''), ['New', 'Processing'], true);
    }

    public function isInvoiceExpiredWithoutPayment(array $invoice): bool
    {
        return 'Expired' === (string) ($invoice['status'] ?? '')
            && 'None' === (string) ($invoice['additionalStatus'] ?? '');
    }

    public function fulfillSale(BuyCoursesPlugin $plugin, array $sale, array $invoice, string $currency): bool
    {
        if (
            BuyCoursesPlugin::PAYMENT_TYPE_BTCPAY !== (int) ($sale['payment_type'] ?? 0)
            || !in_array(
                (int) ($sale['product_type'] ?? 0),
                [BuyCoursesPlugin::PRODUCT_TYPE_COURSE, BuyCoursesPlugin::PRODUCT_TYPE_SESSION],
                true
            )
            || !$this->invoiceBelongsToSale($invoice, $sale, $currency)
            || !$this->isInvoiceReadyForFulfillment($invoice)
        ) {
            return false;
        }

        $invoiceId = (string) $invoice['id'];
        $connection = Database::getManager()->getConnection();
        $lockName = 'bc_btcpay_'.sha1($invoiceId);
        $locked = false;

        try {
            $locked = '1' === (string) $connection->fetchOne('SELECT GET_LOCK(?, 5)', [$lockName]);
            if (!$locked) {
                return false;
            }

            $freshSale = $plugin->getSale((int) $sale['id']);
            if (
                empty($freshSale)
                || BuyCoursesPlugin::PAYMENT_TYPE_BTCPAY !== (int) ($freshSale['payment_type'] ?? 0)
                || 'btcpay:'.$invoiceId !== (string) ($freshSale['reference'] ?? '')
            ) {
                return false;
            }

            if (BuyCoursesPlugin::SALE_STATUS_COMPLETED === (int) $freshSale['status']) {
                return true;
            }

            if (BuyCoursesPlugin::SALE_STATUS_PENDING !== (int) $freshSale['status']) {
                return false;
            }

            if ('Marked' === (string) ($invoice['additionalStatus'] ?? '')) {
                error_log(
                    '[BuyCourses][BTCPay] Fulfilling sale '.(int) $freshSale['id']
                    .' from an invoice explicitly marked as settled in BTCPay Server.'
                );
            }

            if (!$plugin->completeSale((int) $freshSale['id'])) {
                return false;
            }

            $plugin->storePayouts((int) $freshSale['id']);

            return true;
        } finally {
            if ($locked) {
                try {
                    $connection->fetchOne('SELECT RELEASE_LOCK(?)', [$lockName]);
                } catch (Throwable $exception) {
                    error_log('[BuyCourses][BTCPay] Could not release the invoice lock: '.$exception->getMessage());
                }
            }
        }
    }

    private static function createFromNormalizedSettings(array $settings): self
    {
        return new self(
            rtrim($settings['server_url'], '/'),
            $settings['store_id'],
            $settings['api_key'],
            $settings['webhook_secret'],
        );
    }

    private static function normalizeSettings(array $settings): array
    {
        return [
            'server_url' => trim((string) ($settings['server_url'] ?? '')),
            'store_id' => trim((string) ($settings['store_id'] ?? '')),
            'api_key' => trim((string) ($settings['api_key'] ?? '')),
            'webhook_secret' => trim((string) ($settings['webhook_secret'] ?? '')),
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
            $curlError = curl_error($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        } finally {
            curl_close($handle);
        }

        if (false === $response || $status < 200 || $status >= 300) {
            $suffix = '' !== $curlError ? ' '.$curlError : '';
            throw new RuntimeException('BTCPay request failed (HTTP '.$status.').'.$suffix);
        }

        $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new RuntimeException('BTCPay returned an invalid JSON response.');
        }

        return $decoded;
    }
}
