<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Portfolio;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Portfolio\PortfolioDetails;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\PortfolioCommentRepository;
use Chamilo\CoreBundle\Repository\Node\PortfolioRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use CourseManager;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<PortfolioDetails>
 */
final readonly class PortfolioDetailsProvider implements ProviderInterface
{
    use PortfolioAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private PortfolioRepository $portfolioRepository,
        private PortfolioCommentRepository $commentRepository,
        private Security $security,
        private UserHelper $userHelper,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): PortfolioDetails
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
            throw new AccessDeniedHttpException('You are not allowed to view Portfolio details in this context.');
        }
        $owner = $this->getPortfolioRequestedUser($this->entityManager, $request, $currentUser);
        if ($course instanceof Course
            && $owner->getId() !== $currentUser->getId()
            && !$this->isPortfolioCourseUser($owner, $course, $session)
        ) {
            throw new AccessDeniedHttpException('The requested Portfolio owner is outside the current course context.');
        }
        $canManage = $course instanceof Course
            && $this->canManagePortfolioCourse($this->security, $currentUser, $course, $session);
        if ($owner->getId() !== $currentUser->getId() && !$canManage && !$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('You are not allowed to view another user Portfolio details.');
        }

        $advancedSharing = $course instanceof Course && $this->portfolioBoolean(
            $this->settingsManager->getSetting('platform.portfolio_advanced_sharing', true),
        );
        $showBasePosts = $session instanceof Session && $this->portfolioBoolean(
            $this->settingsManager->getSetting('platform.portfolio_show_base_course_post_in_sessions', true),
        );

        $items = $this->loadOwnerItems(
            $owner,
            $currentUser,
            $course,
            $session,
            $advancedSharing,
            $showBasePosts,
            $canManage,
        );
        $comments = $this->loadOwnerComments(
            $owner,
            $currentUser,
            $course,
            $session,
            $advancedSharing,
            $showBasePosts,
            $canManage,
        );

        $result = new PortfolioDetails();
        $result->mode = $course instanceof Course ? 'course' : 'personal';
        $result->owner = $this->normalizePortfolioUser($owner);
        $result->canSelectOwner = $canManage || $this->security->isGranted('ROLE_ADMIN');
        $result->owners = $result->canSelectOwner && $course instanceof Course
            ? $this->loadCourseOwners($course, $session)
            : [$result->owner];
        $result->requiredItems = $course instanceof Course ? $this->getCourseSettingInt('portfolio_number_items', $course) : 0;
        $result->requiredComments = $course instanceof Course ? $this->getCourseSettingInt('portfolio_number_comments', $course) : 0;

        foreach ($items as $item) {
            $node = $item->getResourceNode();
            $result->items[] = [
                'id' => (int) $item->getId(),
                'title' => \trim(\strip_tags($item->getTitle())),
                'createdAt' => $this->formatPortfolioDate($node->getCreatedAt()),
                'updatedAt' => $this->formatPortfolioDate($node->getUpdatedAt()),
                'category' => $item->getCategory()?->getTitle() ?? '',
                'commentsCount' => $item->getComments()->count(),
                'score' => $item->getScore(),
                'course' => $this->getPortfolioResourceContext($item),
            ];
            $result->itemScoreTotal += (float) ($item->getScore() ?? 0.0);
        }
        foreach ($comments as $comment) {
            $result->comments[] = [
                'id' => (int) $comment->getId(),
                'itemId' => (int) $comment->getItem()->getId(),
                'itemTitle' => \trim(\strip_tags($comment->getItem()->getTitle())),
                'excerpt' => $this->portfolioExcerpt($comment->getContent(), 240),
                'date' => $this->formatPortfolioDate($comment->getDate()),
                'score' => $comment->getScore(),
            ];
            $result->commentScoreTotal += (float) ($comment->getScore() ?? 0.0);
        }
        $result->totalItems = \count($result->items);
        $result->totalComments = \count($result->comments);

        return $result;
    }

    /**
     * @return array<int, Portfolio>
     */
    private function loadOwnerItems(
        User $owner,
        User $currentUser,
        ?Course $course,
        ?Session $session,
        bool $advancedSharing,
        bool $showBasePosts,
        bool $canManage,
    ): array {
        $qb = $this->portfolioRepository->createQueryBuilder('item')
            ->select('DISTINCT item', 'node', 'links', 'category')
            ->innerJoin('item.resourceNode', 'node')
            ->leftJoin('node.resourceLinks', 'links')
            ->leftJoin('item.category', 'category')
            ->andWhere('node.creator = :owner')
            ->setParameter('owner', (int) $owner->getId(), Types::INTEGER)
            ->orderBy('node.createdAt', 'DESC')
        ;
        if ($course instanceof Course) {
            $qb
                ->andWhere('links.course = :course')
                ->setParameter('course', (int) $course->getId(), Types::INTEGER)
            ;
            if ($session instanceof Session) {
                $qb
                    ->andWhere('(links.session = :session OR links.session IS NULL)')
                    ->setParameter('session', (int) $session->getId(), Types::INTEGER)
                ;
            } else {
                $qb->andWhere('links.session IS NULL');
            }
        }

        /** @var array<int, Portfolio> $items */
        $items = $qb->getQuery()->getResult();

        return \array_values(\array_filter($items, fn (Portfolio $item): bool => $this->canViewPortfolioItem(
            $item,
            $currentUser,
            $course,
            $session,
            $showBasePosts,
            $advancedSharing,
            $canManage,
        )));
    }

    /**
     * @return array<int, PortfolioComment>
     */
    private function loadOwnerComments(
        User $owner,
        User $currentUser,
        ?Course $course,
        ?Session $session,
        bool $advancedSharing,
        bool $showBasePosts,
        bool $canManage,
    ): array {
        $qb = $this->commentRepository->createQueryBuilder('comment')
            ->select('DISTINCT comment', 'node', 'item', 'itemNode', 'itemLinks')
            ->innerJoin('comment.resourceNode', 'node')
            ->innerJoin('comment.item', 'item')
            ->innerJoin('item.resourceNode', 'itemNode')
            ->leftJoin('itemNode.resourceLinks', 'itemLinks')
            ->andWhere('node.creator = :owner')
            ->setParameter('owner', (int) $owner->getId(), Types::INTEGER)
            ->orderBy('comment.date', 'DESC')
        ;

        if ($course instanceof Course) {
            $qb
                ->andWhere('itemLinks.course = :course')
                ->setParameter('course', (int) $course->getId(), Types::INTEGER)
            ;
            if ($session instanceof Session) {
                $qb
                    ->andWhere('(itemLinks.session = :session OR itemLinks.session IS NULL)')
                    ->setParameter('session', (int) $session->getId(), Types::INTEGER)
                ;
            } else {
                $qb->andWhere('itemLinks.session IS NULL');
            }
        }

        /** @var array<int, PortfolioComment> $comments */
        $comments = $qb->getQuery()->getResult();

        return \array_values(\array_filter(
            $comments,
            fn (PortfolioComment $comment): bool => $this->canViewPortfolioItem(
                $comment->getItem(),
                $currentUser,
                $course,
                $session,
                $showBasePosts,
                $advancedSharing,
                $canManage,
            ) && $this->canViewPortfolioComment(
                $comment,
                $currentUser,
                $course,
                $session,
                $advancedSharing,
                $showBasePosts,
            ),
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadCourseOwners(Course $course, ?Session $session): array
    {
        if (!\class_exists(CourseManager::class)) {
            return [];
        }
        $rows = CourseManager::get_user_list_from_course_code(
            $course->getCode(),
            $session?->getId() ?? 0,
            null,
            null,
            null,
            false,
            false,
            false,
            [],
            [],
            [],
            true,
        );
        $result = [];
        foreach ($rows as $row) {
            $id = (int) ($row['user_id'] ?? $row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $user = $this->entityManager->getRepository(User::class)->find($id);
            if ($user instanceof User) {
                $result[] = $this->normalizePortfolioUser($user);
            }
        }
        \usort($result, static fn (array $a, array $b): int => \strcasecmp($a['fullName'], $b['fullName']));

        return $result;
    }

    private function getCourseSettingInt(string $variable, Course $course): int
    {
        if (!\function_exists('api_get_course_setting') || !\function_exists('api_get_course_info')) {
            return 0;
        }

        return \max(0, (int) \api_get_course_setting($variable, \api_get_course_info($course->getCode())));
    }
}
