<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Toolbox;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\ApiResource\Toolbox\ToolboxCollection;
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

/** @implements ProviderInterface<ToolboxCollection> */
final readonly class ToolboxCollectionProvider implements ProviderInterface
{
    use ToolboxAccessHelperTrait;

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private CToolboxRepository $toolboxRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private StudentViewHelper $studentViewHelper,
        private AiProviderFactory $aiProviderFactory,
        private ToolboxApplicationService $applicationService,
        private ToolboxResponseBuilder $responseBuilder,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ToolboxCollection
    {
        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $group = $this->cidReqHelper->getDoctrineGroupEntity();
        $this->assertToolboxSessionBelongsToCourse($session, $course);
        $this->assertToolboxGroupBelongsToContext($group, $course, $session);

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        $result = new ToolboxCollection();
        $result->enabled = $this->isToolboxEnabled($this->settingsManager);
        $result->canManage = $this->canManageToolbox($this->security, $this->studentViewHelper);
        $result->canCreate = $result->enabled
            && $result->canManage
            && $this->canWriteToolboxContext($this->security, $this->studentViewHelper, $session);

        if ($result->canManage && $result->enabled) {
            $result->providers = $this->getTextProviderOptions();
        }

        if (!$result->enabled) {
            return $result;
        }

        $items = [];
        foreach ($this->toolboxRepository->findAllInContext($course, $session, $group) as $item) {
            if (!$item instanceof CToolbox || null === $item->getIid()) {
                continue;
            }

            $node = $item->getResourceNode();
            if (!$result->canManage) {
                if (!$this->applicationService->isPublished($item, $course, $session, $group)) {
                    continue;
                }
                if (null === $node || !$this->security->isGranted('VIEW', $node)) {
                    continue;
                }
            }

            $itemCanManage = $result->canManage
                && null !== $node
                && $this->security->isGranted('EDIT', $node);

            $items[] = $this->responseBuilder->buildSummary(
                $item,
                $course,
                $session,
                $group,
                $user,
                $itemCanManage,
            );
        }

        usort(
            $items,
            static fn (array $left, array $right): int => ((int) $right['id']) <=> ((int) $left['id']),
        );

        $result->items = $items;
        $result->totalItems = \count($items);

        return $result;
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function getTextProviderOptions(): array
    {
        $configured = $this->settingsManager->getSetting('ai_helpers.ai_providers', true);
        if (\is_string($configured)) {
            $decoded = json_decode($configured, true);
            $configured = \is_array($decoded) ? $decoded : [];
        }
        if (!\is_array($configured)) {
            $configured = [];
        }

        $options = [];
        foreach ($this->aiProviderFactory->getProvidersForType('text') as $providerName) {
            $providerName = trim((string) $providerName);
            if ('' === $providerName) {
                continue;
            }

            $providerConfig = $configured[$providerName] ?? [];
            $model = '';
            if (\is_array($providerConfig)) {
                $textConfig = $providerConfig['text'] ?? [];
                $model = \is_array($textConfig)
                    ? trim((string) ($textConfig['model'] ?? ''))
                    : trim((string) ($providerConfig['model'] ?? ''));
            }

            $options[] = [
                'label' => '' !== $model ? $providerName.' ('.$model.')' : $providerName,
                'value' => $providerName,
            ];
        }

        return $options;
    }
}
