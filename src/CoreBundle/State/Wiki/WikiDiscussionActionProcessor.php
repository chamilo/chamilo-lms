<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiDiscussionAction;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Helpers\WikiHelper;
use Chamilo\CourseBundle\Entity\CWiki;
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

/**
 * @implements ProcessorInterface<WikiDiscussionAction, WikiDiscussionAction>
 */
final readonly class WikiDiscussionActionProcessor implements ProcessorInterface
{
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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): WikiDiscussionAction
    {
        if (!$data instanceof WikiDiscussionAction) {
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

        if (!$this->wikiHelper->canRead($course, $session, $group)) {
            throw new AccessDeniedHttpException('You are not allowed to use Wiki discussions in this context.');
        }

        if ($this->studentViewHelper->isActive()) {
            throw new AccessDeniedHttpException('Wiki discussion actions are not available in student view.');
        }

        $pageId = isset($uriVariables['pageId']) ? (int) $uriVariables['pageId'] : 0;
        if ($pageId <= 0) {
            throw new BadRequestHttpException('A valid Wiki page id is required.');
        }

        $courseId = (int) $course->getId();
        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $groupId = null !== $group?->getIid() ? (int) $group->getIid() : 0;
        $latest = $this->wikiRepository->findLatestVersionInContext($courseId, $pageId, $groupId, $sessionId);

        if (!$latest instanceof CWiki) {
            throw new NotFoundHttpException('The requested Wiki discussion was not found in the current context.');
        }

        $canManage = $this->wikiHelper->canManage(
            $course,
            $session,
            $group,
        );

        if (null !== $session && !$canManage) {
            throw new AccessDeniedHttpException('Wiki discussions in sessions are available to session editors only.');
        }

        $this->wikiHelper->assertPageVisible($latest, $canManage);

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('An authenticated user is required.');
        }

        $isWorkOwner = 2 === $latest->getAssignment() && $latest->getUserId() === (int) $user->getId();
        if (1 !== $latest->getVisibilityDisc() && !$canManage && !$isWorkOwner) {
            throw new AccessDeniedHttpException('This Wiki discussion is not visible in the current context.');
        }

        $operationName = (string) $operation->getName();

        if (WikiDiscussionAction::OPERATION_SUBSCRIPTION === $operationName) {
            $this->changeSubscription($data, $latest, $user, $courseId, $sessionId, $groupId);

            return $data;
        }

        if (!$canManage) {
            throw new AccessDeniedHttpException('You are not allowed to manage this Wiki discussion.');
        }

        $versions = $this->wikiRepository->findPageVersionsInContext($courseId, $pageId, $groupId, $sessionId);

        foreach ($versions as $version) {
            match ($operationName) {
                WikiDiscussionAction::OPERATION_VISIBILITY => $version->setVisibilityDisc($data->enabled ? 1 : 0),
                WikiDiscussionAction::OPERATION_COMMENTING => $version->setAddlockDisc($data->enabled ? 1 : 0),
                WikiDiscussionAction::OPERATION_RATING => $version->setRatinglockDisc($data->enabled ? 1 : 0),
                default => throw new BadRequestHttpException('The requested Wiki discussion action is not supported.'),
            };
        }

        $this->entityManager->flush();

        return $data;
    }

    private function changeSubscription(
        WikiDiscussionAction $data,
        CWiki $wiki,
        User $user,
        int $courseId,
        int $sessionId,
        int $groupId,
    ): void {
        $type = 'watchdisc:'.$wiki->getReflink();
        $subscription = $this->entityManager->getRepository(CWikiMailcue::class)->createQueryBuilder('m')
            ->andWhere('m.cId = :courseId')
            ->andWhere('COALESCE(m.groupId, 0) = :groupId')
            ->andWhere('COALESCE(m.sessionId, 0) = :sessionId')
            ->andWhere('m.userId = :userId')
            ->andWhere('m.type = :type')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('groupId', $groupId, Types::INTEGER)
            ->setParameter('sessionId', $sessionId, Types::INTEGER)
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ->setParameter('type', $type, Types::STRING)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

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
    }
}
