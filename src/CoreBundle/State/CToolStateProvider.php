<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\DataTransformer\CourseToolDataTranformer;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Tool\AbstractPlugin;
use Chamilo\CoreBundle\Tool\AbstractTool;
use Chamilo\CoreBundle\Tool\LegacyPluginCourseTool;
use Chamilo\CoreBundle\Tool\ToolChain;
use Chamilo\CoreBundle\Traits\CourseFromRequestTrait;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\ORM\EntityManagerInterface;
use Event;
use Plugin;
use Positioning;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

/**
 * @template-implements ProviderInterface<CTool>
 */
final class CToolStateProvider implements ProviderInterface
{
    use CourseFromRequestTrait;

    private CourseToolDataTranformer $transformer;

    public function __construct(
        private readonly CollectionProvider $provider,
        protected EntityManagerInterface $entityManager,
        private readonly SettingsManager $settingsManager,
        private readonly Security $security,
        private readonly ToolChain $toolChain,
        protected RequestStack $requestStack,
        private readonly PluginHelper $pluginHelper,
    ) {
        $this->transformer = new CourseToolDataTranformer(
            $this->requestStack,
            $this->entityManager,
            $this->toolChain
        );
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        /** @var PartialPaginatorInterface $result */
        $result = $this->provider->provide($operation, $uriVariables, $context);

        $request = $this->requestStack->getMainRequest();
        $studentView = $request ? $request->getSession()->get('studentview') : 'studentview';

        /** @var User|null $user */
        $user = $this->security->getUser();

        $course = $this->getCourse();
        $session = $this->getSession();

        if (null === $course) {
            return [];
        }

        $isAllowToEdit = $user && ($user->isAdmin() || $user->hasRole('ROLE_CURRENT_COURSE_TEACHER'));
        $isAllowToEditBack = $isAllowToEdit;
        $isAllowToSessionEdit = $user && (
            $user->isAdmin()
                || $user->hasRole('ROLE_CURRENT_COURSE_TEACHER')
                || $user->hasRole('ROLE_CURRENT_COURSE_SESSION_TEACHER')
        );

        $allowVisibilityInSession = $this->settingsManager->getSetting('session.allow_edit_tool_visibility_in_session');

        [$restrictToPositioning, $allowedToolName] = $this->shouldRestrictToPositioningOnly(
            $user,
            $course->getId(),
            $session?->getId()
        );

        $results = [];

        /** @var CTool $cTool */
        foreach ($result as $cTool) {
            if ($this->shouldSkipUnavailableTeacherOnlyLegacyCourseTool($cTool)) {
                continue;
            }

            if ($this->shouldHideTeacherOnlyLegacyCourseTool($cTool, (bool) $isAllowToEdit, $studentView)) {
                continue;
            }

            $resolved = $this->resolveToolModelFromCTool($cTool);
            if (null === $resolved) {
                continue;
            }

            /** @var AbstractTool $toolModel */
            $toolModel = $resolved['model'];
            $resolvedName = $resolved['name'];
            $legacyPlugin = $resolved['plugin'] ?? null;

            if ($this->shouldHideLegacyPluginCourseTool($legacyPlugin, (bool) $isAllowToEdit, $studentView)) {
                continue;
            }

            if ($restrictToPositioning && $allowedToolName && $resolvedName !== $allowedToolName) {
                continue;
            }

            if (!$isAllowToEdit && 'admin' === $toolModel->getCategory()) {
                continue;
            }

            $resourceNode = $cTool->getResourceNode();
            if (!$resourceNode) {
                continue;
            }

            $resourceLinks = $resourceNode->getResourceLinks();

            if ($session && $allowVisibilityInSession) {
                $sessionLink = $resourceLinks->findFirst(
                    fn (int $key, ResourceLink $resourceLink): bool => $resourceLink->getSession()?->getId() === $session->getId()
                );

                if ($sessionLink) {
                    $resourceLinks->clear();
                    $resourceLinks->add($sessionLink);

                    $isAllowToEdit = $isAllowToSessionEdit;
                } else {
                    $isAllowToEdit = $isAllowToEditBack;
                }
            }

            if (!$isAllowToEdit || 'studentview' === $studentView) {
                $firstLink = $resourceLinks->first();
                if (!$firstLink) {
                    continue;
                }

                $isHiddenLearningPathAllowed = $this->isHiddenLearningPathAllowedInCourseHome($resolvedName);

                if (
                    ResourceLink::VISIBILITY_PUBLISHED !== $firstLink->getVisibility()
                    && !$isHiddenLearningPathAllowed
                ) {
                    continue;
                }
            }

            $results[] = $this->transformer->transform($cTool, $toolModel);
        }

        return $results;
    }

    /**
     * Resolve a tool model from ToolChain first, then fallback to a legacy plugin course tool.
     *
     * @return array{model: AbstractTool, name: string, plugin?: Plugin}|null
     */
    private function resolveToolModelFromCTool(CTool $cTool): ?array
    {
        $toolEntity = $cTool->getTool();
        $rawTitle = $toolEntity ? trim((string) $toolEntity->getTitle()) : '';
        $courseToolTitle = trim((string) $cTool->getTitle());

        foreach ($this->buildToolNameCandidates($rawTitle) as $candidate) {
            try {
                $model = $this->toolChain->getToolFromName($candidate);

                return [
                    'model' => $model,
                    'name' => $candidate,
                ];
            } catch (Throwable) {
                // Try next candidate
            }
        }

        $legacyTool = $this->resolveLegacyPluginTool($rawTitle, $courseToolTitle);
        if (null !== $legacyTool) {
            return $legacyTool;
        }

        return null;
    }

    /**
     * @return array{model: AbstractTool, name: string, plugin: Plugin}|null
     */
    private function resolveLegacyPluginTool(string $rawTitle, string $courseToolTitle): ?array
    {
        foreach ([$rawTitle, $courseToolTitle] as $candidate) {
            $candidate = trim($candidate);
            if ('' === $candidate) {
                continue;
            }

            $plugin = $this->loadLegacyPluginFromToolTitle($candidate);
            if (!$plugin instanceof Plugin
                || !$plugin->isEnabled(true)
                || !$plugin->isCoursePlugin
                || !$plugin->addCourseTool
            ) {
                continue;
            }

            $legacyTool = LegacyPluginCourseTool::fromLegacyPlugin($plugin, $courseToolTitle);

            return [
                'model' => $legacyTool,
                'name' => $legacyTool->getTitle(),
                'plugin' => $plugin,
            ];
        }

        return null;
    }

    private function loadLegacyPluginFromToolTitle(string $candidate): ?Plugin
    {
        try {
            $plugin = $this->pluginHelper->loadLegacyPlugin($candidate);
            if ($plugin instanceof Plugin) {
                return $plugin;
            }
        } catch (Throwable) {
            // Try class-name fallbacks below.
        }

        foreach ($this->buildLegacyPluginClassCandidates($candidate) as $className) {
            if (!class_exists($className) || !method_exists($className, 'create')) {
                continue;
            }

            try {
                $plugin = $className::create();
            } catch (Throwable) {
                continue;
            }

            if ($plugin instanceof Plugin) {
                return $plugin;
            }
        }

        return null;
    }

    /**
     * Build possible legacy plugin class names from a stored tool title.
     *
     * @return string[]
     */
    private function buildLegacyPluginClassCandidates(string $rawTitle): array
    {
        $rawTitle = trim($rawTitle);
        if ('' === $rawTitle) {
            return [];
        }

        $studly = implode('', array_map('ucfirst', preg_split('/[^a-z0-9]+/i', $rawTitle) ?: []));
        $compact = preg_replace('/[^a-z0-9]+/i', '', $rawTitle) ?: $rawTitle;

        return array_values(array_unique(array_filter([
            $rawTitle,
            $rawTitle.'Plugin',
            $studly,
            $studly.'Plugin',
            $compact,
            $compact.'Plugin',
        ], static fn ($value): bool => \is_string($value) && '' !== trim($value))));
    }

    private function shouldHideLegacyPluginCourseTool(?Plugin $plugin, bool $isAllowToEdit, ?string $studentView): bool
    {
        if (!$plugin instanceof Plugin) {
            return false;
        }

        $visibility = $plugin->getToolIconVisibilityPerUserStatus();

        if (Plugin::TAB_FILTER_NO_STUDENT === $visibility) {
            return !$isAllowToEdit || 'studentview' === $studentView;
        }

        if (Plugin::TAB_FILTER_ONLY_STUDENT === $visibility) {
            return $isAllowToEdit && 'studentview' !== $studentView;
        }

        return false;
    }

    private function shouldSkipUnavailableTeacherOnlyLegacyCourseTool(CTool $cTool): bool
    {
        if (!$this->isTeacherOnlyLegacyCourseTool($cTool)) {
            return false;
        }

        foreach ($this->getLegacyPluginToolTitleCandidates($cTool) as $candidate) {
            try {
                if ($this->pluginHelper->isPluginEnabled($candidate)) {
                    return false;
                }
            } catch (Throwable) {
                // Try next candidate.
            }
        }

        return true;
    }

    /**
     * @return string[]
     */
    private function getLegacyPluginToolTitleCandidates(CTool $cTool): array
    {
        $toolTitle = trim((string) $cTool->getTool()->getTitle());
        $courseToolTitle = trim((string) $cTool->getTitle());

        return array_values(array_unique(array_filter([
            $toolTitle,
            $courseToolTitle,
            'NotebookTeacher',
            'Teacher notes',
            'teacher_notes',
        ], static fn ($value): bool => \is_string($value) && '' !== trim($value))));
    }

    private function shouldHideTeacherOnlyLegacyCourseTool(CTool $cTool, bool $isAllowToEdit, ?string $studentView): bool
    {
        if (!$this->isTeacherOnlyLegacyCourseTool($cTool)) {
            return false;
        }

        return !$isAllowToEdit || 'studentview' === $studentView;
    }

    private function isTeacherOnlyLegacyCourseTool(CTool $cTool): bool
    {
        $toolTitle = strtolower(trim((string) $cTool->getTool()->getTitle()));
        $courseToolTitle = strtolower(trim((string) $cTool->getTitle()));

        return \in_array($toolTitle, ['notebookteacher', 'teacher notes', 'teacher notebook'], true)
            || \in_array($courseToolTitle, ['notebookteacher', 'teacher notes', 'teacher notebook'], true);
    }

    /**
     * Build candidate tool names from a DB title.
     *
     * @return string[]
     */
    private function buildToolNameCandidates(string $rawTitle): array
    {
        $rawTitle = trim($rawTitle);
        if ('' === $rawTitle) {
            return [];
        }

        $candidates = [];

        $lower = strtolower($rawTitle);
        $candidates[] = $lower;

        if ($rawTitle !== $lower) {
            $candidates[] = $rawTitle;
        }

        $spaceSnake = strtolower(preg_replace('/[\s\-]+/', '_', $rawTitle) ?? $rawTitle);
        $spaceSnake = trim($spaceSnake, '_');
        $candidates[] = $spaceSnake;

        $alnumSnake = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $rawTitle) ?? $rawTitle);
        $alnumSnake = trim($alnumSnake, '_');
        $candidates[] = $alnumSnake;

        $camelSnake = preg_replace('/(?<!^)[A-Z]/', '_$0', $rawTitle) ?? $rawTitle;
        $camelSnake = strtolower($camelSnake);
        $camelSnake = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $camelSnake) ?? $camelSnake);
        $camelSnake = trim($camelSnake, '_');
        $candidates[] = $camelSnake;

        $candidates[] = str_replace('_', '', $camelSnake);
        $candidates[] = str_replace('_', '', $alnumSnake);

        return array_values(array_unique(array_filter($candidates, static fn ($v) => \is_string($v) && '' !== trim($v))));
    }

    private function shouldRestrictToPositioningOnly(?User $user, int $courseId, ?int $sessionId): array
    {
        if (!$user || !$user->isStudent()) {
            return [false, null];
        }

        $tool = $this->toolChain->getToolFromName('positioning');

        if (!$tool instanceof AbstractPlugin) {
            return [false, null];
        }

        $pluginInstance = Positioning::create();

        if (!$pluginInstance->isEnabled()) {
            return [false, null];
        }

        if ('true' !== $pluginInstance->get('block_course_if_initial_exercise_not_attempted')) {
            return [false, null];
        }

        $initialData = $pluginInstance->getInitialExercise($courseId, $sessionId);

        if (!isset($initialData['exercise_id'])) {
            return [false, null];
        }

        $results = Event::getExerciseResultsByUser(
            $user->getId(),
            (int) $initialData['exercise_id'],
            $courseId,
            $sessionId
        );

        if (empty($results)) {
            return [true, 'positioning'];
        }

        return [false, null];
    }

    private function isHiddenLearningPathAllowedInCourseHome(string $toolName): bool
    {
        if ('learnpath' !== $toolName) {
            return false;
        }

        return 'true' === $this->settingsManager->getSetting(
            'lp.show_invisible_lp_in_course_home',
            true
        );
    }
}
