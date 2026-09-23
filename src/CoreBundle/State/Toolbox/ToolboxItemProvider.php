<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Toolbox;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Toolbox\ToolboxItem;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Service\Toolbox\ToolboxApplicationService;
use Chamilo\CoreBundle\Service\Toolbox\ToolboxResponseBuilder;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CToolbox;
use Chamilo\CourseBundle\Repository\CToolboxRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<ToolboxItem> */
final readonly class ToolboxItemProvider implements ProviderInterface
{
    use ToolboxAccessHelperTrait;

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private CToolboxRepository $toolboxRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private StudentViewHelper $studentViewHelper,
        private ToolboxApplicationService $applicationService,
        private ToolboxResponseBuilder $responseBuilder,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ToolboxItem
    {
        if (!$this->isToolboxEnabled($this->settingsManager)) {
            throw new AccessDeniedHttpException('Toolbox is disabled.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $group = $this->cidReqHelper->getDoctrineGroupEntity();
        $this->assertToolboxSessionBelongsToCourse($session, $course);
        $this->assertToolboxGroupBelongsToContext($group, $course, $session);

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        $item = $this->toolboxRepository->findOneInContext((int) ($uriVariables['id'] ?? 0), $course, $session, $group);
        if (!$item instanceof CToolbox) {
            throw new NotFoundHttpException('Toolbox item not found.');
        }

        $canManage = $this->canManageToolbox($this->security, $this->studentViewHelper);
        $node = $item->getResourceNode();
        if (null === $node) {
            throw new AccessDeniedHttpException('The Toolbox resource is unavailable.');
        }

        if ($canManage) {
            if (!$this->security->isGranted('EDIT', $node)) {
                throw new AccessDeniedHttpException('You are not allowed to edit this Toolbox item.');
            }
        } elseif (!$this->applicationService->isPublished($item, $course, $session, $group)
            || !$this->security->isGranted('VIEW', $node)
        ) {
            throw new AccessDeniedHttpException('This Toolbox item is not available.');
        }

        return $this->responseBuilder->buildItem($item, $course, $session, $group, $user, $canManage);
    }
}
