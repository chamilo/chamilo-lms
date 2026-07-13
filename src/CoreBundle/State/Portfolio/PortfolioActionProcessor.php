<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Portfolio;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Portfolio\PortfolioAction;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\PortfolioItemDeletedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemHighlightedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemScoredEvent;
use Chamilo\CoreBundle\Event\PortfolioItemVisibilityChangedEvent;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\PortfolioRepository;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<PortfolioAction, PortfolioAction>
 */
final readonly class PortfolioActionProcessor implements ProcessorInterface
{
    use PortfolioWriteHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private PortfolioRepository $portfolioRepository,
        private ResourceLinkRepository $resourceLinkRepository,
        private Security $security,
        private UserHelper $userHelper,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): PortfolioAction {
        if (!$data instanceof PortfolioAction) {
            throw new BadRequestHttpException('Portfolio action data is required.');
        }
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }
        $this->validatePortfolioCsrfToken($this->csrfTokenManager, ['csrfToken' => $data->csrfToken]);

        $currentUser = $this->getPortfolioCurrentUser($this->userHelper);
        $course = $this->getPortfolioCourse($this->entityManager, $request);
        $session = $this->getPortfolioSession($this->entityManager, $request, $course);
        $advancedSharing = $course instanceof Course && $this->portfolioBoolean(
            $this->settingsManager->getSetting('platform.portfolio_advanced_sharing', true),
        );
        $showBasePosts = $session instanceof Session && $this->portfolioBoolean(
            $this->settingsManager->getSetting('platform.portfolio_show_base_course_post_in_sessions', true),
        );
        $canManage = $course instanceof Course
            && $this->canManagePortfolioCourse($this->security, $currentUser, $course, $session);

        $itemId = (int) ($uriVariables['id'] ?? 0);
        $item = $this->findPortfolioItem($this->entityManager, $itemId);
        $this->assertPortfolioItemContext(
            $item,
            $currentUser,
            $course,
            $session,
            $showBasePosts,
            $advancedSharing,
            $canManage,
        );

        $result = new PortfolioAction();
        $result->id = $itemId;

        switch ($data->action) {
            case 'delete':
                $this->assertPortfolioItemOwner($item, $currentUser);
                $this->eventDispatcher->dispatch(
                    new PortfolioItemDeletedEvent(['portfolio' => $item]),
                    Events::PORTFOLIO_ITEM_DELETED,
                );
                $this->entityManager->remove($item);
                $this->entityManager->flush();
                $result->affectedIds = [$itemId];

                return $result;

            case 'toggle_visibility':
                $this->assertPortfolioItemOwner($item, $currentUser);
                if (!$course instanceof Course || $advancedSharing) {
                    throw new BadRequestHttpException('Use explicit recipient visibility in this Portfolio context.');
                }
                $next = match ($item->getVisibility()) {
                    Portfolio::VISIBILITY_HIDDEN => Portfolio::VISIBILITY_VISIBLE,
                    Portfolio::VISIBILITY_VISIBLE => Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER,
                    default => Portfolio::VISIBILITY_HIDDEN,
                };
                $item->setVisibility($next);
                $this->entityManager->flush();
                $this->eventDispatcher->dispatch(
                    new PortfolioItemVisibilityChangedEvent(['portfolio' => $item, 'recipients' => []]),
                    Events::PORTFOLIO_ITEM_VISIBILITY_CHANGED,
                );

                return $result;

            case 'set_visibility':
                $this->assertPortfolioItemOwner($item, $currentUser);
                if (!$course instanceof Course) {
                    $visibility = (int) ($data->visibility ?? Portfolio::VISIBILITY_VISIBLE);
                    if (!\in_array($visibility, [Portfolio::VISIBILITY_HIDDEN, Portfolio::VISIBILITY_VISIBLE], true)) {
                        throw new BadRequestHttpException('The requested personal Portfolio visibility is invalid.');
                    }
                    $item->setVisibility($visibility);
                    $recipientIds = [];
                } else {
                    $visibility = (int) ($data->visibility ?? Portfolio::VISIBILITY_VISIBLE);
                    if ($advancedSharing) {
                        $recipientIds = $this->applyPortfolioVisibility(
                            $item,
                            $visibility,
                            $data->recipientIds,
                            $course,
                            $session,
                            $this->entityManager,
                            $this->resourceLinkRepository,
                        );
                    } else {
                        if (!\in_array($visibility, [
                            Portfolio::VISIBILITY_HIDDEN,
                            Portfolio::VISIBILITY_VISIBLE,
                            Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER,
                        ], true)) {
                            throw new BadRequestHttpException('Advanced Portfolio sharing is not enabled.');
                        }
                        $this->resourceLinkRepository->removeUserLinks($item, $course, $session);
                        $item->setVisibility($visibility);
                        $recipientIds = [];
                    }
                }
                $this->entityManager->flush();
                $this->eventDispatcher->dispatch(
                    new PortfolioItemVisibilityChangedEvent([
                        'portfolio' => $item,
                        'recipients' => $recipientIds,
                    ]),
                    Events::PORTFOLIO_ITEM_VISIBILITY_CHANGED,
                );

                return $result;

            case 'toggle_highlight':
                if (!$canManage || !$course instanceof Course) {
                    throw new AccessDeniedHttpException('Only a course teacher can highlight Portfolio items.');
                }
                $item->setIsHighlighted(!$item->isHighlighted());
                $this->entityManager->flush();
                if ($item->isHighlighted()) {
                    $this->eventDispatcher->dispatch(
                        new PortfolioItemHighlightedEvent(['portfolio' => $item]),
                        Events::PORTFOLIO_ITEM_HIGHLIGHTED,
                    );
                }

                return $result;

            case 'toggle_template':
                $this->assertPortfolioItemOwner($item, $currentUser);
                $item->setIsTemplate(!$item->isTemplate());
                $this->entityManager->flush();

                return $result;

            case 'score':
                if (!$canManage || !$course instanceof Course) {
                    throw new AccessDeniedHttpException('Only a course teacher can score Portfolio items.');
                }
                if (!$this->isPortfolioCourseSettingEnabled('qualify_portfolio_item', $course)) {
                    throw new AccessDeniedHttpException('Portfolio item scoring is not enabled in this course.');
                }
                $item->setScore($this->normalizePortfolioScore($data->score, $course));
                $this->entityManager->flush();
                $this->eventDispatcher->dispatch(
                    new PortfolioItemScoredEvent(['portfolio' => $item]),
                    Events::PORTFOLIO_ITEM_SCORED,
                );

                return $result;

            case 'delete_attachment':
                $this->assertPortfolioItemOwner($item, $currentUser);
                $this->removePortfolioAttachment($item, (int) $data->attachmentId, $this->entityManager);
                $this->entityManager->flush();

                return $result;

            case 'copy_to_own':
                $copy = $this->createPortfolioCopy(
                    $item,
                    null,
                    $currentUser,
                    $course,
                    $session,
                    \sprintf('Portfolio item by %s', $item->getResourceNode()->getCreator()?->getFullName() ?? ''),
                    '',
                );
                $this->entityManager->persist($copy);
                $this->entityManager->flush();
                $result->affectedIds = [(int) $copy->getId()];

                return $result;

            case 'copy_to_students':
                if (!$canManage || !$course instanceof Course) {
                    throw new AccessDeniedHttpException('Only a course teacher can copy Portfolio items to learners.');
                }
                $studentIds = $this->normalizePortfolioIds($data->studentIds);
                if ([] === $studentIds) {
                    throw new BadRequestHttpException('At least one learner is required.');
                }
                $title = $this->sanitizePortfolioHtml($data->title);
                $content = $this->sanitizePortfolioHtml($data->content);
                if ('' === \trim(\strip_tags($title))) {
                    throw new BadRequestHttpException('A title is required for copied Portfolio items.');
                }
                foreach ($studentIds as $studentId) {
                    $student = $this->entityManager->getRepository(User::class)->find($studentId);
                    if (!$student instanceof User || !$this->isPortfolioCourseUser($student, $course, $session)) {
                        throw new AccessDeniedHttpException('One or more selected learners are outside the current context.');
                    }
                    $copy = $this->createPortfolioCopy($item, null, $student, $course, $session, $title, $content);
                    $this->entityManager->persist($copy);
                    $this->entityManager->flush();
                    $result->affectedIds[] = (int) $copy->getId();
                }

                return $result;
        }

        throw new BadRequestHttpException('The requested Portfolio item action is not supported.');
    }

    private function createPortfolioCopy(
        Portfolio $originItem,
        ?int $originCommentId,
        User $owner,
        ?Course $course,
        ?Session $session,
        string $title,
        string $content,
    ): Portfolio {
        $copy = (new Portfolio())
            ->setVisibility(Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER)
            ->setTitle($title)
            ->setContent($content)
            ->setCreator($owner)
            ->setParent($owner)
            ->setOrigin($originCommentId ?? (int) $originItem->getId())
            ->setOriginType(null === $originCommentId ? Portfolio::TYPE_ITEM : Portfolio::TYPE_COMMENT)
        ;
        if ($course instanceof Course) {
            $copy->addCourseLink($course, $session);
        }

        return $copy;
    }
}
