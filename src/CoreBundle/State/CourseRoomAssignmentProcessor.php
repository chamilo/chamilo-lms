<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Helpers\RoomAccessUrlHelper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** @implements ProcessorInterface<Course, Course> */
final readonly class CourseRoomAssignmentProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private RoomAccessUrlHelper $rooms,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        \assert($data instanceof Course);
        // Course PATCH already requires ROLE_ADMIN and only accepts course:write fields.
        // Apply the same access-URL validation used for attendance and calendar room writes.
        $this->rooms->assertRoomAllowed($data->getRoom());

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
