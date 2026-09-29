<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Pens;

use Chamilo\CoreBundle\Helpers\SafeHttpClientHelper;
use Closure;
use InvalidArgumentException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Throwable;

/**
 * SSRF-safe transport for PENS package downloads and acknowledgement callbacks.
 *
 * The current Chamilo host receives no private-network exemption. A current-host
 * URL is accepted only when it resolves to a public address, exactly like every
 * other destination. Redirects remain disabled to preserve the previous cURL
 * behavior and downloads are never buffered before the byte limit is enforced.
 */
final class PensRemoteTransport
{
    public const int DEFAULT_MAX_DOWNLOAD_BYTES = 104857600; // 100 MB

    private const int CONNECTION_TIMEOUT_SECONDS = 15;
    private const int DOWNLOAD_TIMEOUT_SECONDS = 300;
    private const int CALLBACK_TIMEOUT_SECONDS = 60;

    private HttpClientInterface $httpClient;

    /**
     * @var Closure(resource, string): (int|false)
     */
    private Closure $streamWriter;

    /**
     * @param null|callable(resource, string): (int|false) $streamWriter
     */
    public function __construct(
        ?HttpClientInterface $httpClient = null,
        private readonly int $maxDownloadBytes = self::DEFAULT_MAX_DOWNLOAD_BYTES,
        ?callable $streamWriter = null
    ) {
        if ($this->maxDownloadBytes < 1) {
            throw new InvalidArgumentException('The PENS download limit must be greater than zero.');
        }

        $this->httpClient = SafeHttpClientHelper::create($httpClient);

        $defaultStreamWriter = static function (mixed $stream, string $content): false|int {
            if (!\is_resource($stream)) {
                return false;
            }

            return fwrite($stream, $content);
        };
        $this->streamWriter = null === $streamWriter
            ? $defaultStreamWriter
            : Closure::fromCallable($streamWriter);
    }

    /**
     * @param mixed $destination
     */
    public function downloadToStream(
        string $url,
        $destination,
        ?string $userId = null,
        ?string $password = null
    ): int {
        $this->assertHttpUrl($url);

        if (!\is_resource($destination)) {
            throw new PensRemoteException(PensRemoteException::STORAGE_FAILURE, 'The PENS package destination is not writable.');
        }

        $options = [
            'buffer' => false,
            'max_redirects' => 0,
            'timeout' => self::DOWNLOAD_TIMEOUT_SECONDS,
            'max_duration' => self::DOWNLOAD_TIMEOUT_SECONDS,
        ];

        if (null !== $userId) {
            $options['auth_basic'] = [$userId, (string) $password];
        }

        $response = null;

        try {
            $response = $this->httpClient->request('GET', $url, $options);
            $this->awaitConnection($response);
            $statusCode = $response->getStatusCode();
            $headers = $response->getHeaders(false);
        } catch (ExceptionInterface $exception) {
            $response?->cancel();

            throw new PensRemoteException(PensRemoteException::RETRIEVAL_FAILED, 'The PENS package URL could not be reached safely.', $exception);
        }

        if (401 === $statusCode || 403 === $statusCode) {
            $response->cancel();

            throw new PensRemoteException(PensRemoteException::ACCESS_DENIED, 'The PENS package server rejected the supplied credentials.');
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $response->cancel();

            throw new PensRemoteException(PensRemoteException::RETRIEVAL_FAILED, 'The PENS package server returned an unsuccessful response.');
        }

        $contentLength = $headers['content-length'][0] ?? null;
        if (\is_string($contentLength)
            && ctype_digit($contentLength)
            && (int) $contentLength > $this->maxDownloadBytes
        ) {
            $response->cancel();

            throw new PensRemoteException(PensRemoteException::RETRIEVAL_FAILED, 'The PENS package exceeds the configured download limit.');
        }

        $downloadedBytes = 0;

        try {
            foreach ($this->httpClient->stream($response) as $chunk) {
                $content = $chunk->getContent();
                $chunkBytes = \strlen($content);

                if (0 === $chunkBytes) {
                    continue;
                }

                if ($chunkBytes > $this->maxDownloadBytes - $downloadedBytes) {
                    $response->cancel();

                    throw new PensRemoteException(PensRemoteException::RETRIEVAL_FAILED, 'The PENS package exceeds the configured download limit.');
                }

                $this->writeAll($destination, $content);
                $downloadedBytes += $chunkBytes;
            }
        } catch (PensRemoteException $exception) {
            $response->cancel();

            throw $exception;
        } catch (ExceptionInterface $exception) {
            $response->cancel();

            throw new PensRemoteException(PensRemoteException::RETRIEVAL_FAILED, 'The PENS package download was interrupted.', $exception);
        }

        if (0 === $downloadedBytes) {
            throw new PensRemoteException(PensRemoteException::RETRIEVAL_FAILED, 'The PENS package response was empty.');
        }

        return $downloadedBytes;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function sendCallback(string $url, array $parameters): void
    {
        $this->assertHttpUrl($url);

        $response = null;

        try {
            $response = $this->httpClient->request('POST', $url, [
                'body' => $parameters,
                'buffer' => false,
                'max_redirects' => 0,
                'timeout' => self::CALLBACK_TIMEOUT_SECONDS,
                'max_duration' => self::CALLBACK_TIMEOUT_SECONDS,
            ]);
            $this->awaitConnection($response);
            $response->getStatusCode();
            $response->cancel();
        } catch (ExceptionInterface $exception) {
            $response?->cancel();

            throw new PensRemoteException(PensRemoteException::CALLBACK_FAILED, 'The PENS callback URL could not be reached safely.', $exception);
        }
    }

    private function assertHttpUrl(string $url): void
    {
        if ($url !== trim($url)) {
            throw new PensRemoteException(PensRemoteException::INVALID_URL, 'The PENS URL is malformed.');
        }

        $parts = parse_url($url);
        $scheme = \is_array($parts) ? strtolower($parts['scheme'] ?? '') : '';
        $host = \is_array($parts) ? $parts['host'] ?? '' : '';

        if (!\in_array($scheme, ['http', 'https'], true) || '' === $host) {
            throw new PensRemoteException(PensRemoteException::INVALID_URL, 'Only absolute HTTP(S) PENS URLs are allowed.');
        }
    }

    /**
     * Bound connection setup separately from the longer response-body timeout.
     */
    private function awaitConnection(ResponseInterface $response): void
    {
        foreach ($this->httpClient->stream($response, self::CONNECTION_TIMEOUT_SECONDS) as $chunk) {
            if (!$chunk->isTimeout() || (float) $response->getInfo('pretransfer_time') > 0.0) {
                return;
            }

            throw new TransportException('The PENS remote connection timed out.');
        }
    }

    /**
     * @param resource $destination
     */
    private function writeAll($destination, string $content): void
    {
        $offset = 0;
        $contentLength = \strlen($content);

        while ($offset < $contentLength) {
            try {
                $writtenBytes = ($this->streamWriter)($destination, substr($content, $offset));
            } catch (Throwable $exception) {
                throw new PensRemoteException(PensRemoteException::STORAGE_FAILURE, 'Writing the PENS package failed.', $exception);
            }

            if (false === $writtenBytes || $writtenBytes < 1 || $writtenBytes > $contentLength - $offset) {
                throw new PensRemoteException(PensRemoteException::STORAGE_FAILURE, 'Writing the PENS package failed.');
            }

            $offset += $writtenBytes;
        }
    }
}
