<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiCategoryCollection;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<WikiCategoryCollection>
 */
final readonly class WikiCategoryProvider implements ProviderInterface
{
    use WikiAccessHelperTrait;

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private WikiCategoryService $categoryService,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): WikiCategoryCollection
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $this->assertWikiToolEnabled($this->entityManager, $course);
        $this->assertWikiRouteNode($course, $request);
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $this->assertWikiSessionBelongsToCourse($session, $course);
        $group = $this->cidReqHelper->getDoctrineGroupEntity();
        $this->assertWikiGroupBelongsToContext($group, $course, $session);

        if ($this->isWikiStudentView($request)) {
            throw new AccessDeniedHttpException('Wiki categories cannot be managed in student view.');
        }

        $canManage = $this->canManageWikiContext(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            null,
        );
        if (!$canManage) {
            throw new AccessDeniedHttpException('You are not allowed to manage Wiki categories.');
        }

        $enabled = $this->isWikiCourseSettingEnabled(
            $this->entityManager,
            $course,
            'wiki_categories_enabled',
            false,
        );

        $resource = new WikiCategoryCollection();
        $resource->enabled = $enabled;
        $resource->canManage = true;
        $resource->categories = $enabled ? $this->categoryService->getManagementRows($course, $session) : [];

        return $resource;
    }
}
