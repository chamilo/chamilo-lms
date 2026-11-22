<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Security\Upload;

use Chamilo\CoreBundle\Security\Upload\ClamAvScanner;
use PHPUnit\Framework\TestCase;

final class ClamAvScannerTest extends TestCase
{
    public function testUnavailableDaemonNeverApprovesFile(): void
    {
        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);

        try {
            fwrite($stream, 'Plain test content');
            rewind($stream);
            $scanner = new ClamAvScanner('unix:///nonexistent-chamilo-antivirus/clamd.sock', 1, 1024);
            self::assertSame('error', $scanner->scan($stream)['status']);
        } finally {
            fclose($stream);
        }
    }

    public function testUnreadableInputNeverPasses(): void
    {
        self::assertSame('error', (new ClamAvScanner())->scan(false)['status']);
    }

    public function testOversizedFileIsRejected(): void
    {
        $stream = tmpfile();
        self::assertIsResource($stream);

        try {
            fwrite($stream, '12345');
            rewind($stream);
            $scanner = new ClamAvScanner('unix:///nonexistent-chamilo-antivirus/clamd.sock', 1, 4);
            $result = $scanner->scan($stream);
            self::assertSame('error', $result['status']);
            self::assertSame(5, $result['size']);
            self::assertNull($result['sha256']);
        } finally {
            fclose($stream);
        }
    }
}
