<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiPageForm;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Entity\CWikiCategory;
use Chamilo\CourseBundle\Entity\CWikiConf;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Security as LegacySecurity;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

use const COURSEMANAGERLOWSECURITY;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * @implements ProcessorInterface<WikiPageForm, WikiPageForm>
 */
final readonly class WikiPageFormProcessor implements ProcessorInterface
{
    use WikiAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CWikiRepository $wikiRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private WikiPageRenderer $renderer,
        private WikiNotificationService $notificationService,
        private WikiAssignmentService $assignmentService,
        private WikiCategoryService $categoryService,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): WikiPageForm
    {
        if (!$data instanceof WikiPageForm) {
            throw new BadRequestHttpException('The request payload is invalid.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getWikiCourse($this->entityManager, $request);
        $this->assertWikiToolEnabled($this->entityManager, $course);
        $this->assertWikiRouteNode($course, $request);
        $session = $this->getWikiSession($this->entityManager, $request);
        $this->assertWikiSessionBelongsToCourse($session, $course);
        $group = $this->getWikiGroup($this->entityManager, $request);
        $this->assertWikiGroupBelongsToContext($group, $course, $session);

        if ($this->isWikiStudentView($request)) {
            throw new AccessDeniedHttpException('Wiki pages cannot be edited in student view.');
        }

        if (!$this->canReadWikiContext($this->security, $this->settingsManager, $course, $session, $group)) {
            throw new AccessDeniedHttpException('You are not allowed to edit Wiki pages in this context.');
        }

        $this->validateCsrfToken($data->csrfToken);

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('An authenticated user is required.');
        }

        $courseId = (int) $course->getId();
        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $groupId = null !== $group?->getIid() ? (int) $group->getIid() : 0;
        $canManage = $this->canManageWikiContext(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
        );
        $isUpdate = $operation instanceof Put;
        $latest = null;
        $templatePage = null;

        if ($isUpdate) {
            $pageId = isset($uriVariables['pageId']) ? (int) $uriVariables['pageId'] : 0;
            if ($pageId <= 0) {
                throw new BadRequestHttpException('A valid Wiki page id is required.');
            }

            $latest = $this->wikiRepository->findLatestVersionInContext(
                $courseId,
                $pageId,
                $groupId,
                $sessionId,
            );

            if (!$latest instanceof CWiki) {
                throw new NotFoundHttpException('The requested Wiki page was not found in the current context.');
            }

            $this->assertWikiPageVisible($this->security, $latest, $canManage);

            if (!$this->canEditWikiPage(
                $this->entityManager,
                $this->security,
                $this->settingsManager,
                $course,
                $session,
                $group,
                $latest,
            )) {
                throw new AccessDeniedHttpException('You are not allowed to edit this Wiki page.');
            }

            $this->assertVersionAndLock($latest, $data->baseVersion, $user, $canManage);
            $templatePage = $latest;
        }

        $title = $isUpdate && $latest instanceof CWiki
            ? $latest->getTitle()
            : $this->renderer->sanitizeTitle($data->title);

        if ('' === trim($title)) {
            throw new BadRequestHttpException('The title is required.');
        }

        if (mb_strlen($title) > 255) {
            throw new BadRequestHttpException('The title cannot exceed 255 characters.');
        }

        $reflink = $isUpdate && $latest instanceof CWiki
            ? $latest->getReflink()
            : $this->renderer->normalizeReflink('' !== trim($data->reflink) ? $data->reflink : $title);

        if (mb_strlen($reflink) > 255) {
            throw new BadRequestHttpException('The Wiki page reference cannot exceed 255 characters.');
        }

        if (!$isUpdate) {
            $addLock = $this->wikiRepository->findContextAddLock($courseId, $groupId, $sessionId);
            if (!$this->canCreateWikiPage(
                $this->entityManager,
                $this->security,
                $this->settingsManager,
                $course,
                $session,
                $group,
                $reflink,
                $addLock,
            )) {
                throw new AccessDeniedHttpException('You are not allowed to create Wiki pages in this context.');
            }

            if ($this->wikiRepository->reflinkExistsInContext($courseId, $reflink, $groupId, $sessionId)) {
                throw new ConflictHttpException('A Wiki page with the same title already exists.');
            }

            if ($sessionId > 0) {
                $firstTemplate = $this->wikiRepository->findFirstVersionInContext(
                    $courseId,
                    $reflink,
                    $groupId,
                    0,
                );

                if ($firstTemplate instanceof CWiki && null !== $firstTemplate->getPageId()) {
                    $templatePage = $this->wikiRepository->findLatestVersionInContext(
                        $courseId,
                        (int) $firstTemplate->getPageId(),
                        $groupId,
                        0,
                    );

                    if ($templatePage instanceof CWiki) {
                        $this->assertWikiPageVisible($this->security, $templatePage, $canManage);
                    }
                }
            }
        }

        $progress = $this->normalizeProgress($data->progress);
        $content = $this->sanitizeContent($data->content);
        $comment = trim(strip_tags($data->comment));
        $this->prepareAssignmentConfiguration($data, $canManage, $reflink);
        $categoriesEnabled = $this->isWikiCourseSettingEnabled(
            $this->entityManager,
            $course,
            'wiki_categories_enabled',
            false,
        );
        if (!$categoriesEnabled && [] !== $data->categoryIds) {
            throw new AccessDeniedHttpException('Wiki categories are disabled for this course.');
        }
        $categories = $categoriesEnabled
            ? $this->categoryService->resolveCategories($data->categoryIds, $course, $session)
            : [];

        if ($latest instanceof CWiki) {
            $this->assertAssignmentConstraints($latest, $courseId, $content);
        }

        if (!$isUpdate && $data->createAssignment) {
            if (!$canManage) {
                throw new AccessDeniedHttpException('Only Wiki managers can create an assignment workflow.');
            }

            if ('index' === $reflink) {
                throw new BadRequestHttpException('The Wiki homepage cannot be converted into an assignment workflow.');
            }

            return $this->createAssignment(
                $data,
                $course,
                $session,
                $group,
                $user,
                $title,
                $reflink,
                $content,
                $comment,
                $progress,
                (string) ($request->getClientIp() ?? ''),
                $canManage,
                $categories,
            );
        }

        $now = new DateTime('now', new DateTimeZone('UTC'));
        $version = $latest instanceof CWiki ? ((int) $latest->getVersion()) + 1 : 1;
        $pageId = $latest instanceof CWiki && null !== $latest->getPageId() ? (int) $latest->getPageId() : 0;
        $source = $latest instanceof CWiki ? $latest : $templatePage;
        $assignment = $source instanceof CWiki ? $source->getAssignment() : 0;
        $ownerId = 2 === $assignment && $source instanceof CWiki
            ? $source->getUserId()
            : (int) $user->getId();

        $wiki = new CWiki();
        $wiki
            ->setCId($courseId)
            ->setPageId($pageId)
            ->setReflink($reflink)
            ->setTitle($title)
            ->setContent($content)
            ->setUserId($ownerId)
            ->setGroupId($groupId)
            ->setDtime($now)
            ->setAddlock($source instanceof CWiki ? $source->getAddlock() : 1)
            ->setEditlock($source instanceof CWiki ? $source->getEditlock() : 0)
            ->setVisibility($source instanceof CWiki ? $source->getVisibility() : 1)
            ->setAddlockDisc($source instanceof CWiki ? $source->getAddlockDisc() : 1)
            ->setVisibilityDisc($source instanceof CWiki ? $source->getVisibilityDisc() : 1)
            ->setRatinglockDisc($source instanceof CWiki ? $source->getRatinglockDisc() : 1)
            ->setAssignment($assignment)
            ->setComment($comment)
            ->setProgress((string) ($progress / 10))
            ->setScore($source instanceof CWiki ? (int) $source->getScore() : 0)
            ->setVersion($version)
            ->setIsEditing(0)
            ->setTimeEdit(null)
            ->setHits($source instanceof CWiki ? (int) $source->getHits() : 0)
            ->setLinksto($this->renderer->serializeInternalReflinks($content))
            ->setTag('')
            ->setUserIp((string) ($request->getClientIp() ?? ''))
            ->setSessionId($sessionId)
            ->setParent($course)
            ->addCourseLink($course, $session, $group)
        ;
        $wiki->setCreator($user);

        $this->categoryService->applyCategories($wiki, $categories);

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $this->entityManager->persist($wiki);
            $this->entityManager->flush();

            if (!$isUpdate) {
                $wiki->setPageId((int) $wiki->getIid());
            }

            if ($latest instanceof CWiki) {
                $latest
                    ->setIsEditing(0)
                    ->setTimeEdit(null)
                ;
                $this->entityManager->persist($latest);
            }

            $languageCode = $canManage && 'index' !== $reflink
                ? trim($data->language)
                : $this->getResourceLanguage($source);
            $this->applyResourceLanguage($wiki, $languageCode);

            if ($canManage && 'index' !== $reflink) {
                $this->assignmentService->saveConfiguration($data, $wiki, $courseId);
            }

            $this->entityManager->flush();
            $connection->commit();
        } catch (Throwable $throwable) {
            $connection->rollBack();

            throw $throwable;
        }

        $this->notificationService->notifyPageSaved(
            $wiki,
            $course,
            $session,
            $group,
            $user,
            !$isUpdate,
        );

        return $this->createResponse($wiki, $progress, $canManage, $data);
    }

    private function createAssignment(
        WikiPageForm $data,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $teacher,
        string $title,
        string $reflink,
        string $content,
        string $comment,
        int $progress,
        string $clientIp,
        bool $canManage,
        array $categories,
    ): WikiPageForm {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $result = $this->assignmentService->createAssignmentPages(
                $data,
                $course,
                $session,
                $group,
                $teacher,
                $title,
                $reflink,
                $content,
                $comment,
                $progress,
                $clientIp,
                $categories,
            );
            $connection->commit();
        } catch (Throwable $throwable) {
            $connection->rollBack();

            throw $throwable;
        }

        foreach ($result['createdPages'] as $createdPage) {
            $this->notificationService->notifyPageSaved(
                $createdPage,
                $course,
                $session,
                $group,
                $teacher,
                true,
            );
        }

        return $this->createResponse($result['teacherPage'], $progress, $canManage, $data);
    }

    private function createResponse(
        CWiki $wiki,
        int $progress,
        bool $canManage,
        WikiPageForm $source,
    ): WikiPageForm {
        $response = new WikiPageForm();
        $response->iid = null !== $wiki->getIid() ? (int) $wiki->getIid() : null;
        $response->pageId = null !== $wiki->getPageId() ? (int) $wiki->getPageId() : null;
        $response->reflink = $wiki->getReflink();
        $response->title = $wiki->getTitle();
        $response->content = $wiki->getContent();
        $response->comment = $wiki->getComment();
        $response->progress = $progress;
        $response->language = $this->getResourceLanguage($wiki);
        $response->baseVersion = (int) $wiki->getVersion();
        $response->version = (int) $wiki->getVersion();
        $response->assignment = $wiki->getAssignment();
        $response->createAssignment = false;
        $response->isNew = false;
        $response->canManage = $canManage;
        $response->task = $source->task;
        $response->feedback1 = $source->feedback1;
        $response->feedback2 = $source->feedback2;
        $response->feedback3 = $source->feedback3;
        $response->feedbackProgress1 = $source->feedbackProgress1;
        $response->feedbackProgress2 = $source->feedbackProgress2;
        $response->feedbackProgress3 = $source->feedbackProgress3;
        $response->startDate = $source->startDate;
        $response->endDate = $source->endDate;
        $response->delayedSubmit = $source->delayedSubmit;
        $response->maxWords = $source->maxWords;
        $response->maxVersions = $source->maxVersions;
        $response->categoriesEnabled = $source->categoriesEnabled;
        $response->categories = $source->categories;
        $response->categoryIds = array_values(array_map(
            static fn (CWikiCategory $category): int => (int) $category->getId(),
            $wiki->getCategories()->toArray(),
        ));

        return $response;
    }

    private function prepareAssignmentConfiguration(WikiPageForm $data, bool $canManage, string $reflink): void
    {
        if (!$canManage || 'index' === $reflink) {
            $data->createAssignment = false;
            $data->task = '';
            $data->feedback1 = '';
            $data->feedback2 = '';
            $data->feedback3 = '';
            $data->feedbackProgress1 = 0;
            $data->feedbackProgress2 = 0;
            $data->feedbackProgress3 = 0;
            $data->startDate = null;
            $data->endDate = null;
            $data->delayedSubmit = false;
            $data->maxWords = 0;
            $data->maxVersions = 0;

            return;
        }

        $data->task = $this->sanitizeOptionalContent($data->task);
        $data->feedback1 = trim(strip_tags($data->feedback1));
        $data->feedback2 = trim(strip_tags($data->feedback2));
        $data->feedback3 = trim(strip_tags($data->feedback3));
        $this->assignmentService->validateConfiguration($data);
    }

    private function assertAssignmentConstraints(CWiki $wiki, int $courseId, string $content): void
    {
        $pageId = $wiki->getPageId();
        if (null === $pageId) {
            return;
        }

        $configuration = $this->entityManager->getRepository(CWikiConf::class)->findOneBy([
            'cId' => $courseId,
            'pageId' => $pageId,
        ]);

        if (!$configuration instanceof CWikiConf) {
            return;
        }

        $now = time();
        $start = $this->toTimestamp($configuration->getStartdateAssig());
        if (null !== $start && $now < $start) {
            throw new AccessDeniedHttpException('The Wiki assignment has not started yet.');
        }

        $end = $this->toTimestamp($configuration->getEnddateAssig());
        if (null !== $end && $now > $end && 0 === $configuration->getDelayedsubmit()) {
            throw new AccessDeniedHttpException('The Wiki assignment deadline has passed.');
        }

        if (null !== $configuration->getMaxVersion()
            && $configuration->getMaxVersion() > 0
            && (int) $wiki->getVersion() >= $configuration->getMaxVersion()
        ) {
            throw new ConflictHttpException('The maximum number of Wiki versions has been reached.');
        }

        if (null !== $configuration->getMaxText()
            && $configuration->getMaxText() > 0
            && $this->renderer->wordCount($content) > $configuration->getMaxText()
        ) {
            throw new ConflictHttpException('The maximum number of Wiki words has been reached.');
        }
    }

    private function assertVersionAndLock(CWiki $wiki, int $baseVersion, User $user, bool $canManage): void
    {
        $currentVersion = (int) $wiki->getVersion();
        if ($baseVersion <= 0 || $baseVersion !== $currentVersion) {
            throw new ConflictHttpException('This Wiki page was modified by another user. Reload the editor before saving.');
        }

        if ($canManage) {
            return;
        }

        if ($wiki->getIsEditing() !== $user->getId()) {
            throw new ConflictHttpException('The Wiki page edition lock is not owned by the current user.');
        }
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(WikiPageFormProvider::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }

    private function normalizeProgress(int $progress): int
    {
        if ($progress < 0 || $progress > 100 || 0 !== $progress % 10) {
            throw new BadRequestHttpException('The Wiki progress value is invalid.');
        }

        return $progress;
    }

    private function sanitizeOptionalContent(string $content): string
    {
        return '' === trim($content) ? '' : $this->sanitizeContent($content);
    }

    private function sanitizeContent(string $content): string
    {
        if ('' === trim($content)) {
            $content = '<p>&nbsp;</p>';
        }

        if (!class_exists(LegacySecurity::class)) {
            return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        if ($this->resolveWikiBoolean($this->settingsManager->getSetting('editor.htmlpurifier_wiki', true), false)) {
            return (string) LegacySecurity::remove_XSS($content);
        }

        if (\defined('COURSEMANAGERLOWSECURITY')) {
            return (string) LegacySecurity::remove_XSS($content, COURSEMANAGERLOWSECURITY);
        }

        return (string) LegacySecurity::remove_XSS($content);
    }

    private function applyResourceLanguage(CWiki $wiki, string $languageCode): void
    {
        $resourceNode = $wiki->getResourceNode();
        if (null === $resourceNode) {
            return;
        }

        $language = null;
        if ('' !== $languageCode) {
            $language = $this->entityManager->getRepository(Language::class)->findOneBy([
                'isocode' => $languageCode,
                'available' => true,
            ]);

            if (!$language instanceof Language) {
                throw new BadRequestHttpException('The selected language is invalid.');
            }
        }

        $resourceNode->setLanguage($language);
        $this->entityManager->persist($resourceNode);
    }

    private function getResourceLanguage(?CWiki $wiki): string
    {
        $language = $wiki?->getResourceNode()?->getLanguage();

        return null !== $language ? (string) $language->getIsocode() : '';
    }

    private function toTimestamp(?DateTimeInterface $value): ?int
    {
        return $value?->getTimestamp();
    }
}
