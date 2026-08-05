<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\BranchSync;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\RoomAccessUrlHelper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<BranchSync, BranchSync>
 */
final readonly class BranchSyncStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private AccessUrlHelper $accessUrlHelper,
        private RoomAccessUrlHelper $roomAccessUrlHelper,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): BranchSync
    {
        \assert($data instanceof BranchSync);

        if (null === $data->getId()) {
            $currentAccessUrl = $this->accessUrlHelper->getCurrent();
            if (null === $currentAccessUrl) {
                throw new BadRequestHttpException('The current access URL could not be resolved.');
            }

            $data->setUrl($currentAccessUrl);
        } else {
            $this->roomAccessUrlHelper->assertBranchAllowed($data);
        }

        if (null !== $data->getParent()) {
            $this->roomAccessUrlHelper->assertBranchAllowed($data->getParent());
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
