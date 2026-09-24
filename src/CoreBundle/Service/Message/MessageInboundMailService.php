<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Message;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use ValueError;

use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const FILTER_VALIDATE_EMAIL;

final class MessageInboundMailService
{
    public const string REPLY_MARKER = 'Reply above this line';

    private const int TOKEN_BYTES = 32;
    private const int MAX_RAW_BYTES = 5_000_000;
    private const int MAX_REPLY_CHARS = 100_000;

    private ?int $preparedMessageId = null;
    private ?int $preparedReceiverId = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SettingsManager $settingsManager,
    ) {}

    public function isEnabled(): bool
    {
        if ('true' !== (string) $this->settingsManager->getSetting('mail.enable_inbound_mail')) {
            return false;
        }

        return false !== filter_var($this->getInboundAddress(), FILTER_VALIDATE_EMAIL);
    }

    /**
     * Prepares the notification body marker and returns headers for Notification::saveNotification().
     *
     * @return array<string, array<string, string>>
     */
    public function prepareForMessageRecipient(int $messageId, int $receiverId): array
    {
        $this->clearPreparedReply();

        if (!$this->isEnabled() || $messageId <= 0 || $receiverId <= 0) {
            return [];
        }

        $relation = $this->findReplyableRelation($messageId, $receiverId);
        if (!$relation instanceof MessageRelUser) {
            return [];
        }

        $message = $relation->getMessage();
        if (Message::MESSAGE_TYPE_INBOX !== $message->getMsgType()) {
            return [];
        }

        $token = $relation->getMailReplyToken();
        if (null === $token || '' === $token) {
            $token = $this->createUniqueToken();
            $relation->setMailReplyToken($token);
        }

        if (!$message->isMailAnswer()) {
            $message->setMailAnswer(true);
        }

        $this->entityManager->persist($message);
        $this->entityManager->persist($relation);
        $this->entityManager->flush();

        $this->preparedMessageId = $messageId;
        $this->preparedReceiverId = $receiverId;

        return [
            'reply_to' => [
                'name' => $this->getReplyDisplayName(),
                'mail' => $this->buildReplyAddress($token),
            ],
        ];
    }

    public function clearPreparedReply(): void
    {
        $this->preparedMessageId = null;
        $this->preparedReceiverId = null;
    }

    public function prependPreparedReplyMarker(string $html): string
    {
        if (null === $this->preparedMessageId || null === $this->preparedReceiverId) {
            return $html;
        }

        return '<div data-chamilo-mail-reply-marker="1"><strong>'.self::REPLY_MARKER.'</strong></div>'
            .'<hr>'
            .$html;
    }

    public function processRawEmail(string $rawEmail, ?string $envelopeRecipient = null): Message
    {
        if (!$this->isEnabled()) {
            throw new RuntimeException('Inbound e-mail is disabled or the inbound address is invalid.');
        }

        if ('' === trim($rawEmail)) {
            throw new RuntimeException('Inbound e-mail is empty.');
        }

        if (\strlen($rawEmail) > self::MAX_RAW_BYTES) {
            throw new RuntimeException('Inbound e-mail exceeds the supported size limit.');
        }

        [$headers, $body] = $this->splitMessage($rawEmail);
        $token = $this->extractReplyToken($headers, $envelopeRecipient);

        if (null === $token) {
            throw new RuntimeException('Inbound e-mail does not contain a valid Chamilo reply token.');
        }

        $relation = $this->entityManager
            ->getRepository(MessageRelUser::class)
            ->findOneBy(['mailReplyToken' => $token])
        ;

        if (!$relation instanceof MessageRelUser) {
            throw new RuntimeException('Inbound e-mail reply token was not found.');
        }

        if (!\in_array($relation->getReceiverType(), [MessageRelUser::TYPE_TO, MessageRelUser::TYPE_CC], true)) {
            throw new RuntimeException('Inbound e-mail reply token is not associated with a recipient.');
        }

        $originalMessage = $relation->getMessage();
        if (!$originalMessage->isMailAnswer() || Message::MESSAGE_TYPE_INBOX !== $originalMessage->getMsgType()) {
            throw new RuntimeException('The referenced message does not allow e-mail replies.');
        }

        $fromEmail = $this->extractAddress($headers['from'] ?? '');
        $replyAuthor = $relation->getReceiver();
        $expectedEmail = trim((string) $replyAuthor->getEmail());

        if (
            null === $fromEmail
            || '' === $expectedEmail
            || !hash_equals(strtolower($expectedEmail), strtolower($fromEmail))
        ) {
            throw new RuntimeException('Inbound e-mail sender does not match the message recipient.');
        }

        if (!$replyAuthor->isActive()) {
            throw new RuntimeException('Inbound e-mail sender is not an active user.');
        }

        $originalSender = $originalMessage->getSender();
        if (null === $originalSender || !$originalSender->isActive()) {
            throw new RuntimeException('The original message sender is not available.');
        }

        $inboundId = $this->buildInboundId($headers, $rawEmail);
        $existing = $this->entityManager
            ->getRepository(Message::class)
            ->findOneBy(['mailInboundId' => $inboundId])
        ;

        if ($existing instanceof Message) {
            return $existing;
        }

        $plainReply = $this->extractReadableBody($headers, $body);
        $plainReply = $this->stripQuotedHistory($plainReply);
        $plainReply = trim(str_replace("\0", '', $plainReply));

        if ('' === $plainReply) {
            throw new RuntimeException('Inbound e-mail does not contain a reply body.');
        }

        if (mb_strlen($plainReply) > self::MAX_REPLY_CHARS) {
            throw new RuntimeException('Inbound e-mail reply body exceeds the supported size limit.');
        }

        $safeContent = nl2br(
            htmlspecialchars($plainReply, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            false
        );

        $reply = (new Message())
            ->setSender($replyAuthor)
            ->addReceiverTo($originalSender)
            ->setTitle($this->buildReplyTitle($originalMessage->getTitle()))
            ->setContent($safeContent)
            ->setParent($originalMessage)
            ->setMsgType(Message::MESSAGE_TYPE_INBOX)
            ->setMailInboundId($inboundId)
        ;

        $senderRelation = (new MessageRelUser())
            ->setMessage($reply)
            ->setReceiver($replyAuthor)
            ->setReceiverType(MessageRelUser::TYPE_SENDER)
        ;
        $reply->addReceiver($senderRelation);

        $this->entityManager->persist($reply);
        $this->entityManager->flush();

        return $reply;
    }

    private function getInboundAddress(): string
    {
        return trim((string) $this->settingsManager->getSetting('mail.inbound_mail_address'));
    }

    private function getReplyDisplayName(): string
    {
        $name = trim((string) $this->settingsManager->getSetting('mail.mailer_from_name'));

        return '' !== $name ? $name : 'Chamilo';
    }

    private function buildReplyAddress(string $token): string
    {
        $address = $this->getInboundAddress();
        $position = strrpos($address, '@');
        if (false === $position) {
            throw new RuntimeException('Inbound e-mail address is invalid.');
        }

        $local = substr($address, 0, $position);
        $domain = substr($address, $position + 1);

        return $local.'+'.$token.'@'.$domain;
    }

    private function findReplyableRelation(int $messageId, int $receiverId): ?MessageRelUser
    {
        $relation = $this->entityManager
            ->getRepository(MessageRelUser::class)
            ->createQueryBuilder('relation')
            ->innerJoin('relation.message', 'message')
            ->innerJoin('relation.receiver', 'receiver')
            ->andWhere('message.id = :messageId')
            ->andWhere('receiver.id = :receiverId')
            ->andWhere('relation.receiverType IN (:receiverTypes)')
            ->setParameter('messageId', $messageId)
            ->setParameter('receiverId', $receiverId)
            ->setParameter('receiverTypes', [MessageRelUser::TYPE_TO, MessageRelUser::TYPE_CC])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $relation instanceof MessageRelUser ? $relation : null;
    }

    private function createUniqueToken(): string
    {
        $repository = $this->entityManager->getRepository(MessageRelUser::class);

        do {
            $token = bin2hex(random_bytes(self::TOKEN_BYTES));
        } while (null !== $repository->findOneBy(['mailReplyToken' => $token]));

        return $token;
    }

    /**
     * @return array{0: array<string, string>, 1: string}
     */
    private function splitMessage(string $rawEmail): array
    {
        $parts = preg_split("/\r?\n\r?\n/", $rawEmail, 2);
        if (!\is_array($parts) || 2 !== \count($parts)) {
            throw new RuntimeException('Inbound e-mail has no RFC822 header/body separator.');
        }

        return [$this->parseHeaders($parts[0]), $parts[1]];
    }

    /**
     * @return array<string, string>
     */
    private function parseHeaders(string $rawHeaders): array
    {
        $unfolded = preg_replace("/\r?\n[ \t]+/", ' ', $rawHeaders) ?? $rawHeaders;
        $headers = [];

        foreach (preg_split("/\r?\n/", $unfolded) ?: [] as $line) {
            $position = strpos($line, ':');
            if (false === $position) {
                continue;
            }

            $name = strtolower(trim(substr($line, 0, $position)));
            $value = trim(substr($line, $position + 1));
            if ('' === $name) {
                continue;
            }

            if (isset($headers[$name])) {
                $headers[$name] .= ', '.$value;
            } else {
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    /**
     * @param array<string, string> $headers
     */
    private function extractReplyToken(array $headers, ?string $envelopeRecipient): ?string
    {
        $candidates = [];
        if (null !== $envelopeRecipient && '' !== trim($envelopeRecipient)) {
            $candidates[] = $envelopeRecipient;
        }

        foreach (['to', 'delivered-to', 'x-original-to', 'envelope-to'] as $header) {
            if (isset($headers[$header])) {
                $candidates[] = $headers[$header];
            }
        }

        foreach ($candidates as $candidate) {
            if (1 === preg_match('/\+([a-f0-9]{64})@/i', $candidate, $matches)) {
                return strtolower($matches[1]);
            }
        }

        return null;
    }

    private function extractAddress(string $header): ?string
    {
        $decoded = $this->decodeHeader($header);

        if (1 === preg_match('/<\s*([^<>\s]+@[^<>\s]+)\s*>/', $decoded, $matches)) {
            $email = trim($matches[1]);

            return false !== filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
        }

        if (1 === preg_match('/\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b/i', $decoded, $matches)) {
            $email = trim($matches[0]);

            return false !== filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
        }

        return null;
    }

    private function decodeHeader(string $value): string
    {
        $decoded = iconv_mime_decode($value, 0, 'UTF-8');

        return false === $decoded ? $value : $decoded;
    }

    /**
     * @param array<string, string> $headers
     */
    private function buildInboundId(array $headers, string $rawEmail): string
    {
        $messageId = trim($headers['message-id'] ?? '');
        $source = '' !== $messageId ? strtolower($messageId) : $rawEmail;

        return hash('sha256', $source);
    }

    /**
     * @param array<string, string> $headers
     */
    private function extractReadableBody(array $headers, string $body): string
    {
        [$kind, $content] = $this->extractMimePart($headers, $body);

        if ('text/html' === $kind) {
            $content = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $content) ?? $content;
            $content = preg_replace('/<\s*\/(p|div|li|tr|h[1-6])\s*>/i', "\n", $content) ?? $content;
            $content = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return str_replace(["\r\n", "\r"], "\n", $content);
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array{0: string, 1: string}
     */
    private function extractMimePart(array $headers, string $body): array
    {
        $contentTypeHeader = $headers['content-type'] ?? 'text/plain; charset=UTF-8';
        $contentType = strtolower(trim(strtok($contentTypeHeader, ';') ?: 'text/plain'));

        if (str_starts_with($contentType, 'multipart/')) {
            $boundary = $this->extractParameter($contentTypeHeader, 'boundary');
            if (null === $boundary || '' === $boundary) {
                throw new RuntimeException('Inbound multipart e-mail has no boundary.');
            }

            $plainCandidate = null;
            $htmlCandidate = null;
            $segments = explode('--'.$boundary, $body);

            foreach ($segments as $segment) {
                $segment = ltrim($segment, "\r\n");
                $segment = rtrim($segment, "\r\n");
                if ('' === $segment || '--' === $segment || str_starts_with($segment, '--')) {
                    continue;
                }

                try {
                    [$partHeaders, $partBody] = $this->splitMessage($segment);
                    [$partKind, $partContent] = $this->extractMimePart($partHeaders, $partBody);
                } catch (RuntimeException) {
                    continue;
                }

                if ('text/plain' === $partKind && null === $plainCandidate) {
                    $plainCandidate = $partContent;
                } elseif ('text/html' === $partKind && null === $htmlCandidate) {
                    $htmlCandidate = $partContent;
                }
            }

            if (null !== $plainCandidate) {
                return ['text/plain', $plainCandidate];
            }
            if (null !== $htmlCandidate) {
                return ['text/html', $htmlCandidate];
            }

            throw new RuntimeException('Inbound multipart e-mail contains no readable text part.');
        }

        if (!\in_array($contentType, ['text/plain', 'text/html'], true)) {
            throw new RuntimeException('Inbound e-mail contains no supported text body.');
        }

        $transferEncoding = strtolower(trim($headers['content-transfer-encoding'] ?? ''));
        if ('base64' === $transferEncoding) {
            $decoded = base64_decode($body, true);
            if (false === $decoded) {
                throw new RuntimeException('Inbound e-mail contains invalid base64 content.');
            }
            $body = $decoded;
        } elseif ('quoted-printable' === $transferEncoding) {
            $body = quoted_printable_decode($body);
        }

        $charset = $this->extractParameter($contentTypeHeader, 'charset');
        if (null !== $charset && '' !== $charset && 0 !== strcasecmp($charset, 'UTF-8')) {
            try {
                $body = mb_convert_encoding($body, 'UTF-8', $charset);
            } catch (ValueError) {
                throw new RuntimeException('Inbound e-mail uses an unsupported charset.');
            }
        }

        return [$contentType, $body];
    }

    private function extractParameter(string $header, string $name): ?string
    {
        $pattern = '/(?:^|;)\s*'.preg_quote($name, '/').'\s*=\s*(?:"([^"]*)"|([^;\s]*))/i';
        if (1 !== preg_match($pattern, $header, $matches)) {
            return null;
        }

        return trim((string) ($matches[1] ?? $matches[2] ?? ''));
    }

    private function stripQuotedHistory(string $body): string
    {
        $position = stripos($body, self::REPLY_MARKER);
        if (false !== $position) {
            $body = substr($body, 0, $position);
        }

        return trim($body);
    }

    private function buildReplyTitle(string $title): string
    {
        return 1 === preg_match('/^\s*re\s*:/i', $title) ? $title : 'Re: '.$title;
    }
}
