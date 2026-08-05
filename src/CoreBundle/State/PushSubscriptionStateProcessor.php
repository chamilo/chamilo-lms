<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\PushSubscription;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Guards write operations on PushSubscription:
 *  - on create, forces the owning `user` to the authenticated user, preventing
 *    mass-assignment of the relation from the request body;
 *  - on delete, validates that the caller owns the subscription (admins bypass)
 *    before delegating to the remove processor.
 *
 * @implements ProcessorInterface<PushSubscription, PushSubscription|null>
 */
final readonly class PushSubscriptionStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private ProcessorInterface $removeProcessor,
        private UserHelper $userHelper,
        private Security $security,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?PushSubscription
    {
        $currentUser = $this->userHelper->getCurrent();
        if (null === $currentUser) {
            throw new AccessDeniedHttpException();
        }

        if ($operation instanceof DeleteOperationInterface) {
            if ($data instanceof PushSubscription
                && !$this->security->isGranted('ROLE_ADMIN')
                && $data->getUser()?->getId() !== $currentUser->getId()
            ) {
                throw new AccessDeniedHttpException();
            }

            $this->removeProcessor->process($data, $operation, $uriVariables, $context);

            return null;
        }

        if ($data instanceof PushSubscription) {
            $data->setUser($currentUser);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
