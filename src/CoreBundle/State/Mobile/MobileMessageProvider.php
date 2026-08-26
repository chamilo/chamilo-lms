<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Mobile;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Mobile\MobileMessage;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOL;

/**
 * @implements ProviderInterface<MobileMessage>
 */
final readonly class MobileMessageProvider implements ProviderInterface
{
    private const int COLLECTION_LIMIT = 50;

    public function __construct(
        private MessageRepository $messageRepository,
        private UserHelper $userHelper,
        private RequestStack $requestStack,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return iterable<MobileMessage>|MobileMessage|null
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): array|MobileMessage|null {
        $user = $this->userHelper->getCurrent();

        if (null === $user) {
            throw new AccessDeniedHttpException('An authenticated user is required.');
        }

        if ($operation instanceof CollectionOperationInterface) {
            $resources = [];
            $request = $this->requestStack->getMainRequest();
            $box = 'sent' === $request?->query->getString('box') ? 'sent' : 'inbox';
            $search = $request?->query->getString('search') ?: null;
            $unread = self::optionalBoolean($request?->query->get('unread'));
            $starred = self::optionalBoolean($request?->query->get('starred'));

            foreach (
                $this->messageRepository->findMobileMessagesForUser(
                    $user,
                    $box,
                    $search,
                    $unread,
                    $starred,
                    self::COLLECTION_LIMIT
                ) as $message
            ) {
                $resource = MobileMessage::fromMessage($message, $user, false, $box);

                if ($resource instanceof MobileMessage) {
                    $resources[] = $resource;
                }
            }

            return $resources;
        }

        $messageId = (int) ($uriVariables['id'] ?? 0);
        $message = $this->messageRepository->findMobileMessageForUser($messageId, $user);

        if (!$message instanceof Message) {
            return null;
        }

        return MobileMessage::fromMessage($message, $user, true);
    }

    private static function optionalBoolean(mixed $value): ?bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (!\is_string($value) || '' === $value) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    }
}
