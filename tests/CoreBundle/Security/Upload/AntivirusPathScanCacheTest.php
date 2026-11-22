<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Security\Upload;

use Chamilo\CoreBundle\Security\Upload\AntivirusPathScanCache;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class AntivirusPathScanCacheTest extends TestCase
{
    public function testOnlyIdenticalBytesAtTheSamePathInTheSameRequestAreReused(): void
    {
        $stack = new RequestStack();
        $stack->push(new Request());
        $cache = new AntivirusPathScanCache($stack);
        $stream = tmpfile();
        self::assertIsResource($stream);

        try {
            fwrite($stream, 'original');
            rewind($stream);
            $result = ['status' => 'clean', 'size' => 8, 'sha256' => hash('sha256', 'original')];
            self::assertFalse($cache->matches('/tmp/upload', $stream));
            $cache->remember('/tmp/upload', $result);
            self::assertTrue($cache->matches('/tmp/upload', $stream));
            self::assertSame(0, ftell($stream));
            self::assertFalse($cache->matches('/tmp/another-upload', $stream));
            fwrite($stream, 'modified');
            rewind($stream);
            self::assertFalse($cache->matches('/tmp/upload', $stream));
            self::assertSame(0, ftell($stream));
            fwrite($stream, 'original');
            rewind($stream);
            $stack->pop();
            $stack->push(new Request());
            self::assertFalse($cache->matches('/tmp/upload', $stream));
            $stack->pop();
            $cache->remember('/tmp/upload', $result);
            self::assertFalse($cache->matches('/tmp/upload', $stream));
        } finally {
            fclose($stream);
        }
    }

    public function testRejectedResultsAreNeverReused(): void
    {
        $stack = new RequestStack();
        $stack->push(new Request());
        $cache = new AntivirusPathScanCache($stack);
        $stream = tmpfile();
        self::assertIsResource($stream);

        try {
            fwrite($stream, 'test');
            rewind($stream);
            foreach (['infected', 'error'] as $status) {
                $cache->remember('/tmp/upload', ['status' => $status, 'size' => 4, 'sha256' => hash('sha256', 'test')]);
                self::assertFalse($cache->matches('/tmp/upload', $stream));
            }
        } finally {
            fclose($stream);
        }
    }
}
