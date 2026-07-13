<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Portfolio;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Portfolio\PortfolioCommentAction;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\PortfolioCommentScoredEvent;
use Chamilo\CoreBundle\Helpers\UserHelper;
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
 * @implements ProcessorInterface<PortfolioCommentAction, PortfolioCommentAction>
 */
final readonly class PortfolioCommentActionProcessor implements ProcessorInterface
{
    use PortfolioWriteHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
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
    ): PortfolioCommentAction {
        if (!$data instanceof PortfolioCommentAction) {
            throw new BadRequestHttpException('Portfolio comment action data is required.');
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

        $comment = $this->findPortfolioComment($this->entityManager, (int) ($uriVariables['id'] ?? 0));
        $item = $comment->getItem();
        $this->assertPortfolioItemContext(
            $item,
            $currentUser,
            $course,
            $session,
            $showBasePosts,
            $advancedSharing,
            $canManage,
        );
        if (!$this->canViewPortfolioComment(
            $comment,
            $currentUser,
            $course,
            $session,
            $advancedSharing,
            $showBasePosts,
        )) {
            throw new AccessDeniedHttpException('The portfolio comment is not visible in the current context.');
        }

        $result = new PortfolioCommentAction();
        $result->id = (int) $comment->getId();
        $result->itemId = (int) $item->getId();

        switch ($data->action) {
            case 'delete':
                $this->assertPortfolioCommentOwner($comment, $currentUser);
                $this->entityManager->remove($comment);
                $this->entityManager->flush();
                $result->affectedIds = [(int) $comment->getId()];

                return $result;

            case 'toggle_important':
                if (!$canManage) {
                    throw new AccessDeniedHttpException('Only a course teacher can mark comments as important.');
                }
                $comment->setIsImportant(!$comment->isImportant());
                $this->entityManager->flush();

                return $result;

            case 'toggle_template':
                $this->assertPortfolioCommentOwner($comment, $currentUser);
                $comment->setIsTemplate(!$comment->isTemplate());
                $this->entityManager->flush();

                return $result;

            case 'score':
                if (!$canManage || !$course instanceof Course) {
                    throw new AccessDeniedHttpException('Only a course teacher can score Portfolio comments.');
                }
                if (!$this->isPortfolioCourseSettingEnabled('qualify_portfolio_comment', $course)) {
                    throw new AccessDeniedHttpException('Portfolio comment scoring is not enabled in this course.');
                }
                $comment->setScore($this->normalizePortfolioScore($data->score, $course));
                $this->entityManager->flush();
                $this->eventDispatcher->dispatch(
                    new PortfolioCommentScoredEvent(['comment' => $comment]),
                    Events::PORTFOLIO_COMMENT_SCORED,
                );

                return $result;

            case 'set_visibility':
                $this->assertPortfolioCommentOwner($comment, $currentUser);
                if (!$course instanceof Course) {
                    $comment->setVisibility(PortfolioComment::VISIBILITY_VISIBLE);
                    $recipients = [];
                } elseif ($advancedSharing) {
                    $recipients = $this->applyPortfolioVisibility(
                        $comment,
                        (int) ($data->visibility ?? PortfolioComment::VISIBILITY_VISIBLE),
                        $data->recipientIds,
                        $course,
                        $session,
                        $this->entityManager,
                        $this->resourceLinkRepository,
                        true,
                    );
                } else {
                    $visibility = (int) ($data->visibility ?? PortfolioComment::VISIBILITY_VISIBLE);
                    if (PortfolioComment::VISIBILITY_VISIBLE !== $visibility) {
                        throw new BadRequestHttpException('Advanced Portfolio sharing is not enabled.');
                    }
                    $this->resourceLinkRepository->removeUserLinks($comment, $course, $session);
                    $comment->setVisibility(PortfolioComment::VISIBILITY_VISIBLE);
                    $recipients = [];
                }
                $this->entityManager->flush();

                return $result;

            case 'delete_attachment':
                $this->assertPortfolioCommentOwner($comment, $currentUser);
                $this->removePortfolioAttachment($comment, (int) $data->attachmentId, $this->entityManager);
                $this->entityManager->flush();

                return $result;

            case 'copy_to_own':
                $copy = $this->createCommentPortfolioCopy(
                    $comment,
                    $currentUser,
                    $course,
                    $session,
                    \sprintf('Comment by %s', $comment->getResourceNode()->getCreator()?->getFullName() ?? ''),
                    '',
                );
                $this->entityManager->persist($copy);
                $this->entityManager->flush();
                $result->affectedIds = [(int) $copy->getId()];

                return $result;

            case 'copy_to_students':
                if (!$canManage || !$course instanceof Course) {
                    throw new AccessDeniedHttpException('Only a course teacher can copy comments to learner portfolios.');
                }
                $studentIds = $this->normalizePortfolioIds($data->studentIds);
                if ([] === $studentIds) {
                    throw new BadRequestHttpException('At least one learner is required.');
                }
                $title = $this->sanitizePortfolioHtml($data->title);
                $content = $this->sanitizePortfolioHtml($data->content);
                if ('' === trim(strip_tags($title))) {
                    throw new BadRequestHttpException('A title is required for copied Portfolio comments.');
                }
                foreach ($studentIds as $studentId) {
                    $student = $this->entityManager->getRepository(User::class)->find($studentId);
                    if (!$student instanceof User || !$this->isPortfolioCourseUser($student, $course, $session)) {
                        throw new AccessDeniedHttpException('One or more selected learners are outside the current context.');
                    }
                    $copy = $this->createCommentPortfolioCopy($comment, $student, $course, $session, $title, $content);
                    $this->entityManager->persist($copy);
                    $this->entityManager->flush();
                    $result->affectedIds[] = (int) $copy->getId();
                }

                return $result;
        }

        throw new BadRequestHttpException('The requested Portfolio comment action is not supported.');
    }

    private function createCommentPortfolioCopy(
        PortfolioComment $origin,
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
            ->setOrigin((int) $origin->getId())
            ->setOriginType(Portfolio::TYPE_COMMENT)
        ;
        if ($course instanceof Course) {
            $copy->addCourseLink($course, $session);
        }

        return $copy;
    }
}
