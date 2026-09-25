<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Message;

use Chamilo\CoreBundle\Settings\SettingsManager;
use RuntimeException;
use Throwable;

use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_PASSWORD;
use const CURLOPT_PROTOCOLS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;
use const CURLOPT_USERNAME;
use const CURLPROTO_IMAP;
use const CURLPROTO_IMAPS;
use const SORT_NUMERIC;

final class MessageInboundMailboxService
{
    private const int DEFAULT_LIMIT = 20;
    private const int MAX_LIMIT = 100;

    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly MessageInboundMailService $inboundMailService,
    ) {}

    /**
     * @return array{
     *     skipped: bool,
     *     processed: int,
     *     failed: int,
     *     message_ids: int[],
     *     errors: array<int, string>
     * }
     */
    public function fetch(?int $limit = null, bool $markFailedSeen = false): array
    {
        $limit ??= self::DEFAULT_LIMIT;
        if ($limit < 1 || $limit > self::MAX_LIMIT) {
            throw new RuntimeException(\sprintf('Inbound mailbox fetch limit must be between 1 and %d.', self::MAX_LIMIT));
        }

        if (!$this->isEnabled()) {
            return [
                'skipped' => true,
                'processed' => 0,
                'failed' => 0,
                'message_ids' => [],
                'errors' => [],
            ];
        }

        $dsnValue = trim((string) $this->settingsManager->getSetting('mail.inbound_mail_dsn'));
        if ('' === $dsnValue) {
            throw new RuntimeException('Inbound mail is enabled but mail.inbound_mail_dsn is empty.');
        }

        $dsn = InboundMailboxDsn::fromString($dsnValue);
        $this->assertCurlImapSupport($dsn);

        $indexes = \array_slice($this->searchUnseen($dsn), 0, $limit);
        $processed = 0;
        $failed = 0;
        $messageIds = [];
        $errors = [];

        foreach ($indexes as $index) {
            try {
                $rawEmail = $this->fetchMessage($dsn, $index);
                $message = $this->inboundMailService->processRawEmail($rawEmail);
                $messageId = $message->getId();
                if (null !== $messageId) {
                    $messageIds[] = (int) $messageId;
                }
                ++$processed;
            } catch (Throwable $exception) {
                ++$failed;
                $errors[$index] = $exception->getMessage();

                if (!$markFailedSeen) {
                    try {
                        $this->markUnseen($dsn, $index);
                    } catch (Throwable $markException) {
                        $errors[$index] .= ' Could not restore the unread flag: '.$markException->getMessage();
                    }
                }
            }
        }

        return [
            'skipped' => false,
            'processed' => $processed,
            'failed' => $failed,
            'message_ids' => $messageIds,
            'errors' => $errors,
        ];
    }

    private function isEnabled(): bool
    {
        return 'true' === strtolower((string) $this->settingsManager->getSetting('mail.enable_inbound_mail'));
    }

    private function assertCurlImapSupport(InboundMailboxDsn $dsn): void
    {
        if (!\function_exists('curl_init') || !\function_exists('curl_version')) {
            throw new RuntimeException('The PHP cURL extension is required to collect inbound mail.');
        }

        $version = curl_version();
        $protocols = array_map('strtolower', (array) ($version['protocols'] ?? []));
        if (!\in_array($dsn->getScheme(), $protocols, true)) {
            throw new RuntimeException(\sprintf('The installed libcurl does not support the %s protocol required by the inbound mailbox DSN.', $dsn->getScheme()));
        }
    }

    /**
     * @return int[]
     */
    private function searchUnseen(InboundMailboxDsn $dsn): array
    {
        $response = $this->perform($dsn, $dsn->getMailboxUrl().'?UNSEEN');
        $indexes = [];

        if (1 === preg_match('/\*\s+SEARCH(?<indexes>[^\r\n]*)/i', $response, $matches)) {
            preg_match_all('/\d+/', (string) $matches['indexes'], $numbers);
            $indexes = array_map('intval', $numbers[0] ?? []);
        } elseif (1 === preg_match('/^\s*(?:\d+\s*)*$/', $response)) {
            preg_match_all('/\d+/', $response, $numbers);
            $indexes = array_map('intval', $numbers[0] ?? []);
        }

        $indexes = array_values(array_unique(array_filter($indexes, static fn (int $index): bool => $index > 0)));
        sort($indexes, SORT_NUMERIC);

        return $indexes;
    }

    private function fetchMessage(InboundMailboxDsn $dsn, int $index): string
    {
        $rawEmail = $this->perform($dsn, $dsn->getMailboxUrl().'/;MAILINDEX='.$index);
        if ('' === trim($rawEmail)) {
            throw new RuntimeException(\sprintf('Inbound mailbox message %d is empty.', $index));
        }

        return $rawEmail;
    }

    private function markUnseen(InboundMailboxDsn $dsn, int $index): void
    {
        $this->perform(
            $dsn,
            $dsn->getMailboxUrl(),
            \sprintf('STORE %d -FLAGS.SILENT (\Seen)', $index)
        );
    }

    private function perform(InboundMailboxDsn $dsn, string $url, ?string $customRequest = null): string
    {
        $handle = curl_init();
        if (false === $handle) {
            throw new RuntimeException('Could not initialize the cURL client for inbound mail.');
        }

        $protocol = 'imaps' === $dsn->getScheme() ? CURLPROTO_IMAPS : CURLPROTO_IMAP;
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERNAME => $dsn->getUsername(),
            CURLOPT_PASSWORD => $dsn->getPassword(),
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => $protocol,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        if (null !== $customRequest) {
            $options[CURLOPT_CUSTOMREQUEST] = $customRequest;
        }

        try {
            if (!curl_setopt_array($handle, $options)) {
                throw new RuntimeException('Could not configure the cURL client for inbound mail.');
            }

            $response = curl_exec($handle);
            if (false === $response) {
                $error = curl_error($handle);

                throw new RuntimeException(\sprintf('Inbound mailbox request failed for %s: %s', $dsn->getSafeDescription(), '' !== $error ? $error : 'unknown cURL error'));
            }

            return (string) $response;
        } finally {
            curl_close($handle);
        }
    }
}
