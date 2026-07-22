<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CoreBundle\Helpers\RoomAccessUrlHelper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @implements ProcessorInterface<Room, Room>
 */
final readonly class RoomStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private RoomAccessUrlHelper $roomAccessUrlHelper,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Room
    {
        \assert($data instanceof Room);

        $this->roomAccessUrlHelper->assertBranchAllowed($data->getBranch());

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
