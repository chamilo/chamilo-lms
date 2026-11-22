<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Upload;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;
use WeakMap;

final class AntivirusPathScanCache
{
    /**
     * @var WeakMap<Request, array<string, array{size: int, sha256: string}>>
     */
    private readonly WeakMap $approved;

    public function __construct(
        private readonly RequestStack $requests
    ) {
        $this->approved = new WeakMap();
    }

    /**
     * @param resource|false $stream
     */
    public function matches(string $path, $stream): bool
    {
        $request = $this->requests->getMainRequest();
        if (null === $request || !\is_resource($stream)) {
            return false;
        }
        $entry = $this->approved[$request][$path] ?? null;
        $stat = fstat($stream);
        if (null === $entry || false === $stat || 0100000 !== ($stat['mode'] & 0170000)
            || $entry['size'] !== $stat['size'] || 0 !== ftell($stream)) {
            return false;
        }

        // Check the actual bytes again, not merely the path, size or timestamp.
        $hash = hash_init('sha256');

        try {
            $bytes = hash_update_stream($hash, $stream, $entry['size'] + 1);

            $matches = $bytes === $entry['size'] && hash_equals($entry['sha256'], hash_final($hash));
        } finally {
            if (!rewind($stream)) {
                throw new HttpException(503, 'File upload rejected: antivirus scan could not be completed.');
            }
        }

        return $matches;
    }

    /**
     * @param array{status: string, size: int, sha256: ?string} $result
     */
    public function remember(string $path, array $result): void
    {
        $request = $this->requests->getMainRequest();
        if (null === $request || 'clean' !== $result['status'] || null === $result['sha256']) {
            return;
        }
        $entries = $this->approved[$request] ?? [];
        $entries[$path] = ['size' => $result['size'], 'sha256' => $result['sha256']];
        $approved = $this->approved;
        $approved[$request] = $entries;
    }
}
