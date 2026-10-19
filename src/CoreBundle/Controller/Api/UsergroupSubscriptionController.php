<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Entity\UsergroupRelCourse;
use Chamilo\CoreBundle\Entity\UsergroupRelSession;
use Chamilo\CoreBundle\Entity\UsergroupRelUser;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\UsergroupHelper;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * Members, sessions and courses of a class (a Usergroup of the "normal class" type), for integrations.
 *
 * This is the API counterpart of the administration screens under /admin/usergroup-*-data, and it goes through the
 * same UsergroupHelper: subscribing users, sessions or courses to a class also (un)subscribes the members to the
 * sessions and courses linked to the class, which is what makes a class useful. Inserting rows into the relation
 * tables directly would not do that, which is why those tables are not exposed as API resources.
 *
 * Every route needs ROLE_ADMIN, like the administration screens it mirrors. Each PUT replaces the whole list, the same
 * semantics as the screens: ids that are absent from the request are unsubscribed.
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/api/usergroups/{id}', requirements: ['id' => '\d+'])]
final class UsergroupSubscriptionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly UsergroupHelper $usergroupHelper,
    ) {}

    #[Route('/subscriptions', name: 'api_usergroup_subscriptions', methods: ['GET'])]
    public function subscriptions(int $id): JsonResponse
    {
        $usergroup = $this->findClass($id);
        if ($usergroup instanceof JsonResponse) {
            return $usergroup;
        }

        return $this->json([
            'userIds' => $this->subscribedIds(UsergroupRelUser::class, 'user', $usergroup),
            'sessionIds' => $this->subscribedIds(UsergroupRelSession::class, 'session', $usergroup),
            'courseIds' => $this->subscribedIds(UsergroupRelCourse::class, 'course', $usergroup),
        ]);
    }

    #[Route('/users', name: 'api_usergroup_subscriptions_users', methods: ['PUT'])]
    public function putUsers(Request $request, int $id): JsonResponse
    {
        return $this->replace($request, $id, 'userIds', User::class, false, function (Usergroup $group, array $ids): void {
            // Relation type 0 is what every other class-membership writer uses (see UsergroupAddUsersController).
            $this->usergroupHelper->subscribeUsers((int) $group->getId(), $ids, true, 0);
        });
    }

    #[Route('/sessions', name: 'api_usergroup_subscriptions_sessions', methods: ['PUT'])]
    public function putSessions(Request $request, int $id): JsonResponse
    {
        return $this->replace($request, $id, 'sessionIds', Session::class, true, function (Usergroup $group, array $ids): void {
            $this->usergroupHelper->synchronizeSessions((int) $group->getId(), $ids);
        });
    }

    #[Route('/courses', name: 'api_usergroup_subscriptions_courses', methods: ['PUT'])]
    public function putCourses(Request $request, int $id): JsonResponse
    {
        return $this->replace($request, $id, 'courseIds', Course::class, true, function (Usergroup $group, array $ids): void {
            $this->usergroupHelper->synchronizeCourses((int) $group->getId(), $ids);
        });
    }

    /**
     * @param class-string                          $entity       Entity of the ids
     * @param bool                                  $limitToUrl   Keep only the entities of the current access URL
     * @param callable(Usergroup, list<int>): void  $apply        Applies the list through UsergroupHelper
     */
    private function replace(Request $request, int $id, string $key, string $entity, bool $limitToUrl, callable $apply): JsonResponse
    {
        $usergroup = $this->findClass($id);
        if ($usergroup instanceof JsonResponse) {
            return $usergroup;
        }

        try {
            $payload = $request->toArray();
        } catch (Throwable) {
            return $this->json(['error' => 'The body must be a JSON object'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($payload[$key]) || !\is_array($payload[$key])) {
            return $this->json(['error' => '"'.$key.'" must be a list of ids'], Response::HTTP_BAD_REQUEST);
        }

        $requested = [];
        foreach ($payload[$key] as $value) {
            if (!\is_int($value) && !(\is_string($value) && ctype_digit($value))) {
                return $this->json(['error' => '"'.$key.'" must only contain integer ids'], Response::HTTP_BAD_REQUEST);
            }
            if ((int) $value > 0) {
                $requested[(int) $value] = (int) $value;
            }
        }
        $requested = array_values($requested);

        $allowed = $this->existingIds($entity, $requested, $limitToUrl);
        $ignored = array_values(array_diff($requested, $allowed));

        $apply($usergroup, $allowed);

        return $this->json(['success' => true, 'applied' => $allowed, 'ignored' => $ignored]);
    }

    /**
     * The class, or the error response to return. Social groups are refused: their membership has roles and rules that
     * differ from a class, and these routes only implement the class semantics.
     */
    private function findClass(int $id): Usergroup|JsonResponse
    {
        $usergroup = $this->em->find(Usergroup::class, $id);
        if (!$usergroup instanceof Usergroup || !$this->belongsToCurrentUrl($usergroup)) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        if (Usergroup::NORMAL_CLASS !== $usergroup->getGroupType()) {
            return $this->json(['error' => 'This group is not a class'], Response::HTTP_CONFLICT);
        }

        return $usergroup;
    }

    /**
     * @param class-string $relation Relation entity
     *
     * @return list<int>
     */
    private function subscribedIds(string $relation, string $field, Usergroup $usergroup): array
    {
        $rows = $this->em
            ->createQuery('SELECT IDENTITY(r.'.$field.') FROM '.$relation.' r WHERE r.usergroup = :group')
            ->setParameter('group', $usergroup)
            ->getSingleColumnResult()
        ;

        $ids = array_values(array_unique(array_map('intval', $rows)));
        sort($ids);

        return $ids;
    }

    /**
     * @param class-string $entity
     * @param list<int>    $ids
     *
     * @return list<int>
     */
    private function existingIds(string $entity, array $ids, bool $limitToUrl): array
    {
        if ([] === $ids) {
            return [];
        }

        $qb = $this->em->createQueryBuilder()
            ->select('e.id')
            ->from($entity, 'e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', $ids, ArrayParameterType::INTEGER)
        ;

        if ($limitToUrl && $this->accessUrlHelper->isMultiple()) {
            $accessUrl = $this->accessUrlHelper->getCurrent();
            if (null !== $accessUrl) {
                $qb->innerJoin('e.urls', 'urlRel')
                    ->andWhere('urlRel.url = :urlId')
                    ->setParameter('urlId', $accessUrl->getId(), Types::INTEGER)
                ;
            }
        }

        $found = array_map('intval', $qb->getQuery()->getSingleColumnResult());
        sort($found);

        return $found;
    }

    private function belongsToCurrentUrl(Usergroup $usergroup): bool
    {
        if (!$this->accessUrlHelper->isMultiple()) {
            return true;
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl) {
            return true;
        }

        foreach ($usergroup->getUrls() as $urlRel) {
            $relUrl = $urlRel->getUrl();
            if (null !== $relUrl && $relUrl->getId() === $accessUrl->getId()) {
                return true;
            }
        }

        return false;
    }
}
