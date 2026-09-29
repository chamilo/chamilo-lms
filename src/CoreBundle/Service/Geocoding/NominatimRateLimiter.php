<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Geocoding;

use RuntimeException;
use Symfony\Component\HttpKernel\KernelInterface;

use const LOCK_EX;
use const LOCK_UN;

/**
 * Enforces Nominatim's usage policy of at most 1 request/second, platform-wide
 * across every PHP-FPM worker, through an flock()-serialized timestamp file.
 *
 * @see https://operations.osmfoundation.org/policies/nominatim/
 */
final class NominatimRateLimiter
{
    private const float MIN_INTERVAL_SECONDS = 1.0;

    private readonly string $lockFilePath;

    public function __construct(KernelInterface $kernel)
    {
        $this->lockFilePath = rtrim($kernel->getProjectDir(), '/').'/var/nominatim-geocode-request.lock';
    }

    /**
     * Blocks the caller, if needed, until at least MIN_INTERVAL_SECONDS have
     * passed since the last call anywhere on the platform, then reserves the
     * new slot before returning.
     */
    public function throttle(): void
    {
        $handle = fopen($this->lockFilePath, 'c+');

        if (false === $handle) {
            throw new RuntimeException(\sprintf('Unable to open Nominatim rate-limit lock file "%s".', $this->lockFilePath));
        }

        try {
            flock($handle, LOCK_EX);

            $lastRequestAt = (float) stream_get_contents($handle);
            $now = microtime(true);
            $wait = self::MIN_INTERVAL_SECONDS - ($now - $lastRequestAt);

            if ($wait > 0) {
                usleep((int) round($wait * 1_000_000));
                $now += $wait;
            }

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, (string) $now);
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
