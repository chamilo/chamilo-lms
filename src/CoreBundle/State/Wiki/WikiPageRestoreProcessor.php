<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiPageHistory;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Entity\CWikiConf;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
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

/**
 * @implements ProcessorInterface<WikiPageHistory, WikiPageHistory>
 */
final readonly class WikiPageRestoreProcessor implements ProcessorInterface
{
    use WikiAccessHelperTrait;

    private const int LOCK_TIMEOUT_SECONDS = 1200;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CWikiRepository $wikiRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private WikiPageRenderer $renderer,
        private WikiNotificationService $notificationService,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): WikiPageHistory
    {
        if (!$data instanceof WikiPageHistory) {
            throw new BadRequestHttpException('The request payload is invalid.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getWikiCourse($this->entityManager, $request);
        $this->assertWikiToolEnabled($this->entityManager, $course);
        $nodeId = $this->assertWikiRouteNode($course, $request);
        $session = $this->getWikiSession($this->entityManager, $request);
        $this->assertWikiSessionBelongsToCourse($session, $course);
        $group = $this->getWikiGroup($this->entityManager, $request);
        $this->assertWikiGroupBelongsToContext($group, $course, $session);

        if ($this->isWikiStudentView($request)) {
            throw new AccessDeniedHttpException('Wiki versions cannot be restored in student view.');
        }

        $this->validateCsrfToken($data->csrfToken);

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('An authenticated user is required.');
        }

        $pageId = isset($uriVariables['pageId']) ? (int) $uriVariables['pageId'] : 0;
        $versionIid = (int) ($data->versionIid ?? 0);
        if ($pageId <= 0 || $versionIid <= 0) {
            throw new BadRequestHttpException('A valid Wiki page and version are required.');
        }

        $courseId = (int) $course->getId();
        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $groupId = null !== $group?->getIid() ? (int) $group->getIid() : 0;
        $latest = $this->wikiRepository->findLatestVersionInContext(
            $courseId,
            $pageId,
            $groupId,
            $sessionId,
        );

        if (!$latest instanceof CWiki) {
            throw new NotFoundHttpException('The requested Wiki page was not found in the current context.');
        }

        $selected = $this->wikiRepository->findPageVersionByIidInContext(
            $courseId,
            $pageId,
            $versionIid,
            $groupId,
            $sessionId,
        );

        if (!$selected instanceof CWiki) {
            throw new NotFoundHttpException('The requested Wiki version was not found in the current context.');
        }

        if ($selected->getIid() === $latest->getIid()) {
            throw new ConflictHttpException('The current Wiki version cannot be restored.');
        }

        $canManage = $this->canManageWikiContext(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
        );
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
            throw new AccessDeniedHttpException('You are not allowed to restore this Wiki page.');
        }

        $this->assertLockAvailable($latest, $user, $canManage);
        $this->assertAssignmentConstraints($latest, $selected, $courseId);

        $now = new DateTime('now', new DateTimeZone('UTC'));
        $newVersion = ((int) $latest->getVersion()) + 1;
        $wiki = new CWiki();
        $wiki
            ->setCId($courseId)
            ->setPageId($pageId)
            ->setReflink($selected->getReflink())
            ->setTitle($selected->getTitle())
            ->setContent($selected->getContent())
            ->setUserId(2 === $latest->getAssignment() ? $latest->getUserId() : (int) $user->getId())
            ->setGroupId($groupId)
            ->setDtime($now)
            ->setAddlock($latest->getAddlock())
            ->setEditlock($latest->getEditlock())
            ->setVisibility($latest->getVisibility())
            ->setAddlockDisc($latest->getAddlockDisc())
            ->setVisibilityDisc($latest->getVisibilityDisc())
            ->setRatinglockDisc($latest->getRatinglockDisc())
            ->setAssignment($latest->getAssignment())
            ->setComment('Restored from version: '.(int) $selected->getVersion())
            ->setProgress($selected->getProgress())
            ->setScore((int) $latest->getScore())
            ->setVersion($newVersion)
            ->setIsEditing(0)
            ->setTimeEdit(null)
            ->setHits((int) $latest->getHits())
            ->setLinksto($selected->getLinksto())
            ->setTag($latest->getTag())
            ->setUserIp((string) ($request->getClientIp() ?? ''))
            ->setSessionId($sessionId)
            ->setParent($course)
            ->addCourseLink($course, $session, $group)
        ;
        $wiki->setCreator($user);

        foreach ($selected->getCategories() as $category) {
            $wiki->addCategory($category);
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $this->entityManager->persist($wiki);
            $this->entityManager->flush();

            $language = $selected->getResourceNode()?->getLanguage();
            $wiki->getResourceNode()?->setLanguage($language);
            if (null !== $wiki->getResourceNode()) {
                $this->entityManager->persist($wiki->getResourceNode());
            }

            $latest
                ->setIsEditing(0)
                ->setTimeEdit(null)
            ;
            $this->entityManager->persist($latest);
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
            false,
        );

        $response = new WikiPageHistory();
        $response->pageId = $pageId;
        $response->courseId = $courseId;
        $response->sessionId = $sessionId > 0 ? $sessionId : null;
        $response->groupId = $groupId > 0 ? $groupId : null;
        $response->nodeId = $nodeId;
        $response->reflink = $wiki->getReflink();
        $response->title = $this->renderer->displayTitle($wiki->getReflink(), $wiki->getTitle());
        $response->currentIid = null !== $wiki->getIid() ? (int) $wiki->getIid() : null;
        $response->currentVersion = $newVersion;
        $response->restoredIid = $response->currentIid;
        $response->restoredVersion = $newVersion;
        $response->canRestore = true;

        return $response;
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(WikiPageHistoryProvider::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }

    private function assertLockAvailable(CWiki $wiki, User $user, bool $canManage): void
    {
        $lockOwnerId = $wiki->getIsEditing();
        if ($lockOwnerId <= 0 || $lockOwnerId === $user->getId() || $canManage || $this->isLockExpired($wiki)) {
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

    private function assertAssignmentConstraints(CWiki $latest, CWiki $selected, int $courseId): void
    {
        $configuration = $this->entityManager->getRepository(CWikiConf::class)->findOneBy([
            'cId' => $courseId,
            'pageId' => $latest->getPageId(),
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
            && (int) $latest->getVersion() >= $configuration->getMaxVersion()
        ) {
            throw new ConflictHttpException('The maximum number of Wiki versions has been reached.');
        }

        if (null !== $configuration->getMaxText()
            && $configuration->getMaxText() > 0
            && $this->renderer->wordCount($selected->getContent()) > $configuration->getMaxText()
        ) {
            throw new ConflictHttpException('The maximum number of Wiki words has been reached.');
        }
    }

    private function toTimestamp(?DateTimeInterface $value): ?int
    {
        return $value?->getTimestamp();
    }
}
