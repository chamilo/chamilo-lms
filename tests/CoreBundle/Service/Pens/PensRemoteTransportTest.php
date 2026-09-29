<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Pens;

use Chamilo\CoreBundle\Service\Pens\PensRemoteException;
use Chamilo\CoreBundle\Service\Pens\PensRemoteTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Response\ResponseStream;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

use const SEEK_SET;

final class PensRemoteTransportTest extends TestCase
{
    private const string PUBLIC_IPV4_URL = 'https://93.184.216.34/package.zip';

    public function testDownloadsPublicPackageWithoutBufferingOrRedirects(): void
    {
        /** @var null|array{0: string, 1: string, 2: array<array-key, mixed>} $capturedRequest */
        $capturedRequest = null;
        $mockClient = new MockHttpClient(
            static function (string $method, string $url, array $options) use (&$capturedRequest): MockResponse {
                $capturedRequest = [$method, $url, $options];

                return new MockResponse((static function (): iterable {
                    yield "PK\x03\x04";

                    yield 'package-data';
                })());
            }
        );
        $recordingClient = new PensRecordingHttpClient($mockClient);
        $destination = $this->createDestination();

        $downloadedBytes = (new PensRemoteTransport($recordingClient, 100))->downloadToStream(
            self::PUBLIC_IPV4_URL,
            $destination,
            'pens-user',
            'pens-password'
        );

        self::assertSame(16, $downloadedBytes);
        self::assertSame("PK\x03\x04package-data", $this->readDestination($destination));
        self::assertNotNull($capturedRequest);
        self::assertSame('GET', $capturedRequest[0]);
        self::assertSame(self::PUBLIC_IPV4_URL, $capturedRequest[1]);
        self::assertFalse($capturedRequest[2]['buffer']);
        self::assertSame(0, $capturedRequest[2]['max_redirects']);
        self::assertSame(300.0, $capturedRequest[2]['timeout']);
        self::assertSame(300.0, $capturedRequest[2]['max_duration']);
        self::assertContains('Authorization: Basic cGVucy11c2VyOnBlbnMtcGFzc3dvcmQ=', $capturedRequest[2]['headers']);
        self::assertSame([15.0, null], $recordingClient->streamTimeouts);

        fclose($destination);
    }

    /**
     * @dataProvider blockedUrlProvider
     */
    public function testBlocksPrivateTranslatedAndUnresolvedHosts(string $url): void
    {
        $mockClient = new MockHttpClient(new MockResponse("PK\x03\x04data"));
        $destination = $this->createDestination();

        try {
            (new PensRemoteTransport($mockClient))->downloadToStream($url, $destination);
            self::fail('The unsafe PENS URL was accepted.');
        } catch (PensRemoteException $exception) {
            self::assertSame(PensRemoteException::RETRIEVAL_FAILED, $exception->getPensErrorCode());
        }

        self::assertSame(0, $mockClient->getRequestsCount());
        fclose($destination);
    }

    public static function blockedUrlProvider(): iterable
    {
        yield 'IPv4 loopback' => ['http://127.0.0.1/package.zip'];

        yield 'IPv4 private' => ['http://192.168.10.12/package.zip'];

        yield 'cloud metadata' => ['http://169.254.169.254/package.zip'];

        yield 'current localhost has no exception' => ['http://localhost/package.zip'];

        yield 'IPv6 loopback' => ['http://[::1]/package.zip'];

        yield 'IPv6 unique local' => ['http://[fc00::1]/package.zip'];

        yield 'IPv4-mapped IPv6' => ['http://[::ffff:127.0.0.1]/package.zip'];

        yield 'expanded IPv4-mapped IPv6' => ['http://[0:0:0:0:0:ffff:7f00:1]/package.zip'];

        yield 'IPv4-compatible IPv6' => ['http://[::127.0.0.1]/package.zip'];

        yield 'NAT64' => ['http://[64:ff9b::7f00:1]/package.zip'];

        yield '6to4' => ['http://[2002:7f00:1::]/package.zip'];

        yield 'unresolved host' => ['http://does-not-resolve.invalid/package.zip'];
    }

    /**
     * @dataProvider publicUrlProvider
     */
    public function testAllowsPublicIpv4AndIpv6Literals(string $url): void
    {
        $mockClient = new MockHttpClient(new MockResponse("PK\x03\x04data"));
        $destination = $this->createDestination();

        self::assertSame(8, (new PensRemoteTransport($mockClient))->downloadToStream($url, $destination));
        self::assertSame(1, $mockClient->getRequestsCount());

        fclose($destination);
    }

    public static function publicUrlProvider(): iterable
    {
        yield 'public IPv4' => [self::PUBLIC_IPV4_URL];

        yield 'public IPv6' => ['https://[2606:4700:4700::1111]/package.zip'];
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testRejectsNonHttpAndMalformedUrls(string $url): void
    {
        $mockClient = new MockHttpClient(new MockResponse("PK\x03\x04data"));
        $destination = $this->createDestination();

        try {
            (new PensRemoteTransport($mockClient))->downloadToStream($url, $destination);
            self::fail('The invalid PENS URL was accepted.');
        } catch (PensRemoteException $exception) {
            self::assertSame(PensRemoteException::INVALID_URL, $exception->getPensErrorCode());
        }

        self::assertSame(0, $mockClient->getRequestsCount());
        fclose($destination);
    }

    public static function invalidUrlProvider(): iterable
    {
        yield 'file scheme' => ['file:///etc/passwd'];

        yield 'FTP scheme' => ['ftp://93.184.216.34/package.zip'];

        yield 'relative URL' => ['/package.zip'];

        yield 'leading whitespace' => [' '.self::PUBLIC_IPV4_URL];

        yield 'missing host' => ['https:///package.zip'];
    }

    public function testRejectsRedirectWithoutFollowingPrivateLocation(): void
    {
        $mockClient = new MockHttpClient(new MockResponse('', [
            'http_code' => 302,
            'response_headers' => ['location: http://127.0.0.1/internal'],
        ]));
        $destination = $this->createDestination();

        try {
            (new PensRemoteTransport($mockClient))->downloadToStream(self::PUBLIC_IPV4_URL, $destination);
            self::fail('The redirecting PENS package URL was accepted.');
        } catch (PensRemoteException $exception) {
            self::assertSame(PensRemoteException::RETRIEVAL_FAILED, $exception->getPensErrorCode());
        }

        self::assertSame(1, $mockClient->getRequestsCount());
        fclose($destination);
    }

    /**
     * @dataProvider authenticationFailureProvider
     */
    public function testMapsAuthenticationFailure(int $statusCode): void
    {
        $mockClient = new MockHttpClient(new MockResponse('', ['http_code' => $statusCode]));
        $destination = $this->createDestination();

        try {
            (new PensRemoteTransport($mockClient))->downloadToStream(self::PUBLIC_IPV4_URL, $destination);
            self::fail('The authentication failure was ignored.');
        } catch (PensRemoteException $exception) {
            self::assertSame(PensRemoteException::ACCESS_DENIED, $exception->getPensErrorCode());
        }

        fclose($destination);
    }

    public static function authenticationFailureProvider(): iterable
    {
        yield 'unauthorized' => [401];

        yield 'forbidden' => [403];
    }

    /**
     * @dataProvider unsuccessfulStatusProvider
     */
    public function testMapsUnsuccessfulResponse(int $statusCode): void
    {
        $mockClient = new MockHttpClient(new MockResponse('', ['http_code' => $statusCode]));
        $destination = $this->createDestination();

        try {
            (new PensRemoteTransport($mockClient))->downloadToStream(self::PUBLIC_IPV4_URL, $destination);
            self::fail('The unsuccessful PENS package response was accepted.');
        } catch (PensRemoteException $exception) {
            self::assertSame(PensRemoteException::RETRIEVAL_FAILED, $exception->getPensErrorCode());
        }

        fclose($destination);
    }

    public static function unsuccessfulStatusProvider(): iterable
    {
        yield 'not found' => [404];

        yield 'server error' => [500];
    }

    public function testMapsInterruptedAndEmptyResponses(): void
    {
        $interruptedClient = new MockHttpClient(new MockResponse((static function (): iterable {
            yield "PK\x03\x04";

            yield new TransportException('Connection interrupted.');
        })()));
        $interruptedDestination = $this->createDestination();

        try {
            (new PensRemoteTransport($interruptedClient))->downloadToStream(
                self::PUBLIC_IPV4_URL,
                $interruptedDestination
            );
            self::fail('The interrupted PENS package response was accepted.');
        } catch (PensRemoteException $exception) {
            self::assertSame(PensRemoteException::RETRIEVAL_FAILED, $exception->getPensErrorCode());
        }

        self::assertSame("PK\x03\x04", $this->readDestination($interruptedDestination));
        fclose($interruptedDestination);

        $emptyDestination = $this->createDestination();

        try {
            (new PensRemoteTransport(new MockHttpClient(new MockResponse())))->downloadToStream(
                self::PUBLIC_IPV4_URL,
                $emptyDestination
            );
            self::fail('The empty PENS package response was accepted.');
        } catch (PensRemoteException $exception) {
            self::assertSame(PensRemoteException::RETRIEVAL_FAILED, $exception->getPensErrorCode());
        }

        fclose($emptyDestination);
    }

    public function testMapsConnectionGateTimeout(): void
    {
        $mockClient = new MockHttpClient(new MockResponse("PK\x03\x04data"));
        $recordingClient = new PensRecordingHttpClient($mockClient, true);
        $destination = $this->createDestination();

        try {
            (new PensRemoteTransport($recordingClient))->downloadToStream(self::PUBLIC_IPV4_URL, $destination);
            self::fail('The timed-out PENS connection was accepted.');
        } catch (PensRemoteException $exception) {
            self::assertSame(PensRemoteException::RETRIEVAL_FAILED, $exception->getPensErrorCode());
        }

        self::assertSame([15.0], $recordingClient->streamTimeouts);
        self::assertSame('', $this->readDestination($destination));
        fclose($destination);
    }

    public function testConnectionGateAllowsAnEstablishedConnectionToKeepWaiting(): void
    {
        $mockClient = new MockHttpClient(new MockResponse("PK\x03\x04data", [
            'pretransfer_time' => 0.001,
        ]));
        $recordingClient = new PensRecordingHttpClient($mockClient, true);
        $destination = $this->createDestination();

        self::assertSame(
            8,
            (new PensRemoteTransport($recordingClient))->downloadToStream(self::PUBLIC_IPV4_URL, $destination)
        );
        self::assertSame("PK\x03\x04data", $this->readDestination($destination));
        self::assertNotEmpty($recordingClient->streamTimeouts);
        self::assertSame(15.0, $recordingClient->streamTimeouts[0]);
        self::assertNull($recordingClient->streamTimeouts[array_key_last($recordingClient->streamTimeouts)]);
        fclose($destination);
    }

    public function testRejectsDeclaredAndStreamedOversizePackagesBeforeWritingPastLimit(): void
    {
        $boundaryClient = new MockHttpClient(new MockResponse("PK\x03\x04data"));
        $boundaryDestination = $this->createDestination();

        self::assertSame(
            8,
            (new PensRemoteTransport($boundaryClient, 8))->downloadToStream(
                self::PUBLIC_IPV4_URL,
                $boundaryDestination
            )
        );
        self::assertSame("PK\x03\x04data", $this->readDestination($boundaryDestination));
        fclose($boundaryDestination);

        $declaredOversizeClient = new MockHttpClient(new MockResponse("PK\x03\x04data", [
            'response_headers' => ['content-length: 9'],
        ]));
        $declaredDestination = $this->createDestination();

        try {
            (new PensRemoteTransport($declaredOversizeClient, 8))->downloadToStream(
                self::PUBLIC_IPV4_URL,
                $declaredDestination
            );
            self::fail('The declared oversize PENS package was accepted.');
        } catch (PensRemoteException $exception) {
            self::assertSame(PensRemoteException::RETRIEVAL_FAILED, $exception->getPensErrorCode());
        }

        self::assertSame('', $this->readDestination($declaredDestination));
        fclose($declaredDestination);

        $streamedOversizeClient = new MockHttpClient(new MockResponse((static function (): iterable {
            yield "PK\x03\x04";

            yield '12345';
        })()));
        $streamedDestination = $this->createDestination();

        try {
            (new PensRemoteTransport($streamedOversizeClient, 8))->downloadToStream(
                self::PUBLIC_IPV4_URL,
                $streamedDestination
            );
            self::fail('The streamed oversize PENS package was accepted.');
        } catch (PensRemoteException $exception) {
            self::assertSame(PensRemoteException::RETRIEVAL_FAILED, $exception->getPensErrorCode());
        }

        self::assertSame("PK\x03\x04", $this->readDestination($streamedDestination));
        fclose($streamedDestination);
    }

    public function testDetectsFailedWritesAndCompletesPartialWrites(): void
    {
        foreach ([false, 0] as $writeResult) {
            $failedWriteClient = new MockHttpClient(new MockResponse("PK\x03\x04data"));
            $failedDestination = $this->createDestination();
            $failedWriter = static fn (mixed $stream, string $content): false|int => $writeResult;

            try {
                (new PensRemoteTransport($failedWriteClient, 100, $failedWriter))->downloadToStream(
                    self::PUBLIC_IPV4_URL,
                    $failedDestination
                );
                self::fail('The failed PENS package write was ignored.');
            } catch (PensRemoteException $exception) {
                self::assertSame(PensRemoteException::STORAGE_FAILURE, $exception->getPensErrorCode());
            }

            fclose($failedDestination);
        }

        $partialWriteClient = new MockHttpClient(new MockResponse("PK\x03\x04data"));
        $partialDestination = $this->createDestination();

        $partialWriter = static function (mixed $stream, string $content): false|int {
            if (!\is_resource($stream)) {
                return false;
            }

            return fwrite($stream, substr($content, 0, 2));
        };

        self::assertSame(
            8,
            (new PensRemoteTransport($partialWriteClient, 100, $partialWriter))->downloadToStream(
                self::PUBLIC_IPV4_URL,
                $partialDestination
            )
        );
        self::assertSame("PK\x03\x04data", $this->readDestination($partialDestination));
        fclose($partialDestination);
    }

    public function testSendsPublicCallbackWithoutFollowingRedirect(): void
    {
        /** @var null|array{0: string, 1: string, 2: array<array-key, mixed>} $capturedRequest */
        $capturedRequest = null;
        $mockClient = new MockHttpClient(
            static function (string $method, string $url, array $options) use (&$capturedRequest): MockResponse {
                $capturedRequest = [$method, $url, $options];

                return new MockResponse((static function (): iterable {
                    yield new TransportException('The callback body must not be consumed.');
                })(), [
                    'http_code' => 302,
                    'response_headers' => ['location: http://127.0.0.1/internal'],
                ]);
            }
        );
        $recordingClient = new PensRecordingHttpClient($mockClient);

        (new PensRemoteTransport($recordingClient))->sendCallback(
            'https://93.184.216.34/callback',
            ['command' => 'receipt', 'package-id' => '42']
        );

        self::assertSame(1, $mockClient->getRequestsCount());
        self::assertNotNull($capturedRequest);
        self::assertSame('POST', $capturedRequest[0]);
        self::assertSame(0, $capturedRequest[2]['max_redirects']);
        self::assertSame(60.0, $capturedRequest[2]['timeout']);
        self::assertSame(60.0, $capturedRequest[2]['max_duration']);
        self::assertSame('command=receipt&package-id=42', $capturedRequest[2]['body']);
        self::assertSame([15.0], $recordingClient->streamTimeouts);
    }

    public function testBlocksPrivateCallbackBeforeInnerClientRequest(): void
    {
        $mockClient = new MockHttpClient(new MockResponse());

        try {
            (new PensRemoteTransport($mockClient))->sendCallback(
                'http://[::ffff:127.0.0.1]/callback',
                ['command' => 'alert']
            );
            self::fail('The private PENS callback URL was accepted.');
        } catch (PensRemoteException $exception) {
            self::assertSame(PensRemoteException::CALLBACK_FAILED, $exception->getPensErrorCode());
        }

        self::assertSame(0, $mockClient->getRequestsCount());
    }

    /**
     * @return resource
     */
    private function createDestination()
    {
        $destination = fopen('php://temp', 'w+b');
        self::assertIsResource($destination);

        return $destination;
    }

    /**
     * @param resource $destination
     */
    private function readDestination($destination): string
    {
        self::assertSame(0, fseek($destination, 0, SEEK_SET));

        return (string) stream_get_contents($destination);
    }
}

final class PensRecordingHttpClient implements HttpClientInterface
{
    /**
     * @var list<?float>
     */
    public array $streamTimeouts = [];

    public function __construct(
        private HttpClientInterface $client,
        private readonly bool $simulateConnectionTimeout = false
    ) {}

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->client->request($method, $url, $options);
    }

    public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
    {
        $this->streamTimeouts[] = $timeout;

        if ($this->simulateConnectionTimeout && 1 === \count($this->streamTimeouts)) {
            if ($responses instanceof ResponseInterface) {
                $responses = [$responses];
            }

            return new ResponseStream((static function () use ($responses): iterable {
                foreach ($responses as $response) {
                    yield $response => new PensTimeoutChunk();
                }
            })());
        }

        return $this->client->stream($responses, $timeout);
    }

    public function withOptions(array $options): static
    {
        $clone = clone $this;
        $clone->client = $this->client->withOptions($options);

        return $clone;
    }
}

final class PensTimeoutChunk implements ChunkInterface
{
    public function isTimeout(): bool
    {
        return true;
    }

    public function isFirst(): bool
    {
        return false;
    }

    public function isLast(): bool
    {
        return false;
    }

    public function getInformationalStatus(): ?array
    {
        return null;
    }

    public function getContent(): string
    {
        return '';
    }

    public function getOffset(): int
    {
        return 0;
    }

    public function getError(): ?string
    {
        return 'Idle timeout reached.';
    }
}
