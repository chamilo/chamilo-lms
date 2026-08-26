<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Mobile;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\State\Mobile\MobileMessageDeleteProcessor;
use Chamilo\CoreBundle\State\Mobile\MobileMessageProvider;
use Chamilo\CoreBundle\State\Mobile\MobileMessageReadProcessor;
use Chamilo\CoreBundle\State\Mobile\MobileMessageSendProcessor;
use Chamilo\CoreBundle\State\Mobile\MobileMessageStarProcessor;
use Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;

use const DATE_ATOM;

#[ApiResource(
    shortName: 'MobileMessage',
    operations: [
        new GetCollection(
            uriTemplate: '/mobile_messages',
            paginationEnabled: false,
            security: "is_granted('ROLE_USER')",
            name: 'get_mobile_messages',
            provider: MobileMessageProvider::class,
        ),
        new Get(
            uriTemplate: '/mobile_messages/{id}',
            requirements: ['id' => '\d+'],
            security: "is_granted('ROLE_USER')",
            name: 'get_mobile_message',
            provider: MobileMessageProvider::class,
        ),
        new Post(
            uriTemplate: '/mobile_messages/{id}/read',
            requirements: ['id' => '\d+'],
            status: Response::HTTP_OK,
            security: "is_granted('ROLE_USER')",
            input: false,
            read: false,
            name: 'mark_mobile_message_read',
            processor: MobileMessageReadProcessor::class,
        ),
        new Post(
            uriTemplate: '/mobile_messages/{id}/star',
            requirements: ['id' => '\d+'],
            status: Response::HTTP_OK,
            security: "is_granted('ROLE_USER')",
            input: MobileMessageStarInput::class,
            read: false,
            name: 'star_mobile_message',
            processor: MobileMessageStarProcessor::class,
        ),
        new Post(
            uriTemplate: '/mobile_messages',
            status: Response::HTTP_CREATED,
            security: "is_granted('ROLE_USER')",
            input: MobileMessageWriteInput::class,
            read: false,
            name: 'send_mobile_message',
            processor: MobileMessageSendProcessor::class,
        ),
        new Delete(
            uriTemplate: '/mobile_messages/{id}',
            requirements: ['id' => '\d+'],
            status: Response::HTTP_NO_CONTENT,
            security: "is_granted('ROLE_USER')",
            input: false,
            read: false,
            output: false,
            name: 'delete_mobile_message',
            processor: MobileMessageDeleteProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['mobile_message:read']],
)]
final class MobileMessage
{
    #[ApiProperty(identifier: true)]
    #[Groups(['mobile_message:read'])]
    public int $id;

    #[Groups(['mobile_message:read'])]
    public string $box;

    #[Groups(['mobile_message:read'])]
    public string $title;

    #[Groups(['mobile_message:read'])]
    public string $preview;

    #[Groups(['mobile_message:read'])]
    public ?string $content = null;

    #[Groups(['mobile_message:read'])]
    public string $sendDate;

    #[Groups(['mobile_message:read'])]
    public bool $read;

    #[Groups(['mobile_message:read'])]
    public bool $starred;

    #[Groups(['mobile_message:read'])]
    public int $attachmentCount;

    #[Groups(['mobile_message:read'])]
    public int $senderId;

    #[Groups(['mobile_message:read'])]
    public string $senderUsername;

    #[Groups(['mobile_message:read'])]
    public string $senderName;

    /**
     * @var int[]
     */
    #[Groups(['mobile_message:read'])]
    public array $recipientIds = [];

    /**
     * @var string[]
     */
    #[Groups(['mobile_message:read'])]
    public array $recipientNames = [];

    #[Groups(['mobile_message:read'])]
    public ?int $parentId = null;

    public static function createPlainTextPreview(string $content): string
    {
        $spacedContent = preg_replace(
            '/<\/?(?:p|div|li|h[1-6]|section|article)\b[^>]*>|<br\b[^>]*>/iu',
            ' ',
            $content
        ) ?? $content;

        return trim(preg_replace('/\s+/u', ' ', strip_tags($spacedContent)) ?? '');
    }

    public static function fromMessage(
        Message $message,
        User $user,
        bool $includeContent,
        ?string $box = null,
    ): ?self {
        $relation = self::findUserRelation($message, $user, $box);
        $sender = $message->getSender();

        if (!$relation instanceof MessageRelUser || !$sender instanceof User || null === $message->getId()) {
            return null;
        }

        $resolvedBox = MessageRelUser::TYPE_SENDER === $relation->getReceiverType() ? 'sent' : 'inbox';
        $content = Security::remove_XSS($message->getContent(), STUDENT);
        $plainContent = self::createPlainTextPreview($content);

        $recipientIds = [];
        $recipientNames = [];

        foreach ($message->getReceivers() as $recipientRelation) {
            if (
                MessageRelUser::TYPE_SENDER === $recipientRelation->getReceiverType()
            ) {
                continue;
            }

            $recipient = $recipientRelation->getReceiver();
            $recipientIds[] = (int) $recipient->getId();
            $recipientNames[] = $recipient->getFullName() ?: $recipient->getUsername();
        }

        $resource = new self();
        $resource->id = $message->getId();
        $resource->box = $resolvedBox;
        $resource->title = $message->getTitle();
        $resource->preview = mb_strimwidth($plainContent, 0, 160, '…');
        $resource->content = $includeContent ? $content : null;
        $resource->sendDate = $message->getSendDate()->format(DATE_ATOM);
        $resource->read = 'sent' === $resolvedBox || $relation->isRead();
        $resource->starred = $relation->isStarred();
        $resource->attachmentCount = $message->getAttachments()->count();
        $resource->senderId = (int) $sender->getId();
        $resource->senderUsername = $sender->getUsername();
        $resource->senderName = $sender->getFullName() ?: $sender->getUsername();
        $resource->recipientIds = $recipientIds;
        $resource->recipientNames = $recipientNames;
        $resource->parentId = $message->getParent()?->getId();

        return $resource;
    }

    public static function findUserRelation(
        Message $message,
        User $user,
        ?string $box = null,
    ): ?MessageRelUser {
        foreach ($message->getReceivers() as $relation) {
            if ($relation->getReceiver()->getId() !== $user->getId() || $relation->isDeleted()) {
                continue;
            }

            $relationBox = MessageRelUser::TYPE_SENDER === $relation->getReceiverType()
                ? 'sent'
                : 'inbox';

            if (null !== $box && $relationBox !== $box) {
                continue;
            }

            return $relation;
        }

        return null;
    }
}
