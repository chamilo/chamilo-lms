<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Helpers\RoomAccessUrlHelper;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @implements ProcessorInterface<CAttendance|CAttendanceCalendar|SessionRelCourse, CAttendance|CAttendanceCalendar|SessionRelCourse>
 */
final readonly class RoomAssignmentStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private RoomAccessUrlHelper $roomAccessUrlHelper,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        \assert(
            $data instanceof CAttendance
            || $data instanceof CAttendanceCalendar
            || $data instanceof SessionRelCourse
        );

        $this->roomAccessUrlHelper->assertRoomAllowed($data->getRoom());

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
