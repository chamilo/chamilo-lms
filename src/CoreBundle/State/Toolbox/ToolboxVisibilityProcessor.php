<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Toolbox;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Toolbox\ToolboxVisibilityInput;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Service\Toolbox\ToolboxApplicationService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CToolbox;
use Chamilo\CourseBundle\Repository\CToolboxRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<ToolboxVisibilityInput, null> */
final readonly class ToolboxVisibilityProcessor implements ProcessorInterface
{
    use ToolboxAccessHelperTrait;

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private CToolboxRepository $toolboxRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private StudentViewHelper $studentViewHelper,
        private ToolboxApplicationService $applicationService,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        if (!$data instanceof ToolboxVisibilityInput) {
            throw new BadRequestHttpException('The Toolbox visibility payload is invalid.');
        }

        $this->assertToolboxTeacher($this->security);
        if (!$this->isToolboxEnabled($this->settingsManager)) {
            throw new AccessDeniedHttpException('Toolbox is disabled.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $group = $this->cidReqHelper->getDoctrineGroupEntity();
        $this->assertToolboxSessionBelongsToCourse($session, $course);
        $this->assertToolboxGroupBelongsToContext($group, $course, $session);
        if (!$this->canWriteToolboxContext($this->security, $this->studentViewHelper, $session)) {
            throw new AccessDeniedHttpException('Toolbox is read-only in this context.');
        }

        $item = $this->toolboxRepository->findOneInContext((int) ($uriVariables['id'] ?? 0), $course, $session, $group);
        if (!$item instanceof CToolbox) {
            throw new NotFoundHttpException('Toolbox item not found.');
        }
        $node = $item->getResourceNode();
        if (null === $node || !$this->security->isGranted('EDIT', $node)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this Toolbox item.');
        }

        $this->applicationService->setPublished($item, $course, $session, $group, $data->visible);

        return null;
    }
}
