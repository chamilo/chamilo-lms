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
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

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
        private RequestStack $requestStack,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Room
    {
        \assert($data instanceof Room);

        // Put/Patch denormalize onto a fresh, unmanaged Room instead of the
        // fetched one (object_to_populate isn't taking effect for this
        // resource -- same issue as BranchStateProcessor), so anything
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
                    ->setCoordinates($data->getCoordinates())
                    ->setIp($data->getIp())
                    ->setIpMask($data->getIpMask())
                    ->setBranch($data->getBranch())
                ;
                // Preserve polygon when legacy/other clients omit roomMap.
                // An explicit JSON null is allowed to clear it.
                if ($this->roomMapWasSubmitted()) {
                    $existing->setRoomMap($data->getRoomMap());
                }
                $data = $existing;
            }
        }

        $this->roomAccessUrlHelper->assertBranchAllowed($data->getBranch());

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
    private function roomMapWasSubmitted(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        try {
            return \array_key_exists('roomMap', $request->toArray());
        } catch (Throwable) {
            return false;
        }
    }
}
