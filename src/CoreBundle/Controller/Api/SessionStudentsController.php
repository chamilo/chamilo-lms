<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\AccessUrlRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use SessionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * Adds students to a session, or removes them, for integrations that manage enrolments from another system.
 *
 * It does what the legacy session screens do (SessionManager), and it is its own route rather than a change to
 * /api/course-sessions/actions/*: those are the actions of the tutors' screens and follow the platform setting
 * allow_tutors_to_assign_students_to_session, which an administrator may have turned off on purpose. This route
 * requires ROLE_ADMIN, which already allows the same changes in the administration screens.
 *
 * Nothing here removes someone the caller did not name: subscribing keeps the other students of the session, and
 * unsubscribing only removes the listed ids that are students of it.
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/api/sessions/{id}/students', requirements: ['id' => '\d+'])]
final class SessionStudentsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {}

    #[Route('', name: 'api_session_students', methods: ['GET'])]
    public function list(int $id): JsonResponse
    {
        $session = $this->findSession($id);
        if ($session instanceof JsonResponse) {
            return $session;
        }

        return $this->json(['userIds' => $this->studentIds($session)]);
    }

    #[Route('/subscribe', name: 'api_session_students_subscribe', methods: ['POST'])]
    public function subscribe(Request $request, int $id): JsonResponse
    {
        $session = $this->findSession($id);
        if ($session instanceof JsonResponse) {
            return $session;
        }
        $requested = $this->readIds($request);
        if ($requested instanceof JsonResponse) {
            return $requested;
        }

        $have = $this->studentIds($session);
        $eligible = $this->eligibleUserIds($requested);
        $toAdd = array_values(array_diff($eligible, $have));

        if ([] !== $toAdd) {
            // empty_users is false: the other students of the session are kept. The students are subscribed to every
            // course of the session, like the session screens do.
            SessionManager::subscribeUsersToSession((int) $session->getId(), $toAdd, null, false, true);
        }

        return $this->json([
            'success' => true,
            'applied' => $toAdd,
            'alreadyThere' => array_values(array_intersect($eligible, $have)),
            'ignored' => array_values(array_diff($requested, $eligible)),
        ]);
    }

    #[Route('/unsubscribe', name: 'api_session_students_unsubscribe', methods: ['POST'])]
    public function unsubscribe(Request $request, int $id): JsonResponse
    {
        $session = $this->findSession($id);
        if ($session instanceof JsonResponse) {
            return $session;
        }
        $requested = $this->readIds($request);
        if ($requested instanceof JsonResponse) {
            return $requested;
        }

        $have = $this->studentIds($session);
        $toRemove = array_values(array_intersect($requested, $have));
        foreach ($toRemove as $userId) {
            SessionManager::unsubscribe_user_from_session((int) $session->getId(), $userId);
        }

        return $this->json([
            'success' => true,
            'applied' => $toRemove,
            'ignored' => array_values(array_diff($requested, $have)),
        ]);
    }

    private function findSession(int $id): JsonResponse|Session
    {
        $session = $this->em->find(Session::class, $id);
        if (!$session instanceof Session || !$this->sessionBelongsToCurrentUrl($session)) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        return $session;
    }

    /**
     * @return list<int>|JsonResponse
     */
    private function readIds(Request $request): array|JsonResponse
    {
        try {
            $payload = $request->toArray();
        } catch (Throwable) {
            return $this->json(['error' => 'The body must be a JSON object'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($payload['userIds']) || !\is_array($payload['userIds'])) {
            return $this->json(['error' => '"userIds" must be a list of ids'], Response::HTTP_BAD_REQUEST);
        }

        $ids = [];
        foreach ($payload['userIds'] as $value) {
            if (!\is_int($value) && !(\is_string($value) && ctype_digit($value))) {
                return $this->json(['error' => '"userIds" must only contain integer ids'], Response::HTTP_BAD_REQUEST);
            }
            if ((int) $value > 0) {
                $ids[(int) $value] = (int) $value;
            }
        }

        return array_values($ids);
    }

    /**
     * @return list<int>
     */
    private function studentIds(Session $session): array
    {
        $rows = $this->em
            ->createQuery('SELECT IDENTITY(r.user) FROM '.SessionRelUser::class.' r WHERE r.session = :session AND r.relationType = :type')
            ->setParameter('session', $session)
            ->setParameter('type', Session::STUDENT)
            ->getSingleColumnResult()
        ;
        $ids = array_values(array_unique(array_map('intval', $rows)));
        sort($ids);

        return $ids;
    }

    /**
     * The users that may be students: they exist, are not deleted, are not anonymous and are not human resources
     * managers (who follow students, they are not enrolled), and belong to the current access URL.
     *
     * @param list<int> $ids
     *
     * @return list<int>
     */
    private function eligibleUserIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $users = $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', $ids, ArrayParameterType::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        $eligible = [];
        foreach ($users as $user) {
            if (!$user instanceof User
                || User::SOFT_DELETED === $user->getActive()
                || ANONYMOUS === $user->getStatus()
                || DRH === $user->getStatus()
                || !$this->userBelongsToCurrentUrl($user)
            ) {
                continue;
            }
            $eligible[] = (int) $user->getId();
        }
        sort($eligible);

        return $eligible;
    }

    private function sessionBelongsToCurrentUrl(Session $session): bool
    {
        if (!$this->accessUrlHelper->isMultiple()) {
            return true;
        }

        $currentUrlId = (int) $this->accessUrlHelper->getCurrent()->getId();
        foreach ($session->getUrls() as $relation) {
            if ((int) $relation->getUrl()->getId() === $currentUrlId) {
                return true;
            }
        }

        return false;
    }

    private function userBelongsToCurrentUrl(User $user): bool
    {
        if (!$this->accessUrlHelper->isMultiple()) {
            return true;
        }

        $currentUrlId = (int) $this->accessUrlHelper->getCurrent()->getId();

        return $user->getPortals()->exists(
            static fn (int|string $key, AccessUrlRelUser $relation): bool => (int) $relation->getUrl()->getId() === $currentUrlId,
        );
    }
}
