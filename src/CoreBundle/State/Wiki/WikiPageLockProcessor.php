<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiPageForm;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Helpers\WikiHelper;
use Chamilo\CourseBundle\Entity\CWiki;
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

/**
 * @implements ProcessorInterface<WikiPageForm, WikiPageForm>
 */
final readonly class WikiPageLockProcessor implements ProcessorInterface
{
    private const int LOCK_TIMEOUT_SECONDS = 1200;

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private StudentViewHelper $studentViewHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CWikiRepository $wikiRepository,
        private Security $security,
        private WikiHelper $wikiHelper,
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

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $this->wikiHelper->assertToolEnabled($course);
        $this->wikiHelper->assertRouteNode($course, $request);
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $this->wikiHelper->assertSessionBelongsToCourse($session, $course);
        $group = $this->cidReqHelper->getDoctrineGroupEntity();
        $this->wikiHelper->assertGroupBelongsToContext($group, $course, $session);

        if ($this->studentViewHelper->isActive()) {
            throw new AccessDeniedHttpException('Wiki pages cannot be edited in student view.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('An authenticated user is required.');
        }

        $pageId = isset($uriVariables['pageId']) ? (int) $uriVariables['pageId'] : 0;
        if ($pageId <= 0) {
            throw new BadRequestHttpException('A valid Wiki page id is required.');
        }

        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $groupId = null !== $group?->getIid() ? (int) $group->getIid() : 0;
        $wiki = $this->wikiRepository->findLatestVersionInContext(
            (int) $course->getId(),
            $pageId,
            $groupId,
            $sessionId,
        );

        if (!$wiki instanceof CWiki) {
            throw new NotFoundHttpException('The requested Wiki page was not found in the current context.');
        }

        $canManage = $this->wikiHelper->canManage(
            $course,
            $session,
            $group,
        );
        $this->wikiHelper->assertPageVisible($wiki, $canManage);
        $operationName = (string) $operation->getName();

        if ('post_wiki_page_unlock' === $operationName) {
            if ($wiki->getIsEditing() !== $user->getId() && !$canManage) {
                throw new AccessDeniedHttpException('You are not allowed to release this Wiki page lock.');
            }

            $wiki
                ->setIsEditing(0)
                ->setTimeEdit(null)
            ;
            $this->wikiRepository->update($wiki);

            return $this->buildResponse($wiki, false);
        }

        if (!$this->wikiHelper->canEditPage(
            $course,
            $session,
            $group,
            $wiki,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to edit this Wiki page.');
        }

        $lockOwnerId = $wiki->getIsEditing();
        $lockExpired = $this->isLockExpired($wiki);

        if ($lockOwnerId > 0 && $lockOwnerId !== $user->getId() && !$lockExpired && !$canManage) {
            $lockOwner = $this->entityManager->getRepository(User::class)->find($lockOwnerId);
            $lockOwnerName = $lockOwner instanceof User ? $lockOwner->getFullName() : '';

            throw new ConflictHttpException('' !== $lockOwnerName ? 'This Wiki page is currently being edited by '.$lockOwnerName.'.' : 'This Wiki page is currently being edited by another user.');
        }

        $wiki
            ->setIsEditing((int) $user->getId())
            ->setTimeEdit(new DateTime('now', new DateTimeZone('UTC')))
        ;
        $this->wikiRepository->update($wiki);

        return $this->buildResponse($wiki, true);
    }

    private function isLockExpired(CWiki $wiki): bool
    {
        $timeEdit = $wiki->getTimeEdit();
        if (!$timeEdit instanceof DateTimeInterface) {
            return true;
        }

        return time() - $timeEdit->getTimestamp() >= self::LOCK_TIMEOUT_SECONDS;
    }

    private function buildResponse(CWiki $wiki, bool $lockAcquired): WikiPageForm
    {
        $response = new WikiPageForm();
        $response->iid = null !== $wiki->getIid() ? (int) $wiki->getIid() : null;
        $response->pageId = null !== $wiki->getPageId() ? (int) $wiki->getPageId() : null;
        $response->reflink = $wiki->getReflink();
        $response->baseVersion = (int) $wiki->getVersion();
        $response->version = (int) $wiki->getVersion();
        $response->requiresLock = true;
        $response->lockAcquired = $lockAcquired;

        return $response;
    }
}
