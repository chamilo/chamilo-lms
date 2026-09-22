<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Chat;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const LOCK_EX;
use const LOCK_UN;

final readonly class VideoChatSignalService
{
    private const int SIGNAL_TTL_SECONDS = 120;
    private const int MAX_QUEUE_SIZE = 100;

    public function __construct(
        #[Autowire('%kernel.cache_dir%')]
        private string $cacheDir,
    ) {}

    /**
     * @param array<string, mixed> $signal
     */
    public function push(int $recipientId, array $signal): void
    {
        if ($recipientId <= 0) {
            throw new RuntimeException('Invalid video-chat signal recipient.');
        }

        $handle = $this->openQueue($recipientId);

        try {
            $this->lock($handle);
            $queue = $this->readQueue($handle);
            $queue = $this->removeExpired($queue);
            $queue[] = $signal;

            if (\count($queue) > self::MAX_QUEUE_SIZE) {
                $queue = \array_slice($queue, -self::MAX_QUEUE_SIZE);
            }

            $this->writeQueue($handle, $queue);
        } finally {
            $this->unlockAndClose($handle);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pull(int $recipientId): array
    {
        if ($recipientId <= 0) {
            return [];
        }

        $handle = $this->openQueue($recipientId);

        try {
            $this->lock($handle);
            $queue = $this->removeExpired($this->readQueue($handle));
            $this->writeQueue($handle, []);

            return $queue;
        } finally {
            $this->unlockAndClose($handle);
        }
    }

    /**
     * @return resource
     */
    private function openQueue(int $recipientId)
    {
        $directory = $this->cacheDir.'/video_chat_signals';
        if (!is_dir($directory) && !@mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not create the video-chat signaling directory.');
        }

        $path = $directory.'/'.hash('sha256', (string) $recipientId).'.json';
        $handle = @fopen($path, 'c+');
        if (false === $handle) {
            throw new RuntimeException('Could not open the video-chat signaling queue.');
        }

        return $handle;
    }

    /**
     * @param resource $handle
     */
    private function lock($handle): void
    {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Could not lock the video-chat signaling queue.');
        }
    }

    /**
     * @param resource $handle
     *
     * @return list<array<string, mixed>>
     */
    private function readQueue($handle): array
    {
        rewind($handle);
        $contents = stream_get_contents($handle);
        if (false === $contents || '' === trim($contents)) {
            return [];
        }

        $decoded = json_decode($contents, true);
        if (!\is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, 'is_array'));
    }

    /**
     * @param resource                   $handle
     * @param list<array<string, mixed>> $queue
     */
    private function writeQueue($handle, array $queue): void
    {
        rewind($handle);
        if (!ftruncate($handle, 0)) {
            throw new RuntimeException('Could not reset the video-chat signaling queue.');
        }

        if ([] === $queue) {
            fflush($handle);

            return;
        }

        $json = json_encode($queue, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        if (false === fwrite($handle, $json)) {
            throw new RuntimeException('Could not write the video-chat signaling queue.');
        }

        fflush($handle);
    }

    /**
     * @param list<array<string, mixed>> $queue
     *
     * @return list<array<string, mixed>>
     */
    private function removeExpired(array $queue): array
    {
        $minimumTimestamp = time() - self::SIGNAL_TTL_SECONDS;

        return array_values(array_filter(
            $queue,
            static fn (array $signal): bool => (int) ($signal['timestamp'] ?? 0) >= $minimumTimestamp,
        ));
    }

    /**
     * @param resource $handle
     */
    private function unlockAndClose($handle): void
    {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
