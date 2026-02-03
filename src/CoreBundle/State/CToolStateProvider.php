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
use Chamilo\CoreBundle\Tool\ToolChain;
use Chamilo\CoreBundle\Traits\CourseFromRequestTrait;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\ORM\EntityManagerInterface;
use Event;
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

        $isAllowToEdit = $user && ($user->isAdmin() || $user->hasRole('ROLE_CURRENT_COURSE_TEACHER'));
        $isAllowToEditBack = $isAllowToEdit;
        $isAllowToSessionEdit = $user && (
            $user->isAdmin()
                || $user->hasRole('ROLE_CURRENT_COURSE_TEACHER')
                || $user->hasRole('ROLE_CURRENT_COURSE_SESSION_TEACHER')
        );

        $allowVisibilityInSession = $this->settingsManager->getSetting('session.allow_edit_tool_visibility_in_session');
        $session = $this->getSession();
        $course = $this->getCourse();

        [$restrictToPositioning, $allowedToolName] = $this->shouldRestrictToPositioningOnly(
            $user,
            $course->getId(),
            $session?->getId()
        );

        $results = [];

        /** @var CTool $cTool */
        foreach ($result as $cTool) {
            $resolved = $this->resolveToolModelFromCTool($cTool);
            if (null === $resolved) {
                continue;
            }

            $toolModel = $resolved['model'];
            $resolvedName = $resolved['name'];

            // If a positioning restriction is active, keep only the allowed tool.
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
                    // Set the session link as unique to include in repsonse
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

                $notPublishedLink = ResourceLink::VISIBILITY_PUBLISHED !== $firstLink->getVisibility();
                if ($notPublishedLink) {
                    continue;
                }
            }

            $results[] = $this->transformer->transform($cTool);
        }

        return $results;
    }

    /**
     * Resolve a ToolChain model for a given CTool safely.
     * Tries multiple candidate names derived from the stored tool title.
     *
     * @return array{model: object, name: string}|null
     */
    private function resolveToolModelFromCTool(CTool $cTool): ?array
    {
        $toolEntity = $cTool->getTool();
        $rawTitle = $toolEntity ? (string) $toolEntity->getTitle() : '';

        foreach ($this->buildToolNameCandidates($rawTitle) as $candidate) {
            try {
                $model = $this->toolChain->getToolFromName($candidate);

                // Return the candidate we used so other logic can rely on a stable key.
                return [
                    'model' => $model,
                    'name' => $candidate,
                ];
            } catch (Throwable) {
                // Try next candidate
            }
        }

        return null;
    }

    /**
     * Build candidate tool names from a DB title.
     * This keeps backward compatibility while supporting human titles like "H5P import".
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

        // Prefer lowercase first (ToolChain commonly uses lowercase keys)
        $lower = strtolower($rawTitle);
        $candidates[] = $lower;

        // Original as fallback
        if ($rawTitle !== $lower) {
            $candidates[] = $rawTitle;
        }

        // Replace spaces/dashes with underscores (e.g., "H5P import" -> "h5p_import")
        $spaceSnake = strtolower(preg_replace('/[\s\-]+/', '_', $rawTitle) ?? $rawTitle);
        $spaceSnake = trim($spaceSnake, '_');
        $candidates[] = $spaceSnake;

        // Replace any non-alnum with underscores
        $alnumSnake = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $rawTitle) ?? $rawTitle);
        $alnumSnake = trim($alnumSnake, '_');
        $candidates[] = $alnumSnake;

        // CamelCase to snake_case (e.g., "CustomCertificate" -> "custom_certificate")
        $camelSnake = preg_replace('/(?<!^)[A-Z]/', '_$0', $rawTitle) ?? $rawTitle;
        $camelSnake = strtolower($camelSnake);
        $camelSnake = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $camelSnake) ?? $camelSnake);
        $camelSnake = trim($camelSnake, '_');
        $candidates[] = $camelSnake;

        // Some tool keys might be stored without underscores
        $candidates[] = str_replace('_', '', $camelSnake);
        $candidates[] = str_replace('_', '', $alnumSnake);

        // Unique + non-empty
        $candidates = array_values(array_unique(array_filter($candidates, static fn ($v) => \is_string($v) && '' !== trim($v))));

        return $candidates;
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

        if (!$this->pluginHelper->isPluginEnabled('positioning')) {
            return [false, null];
        }

        $pluginInstance = $this->pluginHelper->loadLegacyPlugin('Positioning');

        if (!$pluginInstance || 'true' !== $pluginInstance->get('block_course_if_initial_exercise_not_attempted')) {
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
}
