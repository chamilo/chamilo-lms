<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiPageForm;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Entity\CWikiConf;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<WikiPageForm>
 */
final readonly class WikiPageFormProvider implements ProviderInterface
{
    use WikiAccessHelperTrait;

    public const CSRF_TOKEN_ID = 'wiki_page_form';

    private const LOCK_TIMEOUT_SECONDS = 1200;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CWikiRepository $wikiRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private WikiPageRenderer $renderer,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): WikiPageForm
    {
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

        $courseId = (int) $course->getId();
        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $groupId = null !== $group?->getIid() ? (int) $group->getIid() : 0;
        $requestedPageId = $request->query->getInt('pageId');
        $requestedTitle = trim((string) $request->query->get('title', ''));
        $reflink = '' !== $requestedTitle ? $this->renderer->normalizeReflink($requestedTitle) : '';

        $exactPage = null;
        if ($requestedPageId > 0) {
            $exactPage = $this->wikiRepository->findLatestVersionInContext(
                $courseId,
                $requestedPageId,
                $groupId,
                $sessionId,
            );
        } elseif ('' !== $reflink) {
            $first = $this->wikiRepository->findFirstVersionInContext(
                $courseId,
                $reflink,
                $groupId,
                $sessionId,
            );

            if ($first instanceof CWiki && null !== $first->getPageId()) {
                $exactPage = $this->wikiRepository->findLatestVersionInContext(
                    $courseId,
                    (int) $first->getPageId(),
                    $groupId,
                    $sessionId,
                );
            }
        }

        $sourcePage = $exactPage;
        $isInheritedFromCourse = false;

        if (!$sourcePage instanceof CWiki && $sessionId > 0 && '' !== $reflink) {
            $first = $this->wikiRepository->findFirstVersionInContext(
                $courseId,
                $reflink,
                $groupId,
                0,
            );

            if ($first instanceof CWiki && null !== $first->getPageId()) {
                $sourcePage = $this->wikiRepository->findLatestVersionInContext(
                    $courseId,
                    (int) $first->getPageId(),
                    $groupId,
                    0,
                );
                $isInheritedFromCourse = $sourcePage instanceof CWiki;
            }
        }

        if ($requestedPageId > 0 && !$exactPage instanceof CWiki) {
            throw new AccessDeniedHttpException('The requested Wiki page does not belong to the current course context.');
        }

        if ($sourcePage instanceof CWiki) {
            $reflink = $sourcePage->getReflink();
        }

        $canManage = $this->canManageWikiContext(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
        );
        $addLock = $this->wikiRepository->findContextAddLock($courseId, $groupId, $sessionId);
        $canCreate = $this->canCreateWikiPage(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
            '' !== $reflink ? $reflink : 'new_page',
            $addLock,
        );
        if ($sourcePage instanceof CWiki) {
            $this->assertWikiPageVisible($this->security, $sourcePage, $canManage);
        }

        $canEdit = $exactPage instanceof CWiki
            ? $this->canEditWikiPage(
                $this->entityManager,
                $this->security,
                $this->settingsManager,
                $course,
                $session,
                $group,
                $exactPage,
            )
            : $canCreate;

        if (!$canEdit) {
            throw new AccessDeniedHttpException('You are not allowed to edit this Wiki page.');
        }

        if ($exactPage instanceof CWiki) {
            $this->assertAssignmentConstraints($exactPage, $courseId);
            $this->assertEditLockAvailable($exactPage, $canManage);
        }

        $strictHtmlFiltering = $this->isWikiCourseSettingEnabled(
            $this->entityManager,
            $course,
            'wiki_html_strict_filtering',
            false,
        );

        $form = new WikiPageForm();
        $form->csrfToken = (string) $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID);
        $form->isNew = !$exactPage instanceof CWiki;
        $form->isInheritedFromCourse = $isInheritedFromCourse;
        $form->canManage = $canManage;
        $form->requiresLock = $exactPage instanceof CWiki;
        $form->progressOptions = $this->getProgressOptions();
        $form->settings = [
            'forcePasteAsPlainText' => $this->isPlatformSettingEnabled('editor.force_wiki_paste_as_plain_text'),
            'htmlPurifierEnabled' => $this->isPlatformSettingEnabled('editor.htmlpurifier_wiki'),
            'strictHtmlFiltering' => $strictHtmlFiltering,
        ];

        if ($sourcePage instanceof CWiki) {
            $form->iid = null !== $sourcePage->getIid() ? (int) $sourcePage->getIid() : null;
            $form->pageId = $exactPage instanceof CWiki && null !== $exactPage->getPageId()
                ? (int) $exactPage->getPageId()
                : null;
            $form->reflink = $sourcePage->getReflink();
            $form->title = $this->renderer->displayTitle($sourcePage->getReflink(), $sourcePage->getTitle());
            $form->content = $this->renderer->sanitizeContent(
                $sourcePage->getContent(),
                $strictHtmlFiltering,
            );
            $form->progress = $this->renderer->normalizeStoredProgress($sourcePage->getProgress());
            $form->language = $this->getResourceLanguage($sourcePage);
            $form->version = (int) ($exactPage?->getVersion() ?? 0);
            $form->baseVersion = $form->version;
            $form->assignment = $sourcePage->getAssignment();
        } else {
            $form->reflink = $reflink;
            $form->title = '' === $reflink
                ? ''
                : ('index' === $reflink ? $this->renderer->displayTitle('index') : str_replace('_', ' ', $reflink));
            $form->content = '<p>&nbsp;</p>';
        }

        if ($canManage && 'index' !== $form->reflink) {
            $form->languages = $this->getLanguages();
        }

        return $form;
    }

    private function assertAssignmentConstraints(CWiki $wiki, int $courseId): void
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
            && $this->renderer->wordCount($wiki->getContent()) >= $configuration->getMaxText()
        ) {
            throw new ConflictHttpException('The maximum number of Wiki words has been reached.');
        }
    }

    private function assertEditLockAvailable(CWiki $wiki, bool $canManage): void
    {
        $lockOwnerId = $wiki->getIsEditing();
        if ($lockOwnerId <= 0) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || $user->getId() === $lockOwnerId || $canManage || $this->isLockExpired($wiki)) {
            return;
        }

        $lockOwner = $this->entityManager->getRepository(User::class)->find($lockOwnerId);
        $lockOwnerName = $lockOwner instanceof User ? $lockOwner->getFullName() : '';

        throw new ConflictHttpException('' !== $lockOwnerName ? 'This Wiki page is currently being edited by '.$lockOwnerName.'.' : 'This Wiki page is currently being edited by another user.');
    }

    private function isLockExpired(CWiki $wiki): bool
    {
        $timeEdit = $wiki->getTimeEdit();
        if (!$timeEdit instanceof DateTimeInterface) {
            return true;
        }

        return time() - $timeEdit->getTimestamp() >= self::LOCK_TIMEOUT_SECONDS;
    }

    /**
     * @return array<int, array{value:string, label:string}>
     */
    private function getLanguages(): array
    {
        $languages = [
            [
                'value' => '',
                'label' => 'No specific language',
            ],
        ];

        $availableLanguages = $this->entityManager
            ->getRepository(Language::class)
            ->findBy(['available' => true], ['englishName' => 'ASC'])
        ;

        foreach ($availableLanguages as $language) {
            if (!$language instanceof Language) {
                continue;
            }

            $label = $language->getOriginalName() ?: $language->getEnglishName();
            $languages[] = [
                'value' => $language->getIsocode(),
                'label' => $label ?: $language->getIsocode(),
            ];
        }

        return $languages;
    }

    /**
     * @return array<int, array{value:int, label:string}>
     */
    private function getProgressOptions(): array
    {
        $options = [
            [
                'value' => 0,
                'label' => '',
            ],
        ];

        for ($progress = 10; $progress <= 100; $progress += 10) {
            $options[] = [
                'value' => $progress,
                'label' => $progress.'%',
            ];
        }

        return $options;
    }

    private function getResourceLanguage(CWiki $wiki): string
    {
        $language = $wiki->getResourceNode()?->getLanguage();

        return null !== $language ? (string) $language->getIsocode() : '';
    }

    private function isPlatformSettingEnabled(string $name): bool
    {
        return $this->resolveWikiBoolean($this->settingsManager->getSetting($name, true), false);
    }

    private function toTimestamp(?DateTimeInterface $value): ?int
    {
        return $value?->getTimestamp();
    }
}
