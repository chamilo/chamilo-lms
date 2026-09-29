<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseProgress;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseProgress\CourseProgressThematicAdvance;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\CourseProgressHelper;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CThematicAdvance;
use Chamilo\CourseBundle\Repository\CThematicAdvanceRepository;
use Chamilo\CourseBundle\Repository\CThematicRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<CourseProgressThematicAdvance, void>
 */
final readonly class CourseProgressThematicAdvanceDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private CThematicRepository $thematicRepository,
        private CThematicAdvanceRepository $thematicAdvanceRepository,
        private Security $security,
        private CourseProgressHelper $courseProgressHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $this->courseProgressHelper->assertToolEnabled($course);
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $this->courseProgressHelper->assertSessionBelongsToCourse($session, $course);
        $this->courseProgressHelper->assertCanManage($course, $session);

        $thematicId = isset($uriVariables['thematicId'])
            ? (int) $uriVariables['thematicId']
            : $request->query->getInt('thematicId');
        $thematic = $this->getEditableThematic($thematicId, $course, $session);
        $advanceId = isset($uriVariables['iid']) ? (int) $uriVariables['iid'] : 0;
        $advance = $this->getEditableAdvance($advanceId, $thematic);

        $this->thematicAdvanceRepository->delete($advance);
    }

    private function getEditableThematic(int $thematicId, Course $course, ?Session $session): CThematic
    {
        if ($thematicId <= 0) {
            throw new BadRequestHttpException('A valid thematic id is required.');
        }

        $thematic = $this->thematicRepository->find($thematicId);
        if (!$thematic instanceof CThematic) {
            throw new NotFoundHttpException('The requested thematic was not found.');
        }

        if (!$this->courseProgressHelper->thematicBelongsToExactContext($thematic, $course, $session)) {
            throw new AccessDeniedHttpException('The requested thematic does not belong to the current course context.');
        }

        $resourceNode = $thematic->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted('EDIT', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to edit thematic advances.');
        }

        return $thematic;
    }

    private function getEditableAdvance(int $advanceId, CThematic $thematic): CThematicAdvance
    {
        if ($advanceId <= 0) {
            throw new BadRequestHttpException('A valid thematic advance id is required.');
        }

        $advance = $this->thematicAdvanceRepository->find($advanceId);
        if (!$advance instanceof CThematicAdvance) {
            throw new NotFoundHttpException('The requested thematic advance was not found.');
        }

        if ($advance->getThematic()->getIid() !== $thematic->getIid()) {
            throw new AccessDeniedHttpException('The requested thematic advance does not belong to this thematic.');
        }

        return $advance;
    }
}
