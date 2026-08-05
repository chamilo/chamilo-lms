<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiPageAction;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Entity\CWikiConf;
use Chamilo\CourseBundle\Entity\CWikiDiscuss;
use Chamilo\CourseBundle\Entity\CWikiMailcue;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

/**
 * @implements ProcessorInterface<WikiPageAction, WikiPageAction>
 */
final readonly class WikiPageActionProcessor implements ProcessorInterface
{
    use WikiAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CWikiRepository $wikiRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private WikiNotificationService $notificationService,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): WikiPageAction
    {
        if (!$data instanceof WikiPageAction) {
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
            throw new AccessDeniedHttpException('Wiki management actions are not available in student view.');
        }

        if (!$this->canManageWikiContext(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to manage Wiki pages in this context.');
        }

        $this->validateCsrfToken($data->csrfToken);

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('An authenticated user is required.');
        }

        $courseId = (int) $course->getId();
        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $groupId = null !== $group?->getIid() ? (int) $group->getIid() : 0;
        $operationName = (string) $operation->getName();

        return match ($operationName) {
            WikiPageAction::OPERATION_VISIBILITY => $this->changePageVisibility(
                $data,
                $courseId,
                $sessionId,
                $groupId,
                $uriVariables,
            ),
            WikiPageAction::OPERATION_PROTECTION => $this->changePageProtection(
                $data,
                $courseId,
                $sessionId,
                $groupId,
                $uriVariables,
            ),
            WikiPageAction::OPERATION_SUBSCRIPTION => $this->changePageSubscription(
                $data,
                $user,
                $courseId,
                $sessionId,
                $groupId,
                $uriVariables,
            ),
            WikiPageAction::OPERATION_DELETE_PAGE => $this->deletePage(
                $course,
                $session,
                $group,
                $user,
                $courseId,
                $sessionId,
                $groupId,
                $uriVariables,
            ),
            WikiPageAction::OPERATION_ADD_LOCK => $this->changeContextAddLock(
                $data,
                $courseId,
                $sessionId,
                $groupId,
            ),
            WikiPageAction::OPERATION_CONTEXT_SUBSCRIPTION => $this->changeContextSubscription(
                $data,
                $user,
                $courseId,
                $sessionId,
                $groupId,
            ),
            WikiPageAction::OPERATION_DELETE_CONTEXT => $this->deleteContext(
                $courseId,
                $sessionId,
                $groupId,
            ),
            default => throw new BadRequestHttpException('The requested Wiki action is not supported.'),
        };
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    private function changePageVisibility(
        WikiPageAction $data,
        int $courseId,
        int $sessionId,
        int $groupId,
        array $uriVariables,
    ): WikiPageAction {
        $latest = $this->getExactPage($courseId, $sessionId, $groupId, $uriVariables);
        $versions = $this->wikiRepository->findPageVersionsInContext(
            $courseId,
            (int) $latest->getPageId(),
            $groupId,
            $sessionId,
        );

        foreach ($versions as $version) {
            $version->setVisibility($data->enabled ? 1 : 0);
        }

        $this->entityManager->flush();

        return $this->buildPageResult($latest, $courseId, $sessionId, $groupId);
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    private function changePageProtection(
        WikiPageAction $data,
        int $courseId,
        int $sessionId,
        int $groupId,
        array $uriVariables,
    ): WikiPageAction {
        $latest = $this->getExactPage($courseId, $sessionId, $groupId, $uriVariables);
        $versions = $this->wikiRepository->findPageVersionsInContext(
            $courseId,
            (int) $latest->getPageId(),
            $groupId,
            $sessionId,
        );

        foreach ($versions as $version) {
            $version->setEditlock($data->enabled ? 1 : 0);
        }

        $this->entityManager->flush();

        return $this->buildPageResult($latest, $courseId, $sessionId, $groupId);
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    private function changePageSubscription(
        WikiPageAction $data,
        User $user,
        int $courseId,
        int $sessionId,
        int $groupId,
        array $uriVariables,
    ): WikiPageAction {
        $pageId = $this->getPageId($uriVariables);
        $sourceSessionId = $sessionId;
        $latest = $this->wikiRepository->findLatestVersionInContext(
            $courseId,
            $pageId,
            $groupId,
            $sourceSessionId,
        );

        if (!$latest instanceof CWiki && $sessionId > 0) {
            $sourceSessionId = 0;
            $latest = $this->wikiRepository->findLatestVersionInContext(
                $courseId,
                $pageId,
                $groupId,
                $sourceSessionId,
            );
        }

        if (!$latest instanceof CWiki) {
            throw new NotFoundHttpException('The requested Wiki page was not found in the current context.');
        }

        $type = 'watch:'.$latest->getReflink();
        $subscription = $this->findMailCue(
            $courseId,
            $sessionId,
            $groupId,
            (int) $user->getId(),
            $type,
        );

        if ($data->enabled && !$subscription instanceof CWikiMailcue) {
            $subscription = (new CWikiMailcue())
                ->setCId($courseId)
                ->setGroupId($groupId)
                ->setSessionId($sessionId)
                ->setUserId((int) $user->getId())
                ->setType($type)
            ;
            $this->entityManager->persist($subscription);
        }

        if (!$data->enabled && $subscription instanceof CWikiMailcue) {
            $this->entityManager->remove($subscription);
        }

        $this->entityManager->flush();

        $result = $this->buildPageResult($latest, $courseId, $sessionId, $groupId);
        $result->subscribed = $data->enabled;

        return $result;
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    private function deletePage(
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $user,
        int $courseId,
        int $sessionId,
        int $groupId,
        array $uriVariables,
    ): WikiPageAction {
        $latest = $this->getExactPage($courseId, $sessionId, $groupId, $uriVariables);
        $pageId = (int) $latest->getPageId();
        $reflink = $latest->getReflink();
        $title = $latest->getTitle();
        $versions = $this->wikiRepository->findPageVersionsInContext(
            $courseId,
            $pageId,
            $groupId,
            $sessionId,
        );
        $watchers = $this->getWatcherUserIds($courseId, $sessionId, $groupId, 'watch:'.$reflink);
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $this->removePageDependencies($courseId, [$pageId]);
            $this->removeMailCues($courseId, $sessionId, $groupId, 'watch:'.$reflink);
            $this->removeMailCues($courseId, $sessionId, $groupId, 'watchdisc:'.$reflink);
            $this->removeWikiVersions($versions);
            $this->entityManager->flush();
            $connection->commit();
        } catch (Throwable $throwable) {
            $connection->rollBack();

            throw $throwable;
        }

        $this->notificationService->notifyPageDeleted(
            $course,
            $session,
            $group,
            $user,
            $title,
            $watchers,
        );

        $result = new WikiPageAction();
        $result->pageId = $pageId;
        $result->reflink = $reflink;
        $result->deleted = true;
        $result->deletedVersions = \count($versions);

        return $result;
    }

    private function changeContextAddLock(
        WikiPageAction $data,
        int $courseId,
        int $sessionId,
        int $groupId,
    ): WikiPageAction {
        $versions = $this->wikiRepository->findVersionsInContext($courseId, $groupId, $sessionId, true);
        if ([] === $versions) {
            throw new NotFoundHttpException('The current Wiki context does not contain any pages.');
        }

        foreach ($versions as $version) {
            $version->setAddlock($data->enabled ? 0 : 1);
        }

        $this->entityManager->flush();

        $result = new WikiPageAction();
        $result->addLocked = $data->enabled;

        return $result;
    }

    private function changeContextSubscription(
        WikiPageAction $data,
        User $user,
        int $courseId,
        int $sessionId,
        int $groupId,
    ): WikiPageAction {
        $subscription = $this->findMailCue(
            $courseId,
            $sessionId,
            $groupId,
            (int) $user->getId(),
            'wiki',
        );

        if ($data->enabled && !$subscription instanceof CWikiMailcue) {
            $subscription = (new CWikiMailcue())
                ->setCId($courseId)
                ->setGroupId($groupId)
                ->setSessionId($sessionId)
                ->setUserId((int) $user->getId())
                ->setType('wiki')
            ;
            $this->entityManager->persist($subscription);
        }

        if (!$data->enabled && $subscription instanceof CWikiMailcue) {
            $this->entityManager->remove($subscription);
        }

        $this->entityManager->flush();

        $result = new WikiPageAction();
        $result->subscribed = $data->enabled;

        return $result;
    }

    private function deleteContext(int $courseId, int $sessionId, int $groupId): WikiPageAction
    {
        $versions = $this->wikiRepository->findVersionsInContext($courseId, $groupId, $sessionId, true);
        if ([] === $versions) {
            throw new NotFoundHttpException('The current Wiki context does not contain any pages.');
        }

        $pageIds = array_values(array_unique(array_map(
            static fn (CWiki $wiki): int => (int) $wiki->getPageId(),
            $versions,
        )));
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $this->removePageDependencies($courseId, $pageIds);
            $this->removeMailCues($courseId, $sessionId, $groupId);
            $this->removeWikiVersions($versions);
            $this->entityManager->flush();
            $connection->commit();
        } catch (Throwable $throwable) {
            $connection->rollBack();

            throw $throwable;
        }

        $result = new WikiPageAction();
        $result->contextDeleted = true;
        $result->deletedVersions = \count($versions);

        return $result;
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    private function getExactPage(
        int $courseId,
        int $sessionId,
        int $groupId,
        array $uriVariables,
    ): CWiki {
        $pageId = $this->getPageId($uriVariables);
        $latest = $this->wikiRepository->findLatestVersionInContext(
            $courseId,
            $pageId,
            $groupId,
            $sessionId,
        );

        if (!$latest instanceof CWiki) {
            throw new NotFoundHttpException('The requested Wiki page was not found in the current context.');
        }

        return $latest;
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    private function getPageId(array $uriVariables): int
    {
        $pageId = isset($uriVariables['pageId']) ? (int) $uriVariables['pageId'] : 0;
        if ($pageId <= 0) {
            throw new BadRequestHttpException('A valid Wiki page id is required.');
        }

        return $pageId;
    }

    private function buildPageResult(CWiki $wiki, int $courseId, int $sessionId, int $groupId): WikiPageAction
    {
        $result = new WikiPageAction();
        $result->pageId = $wiki->getPageId();
        $result->reflink = $wiki->getReflink();
        $result->visible = 1 === $wiki->getVisibility();
        $result->editLocked = 1 === $wiki->getEditlock();
        $result->addLocked = 0 === $this->wikiRepository->findContextAddLock($courseId, $groupId, $sessionId);

        return $result;
    }

    /**
     * @param array<int, int> $pageIds
     */
    private function removePageDependencies(int $courseId, array $pageIds): void
    {
        foreach ($pageIds as $pageId) {
            $configurations = $this->entityManager->getRepository(CWikiConf::class)->findBy([
                'cId' => $courseId,
                'pageId' => $pageId,
            ]);
            foreach ($configurations as $configuration) {
                if ($configuration instanceof CWikiConf) {
                    $this->entityManager->remove($configuration);
                }
            }

            $discussions = $this->entityManager->getRepository(CWikiDiscuss::class)->findBy([
                'cId' => $courseId,
                'publicationId' => $pageId,
            ]);
            foreach ($discussions as $discussion) {
                if ($discussion instanceof CWikiDiscuss) {
                    $this->entityManager->remove($discussion);
                }
            }
        }
    }

    /**
     * @param array<int, CWiki> $versions
     */
    private function removeWikiVersions(array $versions): void
    {
        foreach ($versions as $version) {
            $resourceNode = $version->getResourceNode();
            if (null !== $resourceNode) {
                $this->entityManager->remove($resourceNode);

                continue;
            }

            $this->entityManager->remove($version);
        }
    }

    private function removeMailCues(
        int $courseId,
        int $sessionId,
        int $groupId,
        ?string $type = null,
    ): void {
        $queryBuilder = $this->entityManager->getRepository(CWikiMailcue::class)->createQueryBuilder('m')
            ->andWhere('m.cId = :courseId')
            ->andWhere('COALESCE(m.groupId, 0) = :groupId')
            ->andWhere('COALESCE(m.sessionId, 0) = :sessionId')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('groupId', $groupId, Types::INTEGER)
            ->setParameter('sessionId', $sessionId, Types::INTEGER)
        ;

        if (null !== $type) {
            $queryBuilder
                ->andWhere('m.type = :type')
                ->setParameter('type', $type, Types::STRING)
            ;
        }

        $mailCues = $queryBuilder->getQuery()->getResult();
        foreach ($mailCues as $mailCue) {
            if ($mailCue instanceof CWikiMailcue) {
                $this->entityManager->remove($mailCue);
            }
        }
    }

    /**
     * @return array<int, int>
     */
    private function getWatcherUserIds(int $courseId, int $sessionId, int $groupId, string $type): array
    {
        $mailCues = $this->entityManager->getRepository(CWikiMailcue::class)->createQueryBuilder('m')
            ->andWhere('m.cId = :courseId')
            ->andWhere('COALESCE(m.groupId, 0) = :groupId')
            ->andWhere('COALESCE(m.sessionId, 0) = :sessionId')
            ->andWhere('m.type = :type')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('groupId', $groupId, Types::INTEGER)
            ->setParameter('sessionId', $sessionId, Types::INTEGER)
            ->setParameter('type', $type, Types::STRING)
            ->getQuery()
            ->getResult()
        ;
        $userIds = [];

        foreach ($mailCues as $mailCue) {
            if (!$mailCue instanceof CWikiMailcue || $mailCue->getUserId() <= 0) {
                continue;
            }

            $userIds[] = $mailCue->getUserId();
        }

        return array_values(array_unique($userIds));
    }

    private function findMailCue(
        int $courseId,
        int $sessionId,
        int $groupId,
        int $userId,
        string $type,
    ): ?CWikiMailcue {
        $mailCue = $this->entityManager->getRepository(CWikiMailcue::class)->createQueryBuilder('m')
            ->andWhere('m.cId = :courseId')
            ->andWhere('COALESCE(m.groupId, 0) = :groupId')
            ->andWhere('COALESCE(m.sessionId, 0) = :sessionId')
            ->andWhere('m.userId = :userId')
            ->andWhere('m.type = :type')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('groupId', $groupId, Types::INTEGER)
            ->setParameter('sessionId', $sessionId, Types::INTEGER)
            ->setParameter('userId', $userId, Types::INTEGER)
            ->setParameter('type', $type, Types::STRING)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $mailCue instanceof CWikiMailcue ? $mailCue : null;
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(WikiPageAction::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }
}
