<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Upload;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

final class ClamAvScanner implements AntivirusScannerInterface
{
    public function __construct(
        #[Autowire('%env(default:chamilo.antivirus.socket:CHAMILO_ANTIVIRUS_SOCKET)%')]
        private readonly string $endpoint = 'unix:///run/clamav/clamd.ctl',
        #[Autowire('%env(int:default:chamilo.antivirus.timeout:CHAMILO_ANTIVIRUS_TIMEOUT)%')]
        private readonly int $timeout = 30,
        #[Autowire('%env(int:default:chamilo.antivirus.max_bytes:CHAMILO_ANTIVIRUS_MAX_BYTES)%')]
        private readonly int $maxBytes = 104857600,
    ) {}

    public function scan($stream): array
    {
        $socket = null;
        $size = 0;
        $digest = null;
        $knownSize = null;
        $deadline = microtime(true) + max(1, $this->timeout);

        try {
            if (!\is_resource($stream) || $this->maxBytes < 1 || $this->timeout < 1) {
                throw new RuntimeException('Invalid antivirus scan configuration or input.');
            }
            $stat = @fstat($stream);
            if (\is_array($stat) && 0100000 === ($stat['mode'] & 0170000)) {
                $knownSize = (int) $stat['size'];
                if ($knownSize > $this->maxBytes) {
                    throw new RuntimeException('Antivirus size limit exceeded.');
                }
            }
            // The daemon protocol is unauthenticated; never connect to arbitrary hosts.
            if (!str_starts_with($this->endpoint, 'unix:///')
                && 1 !== preg_match('/\Atcp:\/\/(?:127\.0\.0\.1|\[::1\]):[0-9]{1,5}\z/D', $this->endpoint)) {
                throw new RuntimeException('Invalid antivirus endpoint.');
            }
            $socket = @stream_socket_client($this->endpoint, $errno, $error, min(5, $this->timeout));
            if (false === $socket) {
                throw new RuntimeException('Antivirus connection failed.');
            }
            stream_set_blocking($socket, false);
            $hash = hash_init('sha256');
            $this->write($socket, "zINSTREAM\0", $deadline);
            while (!feof($stream)) {
                $chunk = fread($stream, 65536);
                if (false === $chunk || ('' === $chunk && !feof($stream))) {
                    throw new RuntimeException('Cannot read antivirus input.');
                }
                if ('' === $chunk) {
                    break;
                }
                $size += \strlen($chunk);
                if ($size > $this->maxBytes) {
                    throw new RuntimeException('Antivirus size limit exceeded.');
                }
                hash_update($hash, $chunk);
                $this->write($socket, pack('N', \strlen($chunk)).$chunk, $deadline);
            }
            $digest = hash_final($hash);
            $this->write($socket, pack('N', 0), $deadline);
            $reply = '';
            while (!str_contains($reply, "\0")) {
                $this->wait($socket, false, $deadline);
                $chunk = fread($socket, 4096);
                if (false === $chunk || '' === $chunk || \strlen($reply) + \strlen($chunk) > 8192) {
                    throw new RuntimeException('Invalid antivirus response.');
                }
                $reply .= $chunk;
            }
            if ("stream: OK\0" === $reply) {
                return ['status' => 'clean', 'threat' => null, 'sha256' => $digest, 'size' => $size];
            }
            if (1 === preg_match('/\Astream: ([^\x00-\x1f\x7f]+) FOUND\x00\z/D', $reply, $matches)) {
                return ['status' => 'infected', 'threat' => $matches[1], 'sha256' => $digest, 'size' => $size];
            }
        } catch (Throwable) {
            // No transport details or user-controlled content in the client response.
        } finally {
            if (\is_resource($socket)) {
                fclose($socket);
            }
        }

        return ['status' => 'error', 'threat' => null, 'sha256' => $digest, 'size' => $knownSize ?? $size];
    }

    /**
     * @param resource $socket
     */
    private function write($socket, string $bytes, float $deadline): void
    {
        while ('' !== $bytes) {
            $this->wait($socket, true, $deadline);
            $written = @fwrite($socket, $bytes);
            if (false === $written || 0 === $written) {
                throw new RuntimeException('Antivirus write failed.');
            }
            $bytes = substr($bytes, $written);
        }
    }

    /**
     * @param resource $socket
     */
    private function wait($socket, bool $write, float $deadline): void
    {
        $remaining = $deadline - microtime(true);
        if ($remaining <= 0) {
            throw new RuntimeException('Antivirus timeout.');
        }
        $read = $write ? [] : [$socket];
        $writes = $write ? [$socket] : [];
        $except = [];
        if (1 !== @stream_select($read, $writes, $except, (int) $remaining, (int) (($remaining - (int) $remaining) * 1000000))) {
            throw new RuntimeException('Antivirus timeout or socket error.');
        }
    }
}
