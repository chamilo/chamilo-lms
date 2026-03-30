<?php

/* For licensing terms, see /license.txt. */

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

        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $request->getPackageUrl());
        curl_setopt($curlHandle, CURLOPT_HEADER, false);
        curl_setopt($curlHandle, CURLOPT_FILE, $fileHandle);
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 300);

        if (null !== $request->getPackageUrlUserId()) {
            curl_setopt(
                $curlHandle,
                CURLOPT_USERPWD,
                $request->getPackageUrlUserId().':'.$request->getPackageUrlPassword()
            );
        }

        $result = curl_exec($curlHandle);
        $curlErrorNumber = curl_errno($curlHandle);
        $curlErrorMessage = curl_error($curlHandle);
        $httpStatusCode = (int) curl_getinfo($curlHandle, CURLINFO_RESPONSE_CODE);

        curl_close($curlHandle);
        fclose($fileHandle);

        error_log('[Pens][collectPackage] curl result='.var_export($result, true).' errno='.$curlErrorNumber.' http='.$httpStatusCode.' error='.$curlErrorMessage);

        if (false === $result) {
            if (is_file($temporaryPackagePath)) {
                unlink($temporaryPackagePath);
            }

            switch ($curlErrorNumber) {
                case CURLE_UNSUPPORTED_PROTOCOL:
                    throw new PENSException(1301);

                case CURLE_URL_MALFORMAT:
                case CURLE_COULDNT_RESOLVE_PROXY:
                case CURLE_COULDNT_RESOLVE_HOST:
                case CURLE_COULDNT_CONNECT:
                case CURLE_OPERATION_TIMEOUTED:
                case 78: //CURLE_REMOTE_FILE_NOT_FOUND
                    throw new PENSException(1310);

                case CURLE_FTP_ACCESS_DENIED: //CURLE_REMOTE_ACCESS_DENIED
                    throw new PENSException(1312);

                default:
                    throw new PENSException(1301);
            }
        }

        if (401 === $httpStatusCode || 403 === $httpStatusCode) {
            unlink($temporaryPackagePath);
            error_log('[Pens][collectPackage] rejected by remote server');
            throw new PENSException(1312);
        }

        if ($httpStatusCode >= 400) {
            unlink($temporaryPackagePath);
            error_log('[Pens][collectPackage] remote server returned http >= 400');
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

        $curlHandle = curl_init($url);
        curl_setopt($curlHandle, CURLOPT_POST, true);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 60);
        curl_exec($curlHandle);
        curl_close($curlHandle);
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

        if ($this->isLocalHostName($host)) {
            return $allowCurrentHost && $this->isCurrentApplicationHost($host);
        }

        $resolvedIp = gethostbyname($host);
        if (filter_var($resolvedIp, FILTER_VALIDATE_IP)) {
            $isPublicIp = filter_var(
                $resolvedIp,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );

            if (!$isPublicIp) {
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
