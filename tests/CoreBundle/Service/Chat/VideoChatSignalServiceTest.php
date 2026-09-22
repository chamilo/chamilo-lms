<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Chat;

use Chamilo\CoreBundle\Service\Chat\VideoChatSignalService;
use PHPUnit\Framework\TestCase;

final class VideoChatSignalServiceTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir().'/chamilo-video-chat-test-'.bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->cacheDir);
    }

    public function testSignalsAreDeliveredOnceToTheIntendedRecipient(): void
    {
        $service = new VideoChatSignalService($this->cacheDir);
        $signal = [
            'id' => 'signal-1',
            'call_id' => '12345678-1234-1234-1234-123456789012',
            'type' => 'offer',
            'from' => 10,
            'timestamp' => time(),
        ];

        $service->push(20, $signal);

        self::assertSame([], $service->pull(21));
        self::assertSame([$signal], $service->pull(20));
        self::assertSame([], $service->pull(20));
    }

    public function testExpiredSignalsAreDiscarded(): void
    {
        $service = new VideoChatSignalService($this->cacheDir);
        $service->push(20, [
            'id' => 'expired',
            'type' => 'offer',
            'timestamp' => time() - 300,
        ]);

        self::assertSame([], $service->pull(20));
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $path = $directory.'/'.$item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
