<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Portfolio;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Portfolio\PortfolioManagement;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\PortfolioCategory;
use Chamilo\CoreBundle\Entity\PortfolioRelTag;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<PortfolioManagement>
 */
final readonly class PortfolioManagementProvider implements ProviderInterface
{
    use PortfolioAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private UserHelper $userHelper,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): PortfolioManagement
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }
        $currentUser = $this->getPortfolioCurrentUser($this->userHelper);
        $course = $this->getPortfolioCourse($this->entityManager, $request);
        $session = $this->getPortfolioSession($this->entityManager, $request, $course);
        if ($course instanceof Course && !$this->canReadPortfolioCourse(
            $this->security,
            $this->userHelper,
            $this->settingsManager,
            $course,
            $session,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to manage Portfolio in this context.');
        }

        $result = new PortfolioManagement();
        $result->canManageCategories = $this->security->isGranted('ROLE_ADMIN');
        $result->canManageTags = $course instanceof Course
            && $this->canManagePortfolioCourse($this->security, $currentUser, $course, $session);
        if (!$result->canManageCategories && !$result->canManageTags) {
            throw new AccessDeniedHttpException('You are not allowed to manage Portfolio categories or tags.');
        }
        $result->csrfTokenValue = $this->csrfTokenManager->getToken('portfolio_action')->getValue();

        /** @var array<int, PortfolioCategory> $categories */
        $categories = $this->entityManager->getRepository(PortfolioCategory::class)->findBy([], ['title' => 'ASC']);
        $result->categories = \array_map(static fn (PortfolioCategory $category): array => [
            'id' => (int) $category->getId(),
            'title' => $category->getTitle(),
            'description' => (string) $category->getDescription(),
            'visible' => $category->isVisible(),
            'parentId' => $category->getParent()?->getId(),
            'itemsCount' => $category->getItems()->count(),
        ], $categories);

        if ($course instanceof Course) {
            /** @var array<int, PortfolioRelTag> $relations */
            $relations = $this->entityManager->getRepository(PortfolioRelTag::class)->findBy(
                ['course' => $course, 'session' => $session],
            );
            foreach ($relations as $relation) {
                $tag = $relation->getTag();
                $result->tags[] = [
                    'id' => (int) $tag->getId(),
                    'title' => $tag->getTag(),
                    'count' => $tag->getCount(),
                ];
            }
            \usort($result->tags, static fn (array $a, array $b): int => \strcasecmp($a['title'], $b['title']));
        }

        return $result;
    }
}
