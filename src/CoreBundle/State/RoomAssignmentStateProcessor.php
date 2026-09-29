<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Helpers\RoomAccessUrlHelper;
use Chamilo\CoreBundle\Service\Gradebook\GradebookLinkManager;
use Chamilo\CoreBundle\State\Gradebook\GradebookLinkResourceResolver;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

/**
 * @implements ProcessorInterface<CAttendance|CAttendanceCalendar|SessionRelCourse, CAttendance|CAttendanceCalendar|SessionRelCourse>
 */
final readonly class RoomAssignmentStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private RoomAccessUrlHelper $roomAccessUrlHelper,
        private EntityManagerInterface $entityManager,
        private GradebookLinkManager $gradebookLinkManager,
        private RequestStack $requestStack,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        \assert(
            $data instanceof CAttendance
            || $data instanceof CAttendanceCalendar
            || $data instanceof SessionRelCourse
        );

        $this->roomAccessUrlHelper->assertRoomAllowed($data->getRoom());

        if (!$data instanceof CAttendance || !$this->hasGradebookPayload()) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $this->entityManager->beginTransaction();

        try {
            $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

            $resourceLink = $data->getFirstResourceLink();
            $course = $resourceLink->getCourse();
            $session = $resourceLink->getSession();
            $attendanceId = (int) ($data->getIid() ?? 0);

            if ($data->addToGradebook) {
                $this->gradebookLinkManager->upsertLink(
                    $course,
                    $session,
                    GradebookLinkResourceResolver::LINK_ATTENDANCE,
                    $attendanceId,
                    $data->gradebookCategoryId,
                    max(0.0, $data->getAttendanceWeight()),
                    true,
                    0.0,
                );
            } else {
                $this->gradebookLinkManager->removeLinks(
                    $course,
                    $session,
                    GradebookLinkResourceResolver::LINK_ATTENDANCE,
                    $attendanceId,
                );
                $data
                    ->setAttendanceQualifyTitle('')
                    ->setAttendanceWeight(0.0)
                ;
                $this->entityManager->persist($data);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();

            return $result;
        } catch (Throwable $exception) {
            $this->entityManager->rollback();

            throw $exception;
        }
    }

    private function hasGradebookPayload(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        try {
            $payload = $request->toArray();
        } catch (Throwable) {
            return false;
        }

        return \array_key_exists('addToGradebook', $payload)
            || \array_key_exists('gradebookCategoryId', $payload);
    }
}
