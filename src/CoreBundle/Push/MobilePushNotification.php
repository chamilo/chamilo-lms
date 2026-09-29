<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Push;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

use const ENT_HTML5;
use const ENT_QUOTES;

#[Exclude]
final readonly class MobilePushNotification
{
    private const int TITLE_MAX_WIDTH = 100;
    private const int BODY_MAX_WIDTH = 180;

    public function __construct(
        public int $messageId,
        public string $title,
        public string $body,
    ) {}

    public static function fromMessage(Message $message): ?self
    {
        $messageId = $message->getId();

        if (null === $messageId) {
            return null;
        }

        $title = self::normalizePlainText($message->getTitle());
        if ('' === $title) {
            $title = 'Chamilo';
        }

        $sender = $message->getSender();
        $senderName = $sender instanceof User
            ? trim($sender->getFullName() ?: $sender->getUsername())
            : '';
        $preview = self::normalizePlainText($message->getContent());

        $bodyParts = array_values(array_filter([$senderName, $preview], static fn (string $part): bool => '' !== $part));
        $body = implode(' · ', $bodyParts);

        if ('' === $body) {
            $body = 'You have a new message.';
        }

        return new self(
            $messageId,
            mb_strimwidth($title, 0, self::TITLE_MAX_WIDTH, '…'),
            mb_strimwidth($body, 0, self::BODY_MAX_WIDTH, '…'),
        );
    }

    private static function normalizePlainText(string $content): string
    {
        $spacedContent = preg_replace(
            '/<\/?(?:p|div|li|h[1-6]|section|article|blockquote|tr|td|th)\b[^>]*>|<br\b[^>]*>/iu',
            ' ',
            $content
        ) ?? $content;
        $plainText = strip_tags($spacedContent);
        $plainText = html_entity_decode($plainText, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $plainText) ?? '');
    }
}
