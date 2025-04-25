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
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Tool\ToolChain;
use Chamilo\CoreBundle\Traits\CourseFromRequestTrait;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\ORM\EntityManagerInterface;
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

        $isAllowToEdit = $user && ($user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_CURRENT_COURSE_TEACHER'));
        $isAllowToEditBack = $user && ($user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_CURRENT_COURSE_TEACHER'));
        $isAllowToSessionEdit = $user && ($user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_CURRENT_COURSE_TEACHER') || $user->hasRole('ROLE_CURRENT_COURSE_SESSION_TEACHER'));

        $allowVisibilityInSession = $this->settingsManager->getSetting('course.allow_edit_tool_visibility_in_session');
        $session = $this->getSession();

        $results = [];

        /** @var CTool $cTool */
        foreach ($result as $cTool) {
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
}
