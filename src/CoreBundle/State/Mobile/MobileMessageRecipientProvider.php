<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Mobile;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Mobile\MobileMessageRecipient;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProviderInterface<MobileMessageRecipient>
 */
final readonly class MobileMessageRecipientProvider implements ProviderInterface
{
    private const int RESULT_LIMIT = 20;

    public function __construct(
        private UserRepository $userRepository,
        private UserHelper $userHelper,
        private RequestStack $requestStack,
    ) {}

    /**
     * @return MobileMessageRecipient[]
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): array {
        $user = $this->userHelper->getCurrent();

        if (null === $user || null === $user->getId()) {
            throw new AccessDeniedHttpException('An authenticated user is required.');
        }

        $query = trim($this->requestStack->getMainRequest()?->query->getString('q') ?? '');

        if (mb_strlen($query) < 2) {
            return [];
        }

        $resources = [];

        foreach (
            $this->userRepository->findUsersToSendMessage(
                (int) $user->getId(),
                $query,
                self::RESULT_LIMIT
            ) as $recipient
        ) {
            if (null === $recipient->getId() || $recipient->getId() === $user->getId()) {
                continue;
            }

            $resources[] = MobileMessageRecipient::fromUser($recipient);
        }

        return $resources;
    }
}
