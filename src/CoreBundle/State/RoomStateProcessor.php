<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CoreBundle\Helpers\RoomAccessUrlHelper;
use Doctrine\ORM\EntityManagerInterface;
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
        private EntityManagerInterface $em,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Room
    {
        \assert($data instanceof Room);

        // Put/Patch denormalize onto a fresh, unmanaged Room instead of the
        // fetched one (object_to_populate isn't taking effect for this
        // resource -- same issue as BranchSyncStateProcessor), so anything
        // the request didn't submit would otherwise be wiped (e.g. "branch"
        // reset to null, failing its NotNull constraint). Re-attach the
        // submitted room:write values onto the real, managed entity first.
        if (isset($uriVariables['id'])) {
            $existing = $this->em->getRepository(Room::class)->find($uriVariables['id']);

            if ($existing instanceof Room && $existing !== $data) {
                $existing
                    ->setTitle($data->getTitle())
                    ->setDescription($data->getDescription())
                    ->setFloorNumber($data->getFloorNumber())
                    ->setCapacity($data->getCapacity())
                    ->setGeolocation($data->getGeolocation())
                    ->setIp($data->getIp())
                    ->setIpMask($data->getIpMask())
                    ->setBranch($data->getBranch())
                ;
                $data = $existing;
            }
        }

        $this->roomAccessUrlHelper->assertBranchAllowed($data->getBranch());

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
