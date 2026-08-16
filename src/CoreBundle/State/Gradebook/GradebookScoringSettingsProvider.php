<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookScoringSettings;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookScoreDisplay;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<GradebookScoringSettings>
 */
final readonly class GradebookScoringSettingsProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private GradebookContextResolver $contextResolver,
        private EntityManagerInterface $entityManager,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GradebookScoringSettings
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $resolved = $this->contextResolver->resolve($request);
        $rootCategory = $resolved['rootCategory'];
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $category = $this->contextResolver->getSelectedCategory(
            $request,
            $resolved['course'],
            $resolved['session'],
            $rootCategory,
        );

        $customEnabled = $this->contextResolver->isSettingEnabled('gradebook.gradebook_score_display_custom');
        $coloringEnabled = $this->contextResolver->isSettingEnabled('gradebook.my_display_coloring');
        $upperLimitIncluded = $this->contextResolver->isSettingEnabled('gradebook.gradebook_score_display_upperlimit');
        $teachersCanChange = $this->contextResolver->isSettingEnabled('gradebook.teachers_can_change_score_settings');

        $globalColorSplit = (int) ($this->settingsManager->getSetting('gradebook.gradebook_score_display_colorsplit', true) ?: 50);
        $rows = $this->entityManager->getRepository(GradebookScoreDisplay::class)->findBy(
            ['category' => $category],
            ['score' => 'ASC'],
        );

        $ranges = [];
        $colorSplit = $globalColorSplit;
        foreach ($rows as $row) {
            if (!$row instanceof GradebookScoreDisplay) {
                continue;
            }
            if ([] === $ranges && $coloringEnabled) {
                $colorSplit = (int) round((float) $row->getScoreColorPercent());
            }
            $ranges[] = [
                'score' => (float) $row->getScore(),
                'display' => (string) $row->getDisplay(),
            ];
        }

        $resource = new GradebookScoringSettings();
        $resource->context = [
            'cid' => (int) $resolved['course']->getId(),
            'sid' => (int) ($resolved['session']?->getId() ?? 0),
            'gid' => $resolved['groupId'],
            'node' => $request->query->getInt('node'),
        ];
        $resource->category = [
            'id' => (int) $category->getId(),
            'title' => $category->getTitle(),
        ];
        $resource->canManage = $resolved['canManage'] && $teachersCanChange;
        $resource->customEnabled = $customEnabled;
        $resource->coloringEnabled = $coloringEnabled;
        $resource->upperLimitIncluded = $upperLimitIncluded;
        $resource->colorSplitPercent = max(0, min(100, $colorSplit));
        $resource->ranges = $ranges;
        $resource->csrfToken = (string) $this->csrfTokenManager->getToken(GradebookScoringActionProcessor::CSRF_TOKEN_ID);

        return $resource;
    }
}
