<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Portfolio;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Portfolio\PortfolioForm;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioCategory;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\PortfolioItemAddedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemEditedEvent;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\Node\PortfolioRepository;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CoreBundle\Security\Upload\UploadFilenamePolicy;
use Chamilo\CoreBundle\Settings\SettingsManager;
use CourseManager;
use Doctrine\ORM\EntityManagerInterface;
use MessageManager;
use SessionManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

/**
 * @implements ProcessorInterface<mixed, PortfolioForm>
 */
final readonly class PortfolioFormProcessor implements ProcessorInterface
{
    use PortfolioWriteHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private PortfolioRepository $portfolioRepository,
        private ExtraFieldValuesRepository $extraFieldValuesRepository,
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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PortfolioForm
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
            throw new AccessDeniedHttpException('You are not allowed to use Portfolio in this context.');
        }

        $advancedSharing = $course instanceof Course && $this->portfolioBoolean(
            $this->settingsManager->getSetting('platform.portfolio_advanced_sharing', true),
        );
        $showBasePosts = $session instanceof Session && $this->portfolioBoolean(
            $this->settingsManager->getSetting('platform.portfolio_show_base_course_post_in_sessions', true),
        );
        $canManage = $course instanceof Course
            && $this->canManagePortfolioCourse($this->security, $currentUser, $course, $session);

        $isUpdate = (int) ($uriVariables['id'] ?? 0) > 0;
        if ($isUpdate) {
            $item = $this->findPortfolioItem($this->entityManager, (int) ($uriVariables['id'] ?? 0));
            $this->assertPortfolioItemContext(
                $item,
                $currentUser,
                $course,
                $session,
                $showBasePosts,
                $advancedSharing,
                $canManage,
            );
            $this->assertPortfolioItemOwner($item, $currentUser);
        } else {
            if (!$this->canCreatePortfolioItem(
                $this->security,
                $currentUser,
                $currentUser,
                $course,
                $session,
            )) {
                throw new AccessDeniedHttpException('You are not allowed to create a portfolio item.');
            }

            $item = (new Portfolio())
                ->setCreator($currentUser)
                ->setParent($currentUser)
            ;
            if ($course instanceof Course) {
                $item->addCourseLink($course, $session);
            }
        }

        $title = (string) ($payload['title'] ?? '');
        $titleAsHtml = $this->portfolioBoolean(
            $this->settingsManager->getSetting('editor.save_titles_as_html', true),
        );
        $title = $titleAsHtml ? $this->sanitizePortfolioHtml($title) : trim(strip_tags($title));
        $content = $this->sanitizePortfolioHtml((string) ($payload['content'] ?? ''));
        if ('' === trim(strip_tags($title)) || '' === trim(strip_tags($content))) {
            throw new BadRequestHttpException('Portfolio title and content are required.');
        }

        $category = null;
        $categoryId = (int) ($payload['categoryId'] ?? 0);
        if ($categoryId > 0) {
            $category = $this->entityManager->getRepository(PortfolioCategory::class)->find($categoryId);
            if (!$category instanceof PortfolioCategory) {
                throw new BadRequestHttpException('The selected portfolio category is invalid.');
            }
            if (!$category->isVisible() && !$this->security->isGranted('ROLE_ADMIN')) {
                throw new AccessDeniedHttpException('The selected portfolio category is not available.');
            }
        }

        $item
            ->setTitle($title)
            ->setContent($content)
            ->setCategory($category)
        ;

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $this->entityManager->persist($item);
            $this->entityManager->flush();

            if ($course instanceof Course) {
                $visibility = (int) ($payload['visibility'] ?? Portfolio::VISIBILITY_VISIBLE);
                $recipientIds = \is_array($payload['recipientIds'] ?? null) ? $payload['recipientIds'] : [];
                if ($advancedSharing) {
                    $this->applyPortfolioVisibility(
                        $item,
                        $visibility,
                        $recipientIds,
                        $course,
                        $session,
                        $this->entityManager,
                        $this->resourceLinkRepository,
                    );
                } elseif (\in_array($visibility, [
                    Portfolio::VISIBILITY_HIDDEN,
                    Portfolio::VISIBILITY_VISIBLE,
                    Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER,
                ], true)) {
                    $item->setVisibility($visibility);
                } else {
                    throw new BadRequestHttpException('The requested portfolio visibility is invalid.');
                }

                $tagIds = \is_array($payload['tagIds'] ?? null) ? $payload['tagIds'] : [];
                $this->applyPortfolioTags($item, $tagIds, $course, $session, $this->entityManager);
            } else {
                $visibility = (int) ($payload['visibility'] ?? Portfolio::VISIBILITY_VISIBLE);
                if (!\in_array($visibility, [Portfolio::VISIBILITY_HIDDEN, Portfolio::VISIBILITY_VISIBLE], true)) {
                    throw new BadRequestHttpException('The requested personal Portfolio visibility is invalid.');
                }
                $item->setVisibility($visibility);
            }

            $this->applyPortfolioExtraFields(
                $item,
                $payload,
                $request,
                $this->entityManager,
                $this->extraFieldValuesRepository,
            );

            $descriptions = \is_array($payload['attachmentDescriptions'] ?? null)
                ? array_map('strval', $payload['attachmentDescriptions'])
                : [];
            $this->storePortfolioAttachments(
                $request,
                $item,
                $this->portfolioRepository,
                $this->uploadFilenamePolicy,
                $descriptions,
            );
            $this->entityManager->flush();
            $connection->commit();
        } catch (Throwable $throwable) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $this->entityManager->clear();

            throw $throwable;
        }

        if ($isUpdate) {
            $this->eventDispatcher->dispatch(
                new PortfolioItemEditedEvent(['portfolio' => $item]),
                Events::PORTFOLIO_ITEM_EDITED,
            );
        } else {
            $this->eventDispatcher->dispatch(
                new PortfolioItemAddedEvent(['portfolio' => $item]),
                Events::PORTFOLIO_ITEM_ADDED,
            );
            $this->notifyTeachersForNewPortfolioItem($item, $course, $session);
        }

        $result = new PortfolioForm();
        $result->id = (int) $item->getId();
        $result->mode = $course instanceof Course ? 'course' : 'personal';
        $result->courseId = $course?->getId();
        $result->sessionId = $session?->getId();
        $result->title = $item->getTitle();
        $result->content = $item->getContent();
        $result->categoryId = $item->getCategory()?->getId();
        $result->visibility = $item->getVisibility();
        $result->isNew = false;
        $result->canEdit = true;

        return $result;
    }

    private function notifyTeachersForNewPortfolioItem(
        Portfolio $item,
        ?Course $course,
        ?Session $session,
    ): void {
        if (!$course instanceof Course
            || !\function_exists('api_get_course_setting')
            || 1 !== (int) api_get_course_setting('email_alert_teachers_new_post', api_get_course_info($course->getCode()))
            || !class_exists(MessageManager::class)
        ) {
            return;
        }

        try {
            if ($session instanceof Session && class_exists(SessionManager::class)) {
                $recipientIds = array_values(SessionManager::getCoachesByCourseSession(
                    (int) $session->getId(),
                    (int) $course->getId(),
                ));
                $courseTitle = $course->getTitle().' ('.$session->getTitle().')';
            } elseif (class_exists(CourseManager::class)) {
                $recipientIds = array_keys(CourseManager::get_teacher_list_from_course_code($course->getCode()));
                $courseTitle = $course->getTitle();
            } else {
                return;
            }

            $path = '/resources/portfolio/'.(int) $course->getResourceNode()?->getId()
                .'/item/'.(int) $item->getId().'?cid='.(int) $course->getId()
                .($session instanceof Session ? '&sid='.(int) $session->getId() : '');
            $url = \function_exists('api_get_path') && \defined('WEB_PATH')
                ? rtrim((string) api_get_path(WEB_PATH), '/').$path
                : $path;
            $subject = \sprintf('[Portfolio] New post in course %s', $courseTitle);
            $content = \sprintf(
                'There is a new post by %s in the portfolio of course %s. <a href="%s">Open it</a>.',
                $item->getResourceNode()->getCreator()?->getFullName() ?? '',
                $courseTitle,
                $url,
            );
            $content .= '<br><br><strong>'.$this->sanitizePortfolioHtml($item->getTitle()).'</strong><br>'
                .$this->portfolioExcerpt($item->getContent());

            foreach ($recipientIds as $recipientId) {
                MessageManager::send_message_simple((int) $recipientId, $subject, $content, 0, false, false, false);
            }
        } catch (Throwable) {
            // Notification failures must not rollback a valid Portfolio write.
        }
    }
}
