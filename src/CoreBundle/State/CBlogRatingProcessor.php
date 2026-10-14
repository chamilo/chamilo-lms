<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CourseBundle\Entity\CBlogRating;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Binds a new blog rating to the authenticated user, whatever the client sent.
 *
 * @implements ProcessorInterface<CBlogRating, CBlogRating>
 */
final readonly class CBlogRatingProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private UserHelper $userHelper,
        private EntityManagerInterface $entityManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($operation instanceof Post && $data instanceof CBlogRating) {
            $user = $this->userHelper->getCurrent();
            if (!$user instanceof User) {
                throw new AccessDeniedHttpException('Authentication is required.');
            }

            // A managed reference, so Doctrine does not treat the detached token user as new.
            $data->setUser($this->entityManager->getReference(User::class, $user->getId()));
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
