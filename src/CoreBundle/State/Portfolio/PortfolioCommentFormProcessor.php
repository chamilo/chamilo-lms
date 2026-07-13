<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Portfolio;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Portfolio\PortfolioCommentForm;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\PortfolioCommentEditedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemCommentedEvent;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\PortfolioCommentRepository;
use Chamilo\CoreBundle\Repository\Node\PortfolioRepository;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CoreBundle\Security\Upload\UploadFilenamePolicy;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PortfolioNotifier;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

/**
 * @implements ProcessorInterface<mixed, PortfolioCommentForm>
 */
final readonly class PortfolioCommentFormProcessor implements ProcessorInterface
{
    use PortfolioWriteHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private PortfolioRepository $portfolioRepository,
        private PortfolioCommentRepository $commentRepository,
        private ResourceLinkRepository $resourceLinkRepository,
        private Security $security,
        private UserHelper $userHelper,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private UploadFilenamePolicy $uploadFilenamePolicy,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PortfolioCommentForm
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }
        $payload = $this->getPortfolioPayload($request);
        $this->validatePortfolioCsrfToken($this->csrfTokenManager, $payload);

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
            throw new AccessDeniedHttpException('You are not allowed to comment in this Portfolio context.');
        }
        if ($session instanceof Session && Session::READ_ONLY === $session->getVisibility()) {
            throw new AccessDeniedHttpException('Portfolio comments are disabled in this read-only session.');
        }

        $advancedSharing = $course instanceof Course && $this->portfolioBoolean(
            $this->settingsManager->getSetting('platform.portfolio_advanced_sharing', true),
        );
        $showBasePosts = $session instanceof Session && $this->portfolioBoolean(
            $this->settingsManager->getSetting('platform.portfolio_show_base_course_post_in_sessions', true),
        );
        $canManage = $course instanceof Course
            && $this->canManagePortfolioCourse($this->security, $currentUser, $course, $session);

        $commentId = (int) ($payload['commentId'] ?? 0);
        $itemId = (int) ($payload['itemId'] ?? 0);
        $isUpdate = $commentId > 0;
        if (!$isUpdate && $itemId <= 0) {
            throw new BadRequestHttpException('A valid Portfolio item id is required.');
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            if ($isUpdate) {
                $comment = $this->findPortfolioComment($this->entityManager, $commentId);
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
                $this->assertPortfolioCommentOwner($comment, $currentUser);
            } else {
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

                if ($session instanceof Session && $showBasePosts) {
                    $contextInfo = $this->getPortfolioResourceContext($item);
                    if (null === $contextInfo['sessionId'] && !$item->isDuplicatedInSession($session)) {
                        $item = $item->duplicateInSession($session);
                        $this->entityManager->persist($item);
                        $this->entityManager->flush();
                    }
                }

                $parent = $item;
                $parentId = (int) ($payload['parentId'] ?? 0);
                if ($parentId > 0) {
                    $parentComment = $this->findPortfolioComment($this->entityManager, $parentId);
                    if ($parentComment->getItem()->getId() !== $item->getId()) {
                        throw new AccessDeniedHttpException('The parent comment belongs to another Portfolio item.');
                    }
                    if (!$this->canViewPortfolioComment(
                        $parentComment,
                        $currentUser,
                        $course,
                        $session,
                        $advancedSharing,
                        $showBasePosts,
                    )) {
                        throw new AccessDeniedHttpException('The parent comment is not visible in the current context.');
                    }
                    $parent = $parentComment;
                }

                $comment = (new PortfolioComment())
                    ->setItem($item)
                    ->setDate(new DateTime())
                    ->setCreator($currentUser)
                    ->setParent($parent)
                ;
                if ($course instanceof Course) {
                    $comment->addCourseLink($course, $session);
                }
            }

            $content = $this->sanitizePortfolioHtml((string) ($payload['content'] ?? ''));
            if ('' === \trim(\strip_tags($content))) {
                throw new BadRequestHttpException('Portfolio comment content is required.');
            }
            $comment->setContent($content);
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            if ($course instanceof Course && $advancedSharing) {
                $recipientIds = \is_array($payload['recipientIds'] ?? null) ? $payload['recipientIds'] : [];
                $this->applyPortfolioVisibility(
                    $comment,
                    (int) ($payload['visibility'] ?? PortfolioComment::VISIBILITY_VISIBLE),
                    $recipientIds,
                    $course,
                    $session,
                    $this->entityManager,
                    $this->resourceLinkRepository,
                    true,
                );
            } else {
                $comment->setVisibility(PortfolioComment::VISIBILITY_VISIBLE);
            }

            $descriptions = \is_array($payload['attachmentDescriptions'] ?? null)
                ? \array_map('strval', $payload['attachmentDescriptions'])
                : [];
            $this->storePortfolioAttachments(
                $request,
                $comment,
                $this->commentRepository,
                $this->uploadFilenamePolicy,
                $descriptions,
            );
            $this->entityManager->flush();
            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            throw $exception;
        }

        if ($isUpdate) {
            $this->eventDispatcher->dispatch(
                new PortfolioCommentEditedEvent(['comment' => $comment]),
                Events::PORTFOLIO_COMMENT_EDITED,
            );
        } else {
            $this->eventDispatcher->dispatch(
                new PortfolioItemCommentedEvent(['comment' => $comment]),
                Events::PORTFOLIO_ITEM_COMMENTED,
            );
            $this->notifyPortfolioComment($comment);
        }

        $result = new PortfolioCommentForm();
        $result->id = (int) $comment->getId();
        $result->itemId = (int) $item->getId();

        return $result;
    }

    private function notifyPortfolioComment(PortfolioComment $comment): void
    {
        try {
            if (!\class_exists(PortfolioNotifier::class) && \function_exists('api_get_path') && \defined('SYS_CODE_PATH')) {
                $path = \api_get_path(SYS_CODE_PATH).'inc/lib/PortfolioNotifier.php';
                if (\is_file($path)) {
                    require_once $path;
                }
            }
            if (\class_exists(PortfolioNotifier::class)) {
                PortfolioNotifier::notifyTeachersAndAuthor($comment);
            }
        } catch (Throwable) {
            // Notification failures must not rollback a valid Portfolio comment.
        }
    }
}
