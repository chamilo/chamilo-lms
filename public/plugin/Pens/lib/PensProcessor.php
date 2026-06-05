<?php

/* For licensing terms, see /license.txt. */

use Chamilo\CoreBundle\Helpers\SafeHttpClientHelper;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/pens.php';

/**
 * Processes PENS collect requests inside the plugin.
 */
class PensProcessor
{
    private const TABLE_PENS = 'plugin_pens';
    private const SUPPORTED_PACKAGE_TYPES = ['scorm-pif'];
    private const SUPPORTED_PACKAGE_FORMATS = ['zip'];
    private const ALLOWED_REMOTE_SCHEMES = ['http', 'https'];
    private const MAX_PACKAGE_BYTES = 104857600; // 100 MB

    /**
     * Handle a PENS collect request and return the plain-text PENS response.
     */
    public function handle(array $payload): string
    {
        try {
            $request = $this->createCollectRequest($payload);
        } catch (PENSException $exception) {
            return (string) new PENSResponse($exception);
        }

        $initialResponse = new PENSResponse(0, 'collect command received and understood');
        $temporaryPackagePath = null;

        try {
            try {
                $temporaryPackagePath = $this->collectPackage($request);
                $receipt = new PENSResponse(0, 'package successfully collected');
            } catch (PENSException $exception) {
                $receipt = new PENSResponse($exception);
            }

            $this->sendReceipt($request, $receipt);

            if (null !== $temporaryPackagePath) {
                try {
                    $archivedFilename = $this->archivePackage($request, $temporaryPackagePath);
                    $this->savePackageRecord($request, $archivedFilename);

                    $temporaryPackagePath = null;

                    $this->sendAlert($request, new PENSResponse(0, 'Package successfully processed'));
                } catch (PENSException $exception) {
                    $this->sendAlert($request, new PENSResponse($exception));
                } catch (Throwable $exception) {
                    $this->sendAlert($request, new PENSResponse(1432, 'Internal package error'));
                }
            }
        } finally {
            if (null !== $temporaryPackagePath && is_file($temporaryPackagePath)) {
                unlink($temporaryPackagePath);
            }
        }

        return (string) $initialResponse;
    }

    /**
     * Build a valid collect request object from request data.
     */
    private function createCollectRequest(array $payload): PENSRequestCollect
    {
        $arguments = array_merge(
            [
                'command' => '',
                'pens-version' => '',
                'package-type' => '',
                'package-type-version' => '',
                'package-format' => '',
                'package-id' => '',
                'package-url' => '',
                'package-url-user-id' => '',
                'package-url-account' => '',
                'package-url-password' => '',
                'package-url-expiry' => '',
                'client' => '',
                'system-user-id' => '',
                'system-password' => '',
                'receipt' => '',
                'alerts' => '',
                'vendor-data' => '',
            ],
            $payload
        );

        $request = PENSRequestFactory::createPENSRequest($arguments);

        if (!$request instanceof PENSRequestCollect) {
            throw new PENSException(1421);
        }

        return $request;
    }

    /**
     * Download the remote package to a temporary file.
     */
    private function collectPackage(PENSRequestCollect $request): string
    {
        error_log('[Pens][collectPackage] start package-url='.$request->getPackageUrl());

        if (!in_array($request->getPackageType(), self::SUPPORTED_PACKAGE_TYPES, true)) {
            error_log('[Pens][collectPackage] invalid package type');
            throw new PENSException(1430);
        }

        if (!in_array($request->getPackageFormat(), self::SUPPORTED_PACKAGE_FORMATS, true)) {
            error_log('[Pens][collectPackage] invalid package format');
            throw new PENSException(1431);
        }

        if (!$this->isExpiryDateValid($request->getPackageUrlExpiry())) {
            error_log('[Pens][collectPackage] expired package url');
            throw new PENSException(1322);
        }

        if (!$this->isAllowedPackageUrl($request->getPackageUrl())) {
            error_log('[Pens][collectPackage] download url rejected');
            throw new PENSException(1301);
        }

        $temporaryPackagePath = tempnam(sys_get_temp_dir(), 'pens_');

        if (false === $temporaryPackagePath) {
            error_log('[Pens][collectPackage] tempnam failed');
            throw new PENSException(1432);
        }

        $fileHandle = fopen($temporaryPackagePath, 'w');

        if (false === $fileHandle) {
            error_log('[Pens][collectPackage] fopen failed');
            throw new PENSException(1432);
        }

        $options = [
            'timeout' => 15,
            'max_duration' => 300,
        ];

        if (null !== $request->getPackageUrlUserId()) {
            $options['auth_basic'] = [
                (string) $request->getPackageUrlUserId(),
                (string) $request->getPackageUrlPassword(),
            ];
        }

        // SSRF-safe download: NoPrivateNetworkHttpClient refuses any target that
        // resolves to a loopback/private/reserved range (incl. the metadata
        // endpoint) and re-validates every redirect hop at connection time, so it
        // also stops DNS-rebinding the static URL allowlist cannot catch.
        $client = SafeHttpClientHelper::create();

        try {
            $response = $client->request('GET', (string) $request->getPackageUrl(), $options);
            $httpStatusCode = $response->getStatusCode();
        } catch (ExceptionInterface $exception) {
            fclose($fileHandle);

            if (is_file($temporaryPackagePath)) {
                unlink($temporaryPackagePath);
            }

            error_log('[Pens][collectPackage] safe download failed: '.$exception->getMessage());

            throw new PENSException(1310);
        }

        error_log('[Pens][collectPackage] http='.$httpStatusCode);

        if (401 === $httpStatusCode || 403 === $httpStatusCode) {
            fclose($fileHandle);
            unlink($temporaryPackagePath);
            error_log('[Pens][collectPackage] rejected by remote server');

            throw new PENSException(1312);
        }

        if ($httpStatusCode >= 400) {
            fclose($fileHandle);
            unlink($temporaryPackagePath);
            error_log('[Pens][collectPackage] remote server returned http >= 400');

            throw new PENSException(1310);
        }

        try {
            foreach ($client->stream($response) as $chunk) {
                fwrite($fileHandle, $chunk->getContent());
            }

            fclose($fileHandle);
        } catch (ExceptionInterface $exception) {
            if (is_resource($fileHandle)) {
                fclose($fileHandle);
            }

            if (is_file($temporaryPackagePath)) {
                unlink($temporaryPackagePath);
            }

            error_log('[Pens][collectPackage] safe download stream failed: '.$exception->getMessage());

            throw new PENSException(1310);
        }

        $downloadedSize = @filesize($temporaryPackagePath);
        error_log('[Pens][collectPackage] downloaded size='.var_export($downloadedSize, true));

        if (false === $downloadedSize || 0 === $downloadedSize) {
            if (is_file($temporaryPackagePath)) {
                unlink($temporaryPackagePath);
            }

            error_log('[Pens][collectPackage] invalid downloaded size');
            throw new PENSException(1310);
        }

        if ($downloadedSize > self::MAX_PACKAGE_BYTES) {
            unlink($temporaryPackagePath);
            error_log('[Pens][collectPackage] file too large');
            throw new PENSException(1310);
        }

        if (!$this->hasZipSignature($temporaryPackagePath)) {
            unlink($temporaryPackagePath);
            error_log('[Pens][collectPackage] invalid zip signature');
            throw new PENSException(1310);
        }

        error_log('[Pens][collectPackage] success temp='.$temporaryPackagePath);

        return $temporaryPackagePath;
    }

    /**
     * Check whether the package URL expiry is still valid.
     */
    private function isExpiryDateValid(DateTime $expiry): bool
    {
        return $expiry->getTimestamp() >= time();
    }

    /**
     * Move the collected package into archive/pens.
     */
    private function archivePackage(PENSRequestCollect $request, string $temporaryPackagePath): string
    {
        $archiveDirectory = rtrim(api_get_path(SYMFONY_SYS_PATH), '/').'/var/plugins/pens';

        if (!is_dir($archiveDirectory)) {
            mkdir($archiveDirectory, api_get_permissions_for_new_directories(), true);
        }

        $filename = $this->sanitizeFilename($request->getFilename());

        if ('' === $filename) {
            $filename = 'pens_package_'.date('YmdHis').'.zip';
        }

        $targetPath = $this->buildUniqueArchivePath($archiveDirectory, $filename);

        if (!rename($temporaryPackagePath, $targetPath)) {
            if (!copy($temporaryPackagePath, $targetPath)) {
                throw new PENSException(1432);
            }

            unlink($temporaryPackagePath);
        }

        return basename($targetPath);
    }

    /**
     * Persist the package metadata in plugin_pens.
     */
    private function savePackageRecord(PENSRequestCollect $request, string $archivedFilename): void
    {
        $table = Database::get_main_table(self::TABLE_PENS);
        $createdAt = api_get_utc_datetime();

        $cleanPensVersion = Database::escape_string((string) $request->getPensVersion());
        $cleanPackageType = Database::escape_string((string) $request->getPackageType());
        $cleanPackageTypeVersion = Database::escape_string((string) $request->getPackageTypeVersion());
        $cleanPackageFormat = Database::escape_string((string) $request->getPackageFormat());
        $cleanPackageId = Database::escape_string((string) $request->getPackageId());
        $cleanClient = Database::escape_string((string) $request->getClient());
        $cleanVendorData = Database::escape_string((string) $request->getVendorData());
        $cleanPackageName = Database::escape_string($archivedFilename);

        $sql = "INSERT INTO $table (
                    pens_version,
                    package_type,
                    package_type_version,
                    package_format,
                    package_id,
                    client,
                    vendor_data,
                    package_name,
                    created_at
                ) VALUES (
                    '$cleanPensVersion',
                    '$cleanPackageType',
                    '$cleanPackageTypeVersion',
                    '$cleanPackageFormat',
                    '$cleanPackageId',
                    '$cleanClient',
                    '$cleanVendorData',
                    '$cleanPackageName',
                    '$createdAt'
                )
                ON DUPLICATE KEY UPDATE
                    pens_version = VALUES(pens_version),
                    package_type = VALUES(package_type),
                    package_type_version = VALUES(package_type_version),
                    package_format = VALUES(package_format),
                    client = VALUES(client),
                    vendor_data = VALUES(vendor_data),
                    package_name = VALUES(package_name),
                    updated_at = '$createdAt'";

        Database::query($sql);
    }

    /**
     * Send a receipt callback to the client.
     */
    private function sendReceipt(PENSRequestCollect $request, PENSResponse $response): void
    {
        $this->sendCallback($request, $response, 'receipt');
    }

    /**
     * Send an alert callback to the client.
     */
    private function sendAlert(PENSRequestCollect $request, PENSResponse $response): void
    {
        $this->sendCallback($request, $response, 'alert');
    }

    /**
     * Send either a receipt or an alert callback.
     */
    private function sendCallback(PENSRequestCollect $request, PENSResponse $response, string $mode): void
    {
        $url = 'alert' === $mode ? $request->getAlerts() : $request->getReceipt();

        if (empty($url)) {
            return;
        }

        $urlComponents = parse_url($url);
        $scheme = strtolower((string) ($urlComponents['scheme'] ?? ''));

        if ('mailto' === $scheme) {
            $to = (string) ($urlComponents['path'] ?? '');

            if ('' !== $to) {
                $subject = 'alert' === $mode
                    ? 'PENS Alert for '.$request->getPackageId()
                    : 'PENS Receipt for '.$request->getPackageId();

                mail($to, $subject, (string) $response);
            }

            return;
        }

        if (!in_array($scheme, ['http', 'https'], true)) {
            return;
        }

        if (!$this->isAllowedCallbackUrl($url)) {
            return;
        }

        $parameters = 'alert' === $mode
            ? array_merge($request->getSendAlertArray(), $response->getArray())
            : array_merge($request->getSendReceiptArray(), $response->getArray());

        // SSRF-safe callback: the same NoPrivateNetworkHttpClient guard blocks a
        // receipt/alert POST aimed at loopback or internal hosts, including the
        // IPv4-mapped IPv6 literal that defeated the static host allowlist.
        try {
            SafeHttpClientHelper::create()
                ->request('POST', $url, [
                    'body' => $parameters,
                    'timeout' => 15,
                    'max_duration' => 60,
                ])
                ->getContent(false);
        } catch (ExceptionInterface $exception) {
            error_log('[Pens][sendCallback] safe callback failed: '.$exception->getMessage());
        }
    }

    /**
     * Build a unique final path inside archive/pens.
     */
    private function buildUniqueArchivePath(string $archiveDirectory, string $filename): string
    {
        $archiveDirectory = rtrim($archiveDirectory, '/').'/';
        $targetPath = $archiveDirectory.$filename;

        if (!file_exists($targetPath)) {
            return $targetPath;
        }

        $pathInfo = pathinfo($filename);
        $baseName = $pathInfo['filename'] ?? 'package';
        $extension = empty($pathInfo['extension']) ? '' : '.'.$pathInfo['extension'];

        return $archiveDirectory.$baseName.'_'.date('YmdHis').'_'.uniqid('', false).$extension;
    }

    /**
     * Normalize the archived filename.
     */
    private function sanitizeFilename(string $filename): string
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);

        return trim((string) $filename, '._');
    }

    /**
     * Allow package URLs pointing to public hosts or to the current Chamilo host.
     */
    private function isAllowedPackageUrl(string $url): bool
    {
        return $this->isAllowedRemoteUrl($url, true);
    }

    /**
     * Allow callback URLs pointing to public hosts only (no private/reserved ranges).
     */
    private function isAllowedCallbackUrl(string $url): bool
    {
        return $this->isAllowedRemoteUrl($url, false);
    }

    /**
     * Allow only public HTTP(S) URLs, except the current Chamilo host for local development.
     */
    private function isAllowedRemoteUrl(string $url, bool $allowCurrentHost): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, self::ALLOWED_REMOTE_SCHEMES, true)) {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ('' === $host) {
            return false;
        }

        // Strip IPv6 brackets so literals such as [::1] or the IPv4-mapped
        // [0:0:0:0:0:ffff:7f00:1] are normalized before any host comparison.
        $host = trim($host, '[]');
        if ('' === $host) {
            return false;
        }

        if ($this->isLocalHostName($host)) {
            return $allowCurrentHost && $this->isCurrentApplicationHost($host);
        }

        // Reject the URL if any address the host will connect to maps into a
        // loopback/private/reserved range once normalized through inet_pton.
        foreach ($this->resolveHostAddresses($host) as $ip) {
            if (!$this->isPublicIpAddress($ip)) {
                return $allowCurrentHost && $this->isCurrentApplicationHost($host);
            }
        }

        return true;
    }

    /**
     * Check whether the host matches the current Chamilo host.
     */
    private function isCurrentApplicationHost(string $host): bool
    {
        $host = strtolower(trim($host));
        $currentHost = strtolower((string) parse_url(api_get_path(WEB_PATH), PHP_URL_HOST));

        if ('' !== $currentHost && $host === $currentHost) {
            return true;
        }

        return false;
    }

    /**
     * Check whether the host is a classic local hostname or loopback address.
     */
    private function isLocalHostName(string $host): bool
    {
        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    /**
     * Resolve the host to the list of IP addresses cURL will connect to.
     *
     * Literal IPs (v4 or v6) are returned as-is; named hosts are resolved
     * through gethostbyname, matching the previous behaviour for hostnames.
     *
     * @return string[]
     */
    private function resolveHostAddresses(string $host): array
    {
        if (false !== filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $resolvedIp = gethostbyname($host);

        if ($resolvedIp !== $host && false !== filter_var($resolvedIp, FILTER_VALIDATE_IP)) {
            return [$resolvedIp];
        }

        return [];
    }

    /**
     * Determine whether an IP literal is publicly routable.
     *
     * IPv4-mapped and IPv4-compatible IPv6 literals are collapsed to their
     * embedded IPv4 address so that, e.g., ::ffff:7f00:1 is judged as the
     * 127.0.0.1 loopback it really targets instead of slipping through as an
     * unrecognized IPv6 host.
     */
    private function isPublicIpAddress(string $ip): bool
    {
        $binary = inet_pton($ip);

        if (false === $binary) {
            return false;
        }

        if (16 === strlen($binary)) {
            $prefix = substr($binary, 0, 12);
            $ipv4Mapped = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff";
            $ipv4Compatible = str_repeat("\x00", 12);

            if ($prefix === $ipv4Mapped || $prefix === $ipv4Compatible) {
                $ip = (string) inet_ntop(substr($binary, 12));
            }
        }

        return false !== filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Verify that the downloaded file looks like a ZIP archive.
     */
    private function hasZipSignature(string $path): bool
    {
        $handle = fopen($path, 'rb');
        if (false === $handle) {
            return false;
        }

        $signature = fread($handle, 4);
        fclose($handle);

        return in_array($signature, ["PK\x03\x04", "PK\x05\x06", "PK\x07\x08"], true);
    }

}
