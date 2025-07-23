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

        $allowVisibilityInSession = $this->settingsManager->getSetting('course.allow_edit_tool_visibility_in_session');
        $session = $this->getSession();
        $course = $this->getCourse();

        [$restrictToPositioning, $allowedToolName] = $this->shouldRestrictToPositioningOnly($user, $course->getId(), $session?->getId());

        $results = [];

        /** @var CTool $cTool */
        foreach ($result as $cTool) {
            if ($restrictToPositioning && $cTool->getTool()->getTitle() !== $allowedToolName) {
                continue;
            }

            $toolModel = $this->toolChain->getToolFromName(
                $cTool->getTool()->getTitle()
            );

            if (!$isAllowToEdit && 'admin' === $toolModel->getCategory()) {
                continue;
            }

            $resourceLinks = $cTool->getResourceNode()->getResourceLinks();

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
                $notPublishedLink = ResourceLink::VISIBILITY_PUBLISHED !== $resourceLinks->first()->getVisibility();

                if ($notPublishedLink) {
                    continue;
                }
            }

            $results[] = $this->transformer->transform($cTool);
        }

        return $results;
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
