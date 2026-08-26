<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Service\Gradebook\GradebookLinkManager;
use Chamilo\CoreBundle\State\Gradebook\GradebookLinkResourceResolver;
use Chamilo\CourseBundle\Entity\CAttendance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

/**
 * @implements ProcessorInterface<CAttendance, mixed>
 */
final readonly class CAttendanceDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private ProcessorInterface $removeProcessor,
        private EntityManagerInterface $entityManager,
        private GradebookLinkManager $gradebookLinkManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof CAttendance) {
            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        $attendanceId = (int) ($data->getIid() ?? 0);
        $resourceLink = $data->getFirstResourceLink();
        $course = $resourceLink->getCourse();

        $this->entityManager->beginTransaction();

        try {
            if ($attendanceId > 0) {
                $this->gradebookLinkManager->removeAllCourseLinks(
                    $course,
                    GradebookLinkResourceResolver::LINK_ATTENDANCE,
                    $attendanceId,
                );
            }

            $result = $this->removeProcessor->process($data, $operation, $uriVariables, $context);
            $this->entityManager->flush();
            $this->entityManager->commit();

            return $result;
        } catch (Throwable $exception) {
            $this->entityManager->rollback();

            throw $exception;
        }
    }
}
