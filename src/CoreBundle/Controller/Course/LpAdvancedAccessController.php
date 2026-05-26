<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Course;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpRelUser;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/resources/lp/{lpId}/advanced-access')]
final class LpAdvancedAccessController extends AbstractController
{
    #[Route('-data', name: 'chamilo_core_lp_advanced_access_data', methods: ['GET'])]
    public function data(
        int $lpId,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $context = $this->resolveContext($lpId, $request, $entityManager);
        if (!$context['valid']) {
            return $this->json(['error' => $context['error']], $context['status']);
        }

        /** @var Course $course */
        $course = $context['course'];
        /** @var CLp $lp */
        $lp = $context['lp'];
        /** @var Session|null $session */
        $session = $context['session'];

        $this->denyAccessUnlessGranted('EDIT', $course);

        return $this->json([
            'lp' => [
                'id' => $lp->getIid(),
                'title' => $lp->getTitle(),
            ],
            'course' => [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
            ],
            'session' => $session ? [
                'id' => $session->getId(),
                'title' => $session->getTitle(),
            ] : null,
            'users' => $this->getUsers($entityManager, $course, $lp, $session),
            'groups' => $this->getGroups($entityManager, $course, $lp, $session),
        ]);
    }

    #[Route('/user', name: 'chamilo_core_lp_advanced_access_save_user', methods: ['POST'])]
    public function saveUser(
        int $lpId,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $context = $this->resolveContext($lpId, $request, $entityManager);
        if (!$context['valid']) {
            return $this->json(['error' => $context['error']], $context['status']);
        }

        /** @var Course $course */
        $course = $context['course'];
        /** @var CLp $lp */
        $lp = $context['lp'];
        /** @var Session|null $session */
        $session = $context['session'];

        $this->denyAccessUnlessGranted('EDIT', $course);

        $payload = $this->decodePayload($request);
        $userId = (int) ($payload['userId'] ?? 0);
        if (0 >= $userId) {
            return $this->json(['error' => 'Invalid user.'], 400);
        }

        /** @var User|null $user */
        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found.'], 404);
        }

        $dateError = $this->validateDateRange($payload);
        if (null !== $dateError) {
            return $this->json(['error' => $dateError], 400);
        }

        $entry = $this->findRestriction($entityManager, $course, $lp, $session, $user, null);
        if (!$entry instanceof CLpRelUser) {
            $entry = (new CLpRelUser())
                ->setCourse($course)
                ->setLp($lp)
                ->setUser($user)
                ->setCreatorUser($this->getManagedCurrentUser($entityManager))
                ->setCreatedAt(new \DateTime())
            ;

            if ($session instanceof Session) {
                $entry->setSession($session);
            }

            $entityManager->persist($entry);
        }

        $this->applyDates($entry, $payload);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/user/{userId}', name: 'chamilo_core_lp_advanced_access_delete_user', methods: ['DELETE'])]
    public function deleteUser(
        int $lpId,
        int $userId,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $context = $this->resolveContext($lpId, $request, $entityManager);
        if (!$context['valid']) {
            return $this->json(['error' => $context['error']], $context['status']);
        }

        /** @var Course $course */
        $course = $context['course'];
        /** @var CLp $lp */
        $lp = $context['lp'];
        /** @var Session|null $session */
        $session = $context['session'];

        $this->denyAccessUnlessGranted('EDIT', $course);

        /** @var User|null $user */
        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found.'], 404);
        }

        $dateError = $this->validateDateRange($payload);
        if (null !== $dateError) {
            return $this->json(['error' => $dateError], 400);
        }

        $entry = $this->findRestriction($entityManager, $course, $lp, $session, $user, null);
        if ($entry instanceof CLpRelUser) {
            $entityManager->remove($entry);
            $entityManager->flush();
        }

        return $this->json(['success' => true]);
    }

    #[Route('/group', name: 'chamilo_core_lp_advanced_access_save_group', methods: ['POST'])]
    public function saveGroup(
        int $lpId,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $context = $this->resolveContext($lpId, $request, $entityManager);
        if (!$context['valid']) {
            return $this->json(['error' => $context['error']], $context['status']);
        }

        /** @var Course $course */
        $course = $context['course'];
        /** @var CLp $lp */
        $lp = $context['lp'];
        /** @var Session|null $session */
        $session = $context['session'];

        $this->denyAccessUnlessGranted('EDIT', $course);

        $payload = $this->decodePayload($request);
        $groupId = (int) ($payload['groupId'] ?? 0);
        if (0 >= $groupId) {
            return $this->json(['error' => 'Invalid group.'], 400);
        }

        /** @var CGroup|null $group */
        $group = $entityManager->getRepository(CGroup::class)->find($groupId);
        if (!$group instanceof CGroup) {
            return $this->json(['error' => 'Group not found.'], 404);
        }

        $dateError = $this->validateDateRange($payload);
        if (null !== $dateError) {
            return $this->json(['error' => $dateError], 400);
        }

        $members = $this->getGroupMembers($entityManager, $course, $group);
        if ([] === $members) {
            return $this->json(['error' => 'The selected group has no members.'], 400);
        }

        foreach ($members as $user) {
            $entry = $this->findRestriction($entityManager, $course, $lp, $session, $user, $group);
            if (!$entry instanceof CLpRelUser) {
                $entry = (new CLpRelUser())
                    ->setCourse($course)
                    ->setLp($lp)
                    ->setUser($user)
                    ->setGroup($group)
                    ->setCreatorUser($this->getManagedCurrentUser($entityManager))
                    ->setCreatedAt(new \DateTime())
                ;

                if ($session instanceof Session) {
                    $entry->setSession($session);
                }

                $entityManager->persist($entry);
            }

            $this->applyDates($entry, $payload);
        }

        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/group/{groupId}', name: 'chamilo_core_lp_advanced_access_delete_group', methods: ['DELETE'])]
    public function deleteGroup(
        int $lpId,
        int $groupId,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $context = $this->resolveContext($lpId, $request, $entityManager);
        if (!$context['valid']) {
            return $this->json(['error' => $context['error']], $context['status']);
        }

        /** @var Course $course */
        $course = $context['course'];
        /** @var CLp $lp */
        $lp = $context['lp'];
        /** @var Session|null $session */
        $session = $context['session'];

        $this->denyAccessUnlessGranted('EDIT', $course);

        /** @var CGroup|null $group */
        $group = $entityManager->getRepository(CGroup::class)->find($groupId);
        if (!$group instanceof CGroup) {
            return $this->json(['error' => 'Group not found.'], 404);
        }

        $criteria = [
            'course' => $course,
            'lp' => $lp,
            'group' => $group,
        ];

        if ($session instanceof Session) {
            $criteria['session'] = $session;
        } else {
            $criteria['session'] = null;
        }

        $entries = $entityManager->getRepository(CLpRelUser::class)->findBy($criteria);
        foreach ($entries as $entry) {
            $entityManager->remove($entry);
        }

        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/clear-dates', name: 'chamilo_core_lp_advanced_access_clear_dates', methods: ['POST'])]
    public function clearDates(
        int $lpId,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $context = $this->resolveContext($lpId, $request, $entityManager);
        if (!$context['valid']) {
            return $this->json(['error' => $context['error']], $context['status']);
        }

        /** @var Course $course */
        $course = $context['course'];
        /** @var CLp $lp */
        $lp = $context['lp'];
        /** @var Session|null $session */
        $session = $context['session'];

        $this->denyAccessUnlessGranted('EDIT', $course);

        $criteria = [
            'course' => $course,
            'lp' => $lp,
        ];

        if ($session instanceof Session) {
            $criteria['session'] = $session;
        } else {
            $criteria['session'] = null;
        }

        $entries = $entityManager->getRepository(CLpRelUser::class)->findBy($criteria);
        foreach ($entries as $entry) {
            $entry
                ->setStartDate(null)
                ->setEndDate(null)
                ->setIsOpenWithoutDate(true)
            ;
        }

        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    /**
     * @return array{valid: bool, status: int, error?: string, course?: Course, lp?: CLp, session?: Session|null}
     */
    private function resolveContext(
        int $lpId,
        Request $request,
        EntityManagerInterface $entityManager,
    ): array {
        $courseId = (int) $request->query->get('cid', $request->request->get('cid', 0));
        $sessionId = (int) $request->query->get('sid', $request->request->get('sid', 0));

        if (0 >= $courseId) {
            return [
                'valid' => false,
                'status' => 400,
                'error' => 'Missing course identifier.',
            ];
        }

        /** @var Course|null $course */
        $course = $entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            return [
                'valid' => false,
                'status' => 404,
                'error' => 'Course not found.',
            ];
        }

        /** @var CLp|null $lp */
        $lp = $entityManager->getRepository(CLp::class)->find($lpId);
        if (!$lp instanceof CLp) {
            return [
                'valid' => false,
                'status' => 404,
                'error' => 'Learning path not found.',
            ];
        }

        if (!$this->lpBelongsToCourse($lp, $course, $sessionId, $entityManager)) {
            return [
                'valid' => false,
                'status' => 404,
                'error' => 'Learning path not found in this course.',
            ];
        }

        $session = null;
        if (0 < $sessionId) {
            /** @var Session|null $session */
            $session = $entityManager->getRepository(Session::class)->find($sessionId);
        }

        return [
            'valid' => true,
            'status' => 200,
            'course' => $course,
            'lp' => $lp,
            'session' => $session,
        ];
    }

    private function lpBelongsToCourse(CLp $lp, Course $course, int $sessionId, EntityManagerInterface $entityManager): bool
    {
        $qb = $entityManager->createQueryBuilder();

        $qb
            ->select('COUNT(lp.iid)')
            ->from(CLp::class, 'lp')
            ->join('lp.resourceNode', 'rn')
            ->join('rn.resourceLinks', 'rl')
            ->where('lp = :lp')
            ->andWhere('rl.course = :course')
            ->setParameter('lp', $lp)
            ->setParameter('course', $course)
        ;

        if (0 < $sessionId) {
            $qb->andWhere('(rl.session = :session OR rl.session IS NULL)')
                ->setParameter('session', $entityManager->getReference(Session::class, $sessionId))
            ;
        } else {
            $qb->andWhere('rl.session IS NULL');
        }

        return 0 < (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getUsers(
        EntityManagerInterface $entityManager,
        Course $course,
        CLp $lp,
        ?Session $session,
    ): array {
        $qb = $entityManager->createQueryBuilder();

        $qb
            ->select('cru', 'u')
            ->from(CourseRelUser::class, 'cru')
            ->join('cru.user', 'u')
            ->where('cru.course = :course')
            ->andWhere('cru.status = :student')
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->setParameter('course', $course)
            ->setParameter('student', CourseRelUser::STUDENT, Types::INTEGER)
        ;

        /** @var list<CourseRelUser> $subscriptions */
        $subscriptions = $qb->getQuery()->getResult();

        $users = [];
        foreach ($subscriptions as $subscription) {
            $user = $subscription->getUser();
            if ($user instanceof User) {
                $users[] = $user;
            }
        }

        $individualRestrictions = $this->getRestrictionsByUser($entityManager, $course, $lp, $session, false);
        $groupRestrictions = $this->getRestrictionsByUser($entityManager, $course, $lp, $session, true);
        $groupsByUser = $this->getGroupsByUser($entityManager, $course);

        $rows = [];
        foreach ($users as $user) {
            $userId = (int) $user->getId();
            $individual = $individualRestrictions[$userId] ?? null;
            $groupRestriction = $groupRestrictions[$userId] ?? null;
            $groups = $groupsByUser[$userId] ?? [];

            $rows[] = [
                'id' => $userId,
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'groups' => $groups,
                'individualRestriction' => $individual,
                'groupRestriction' => $groupRestriction,
                'effectiveRestriction' => $individual ?? $groupRestriction,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getGroups(
        EntityManagerInterface $entityManager,
        Course $course,
        CLp $lp,
        ?Session $session,
    ): array {
        $qb = $entityManager->createQueryBuilder();

        $qb
            ->select('gru', 'g')
            ->from(CGroupRelUser::class, 'gru')
            ->join('gru.group', 'g')
            ->where('gru.cId = :courseId')
            ->orderBy('g.title', 'ASC')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
        ;

        /** @var list<CGroupRelUser> $groupRelations */
        $groupRelations = $qb->getQuery()->getResult();

        $groups = [];
        foreach ($groupRelations as $groupRelation) {
            $group = $groupRelation->getGroup();
            if ($group instanceof CGroup) {
                $groups[(int) $group->getIid()] = $group;
            }
        }

        $restrictionsByGroup = $this->getRestrictionsByGroup($entityManager, $course, $lp, $session);

        $rows = [];
        foreach ($groups as $group) {
            $groupId = (int) $group->getIid();
            $members = $this->getGroupMembers($entityManager, $course, $group);

            $rows[] = [
                'id' => $groupId,
                'title' => $group->getTitle(),
                'membersCount' => count($members),
                'restriction' => $restrictionsByGroup[$groupId] ?? null,
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getRestrictionsByUser(
        EntityManagerInterface $entityManager,
        Course $course,
        CLp $lp,
        ?Session $session,
        bool $groupOnly,
    ): array {
        $qb = $entityManager->createQueryBuilder();

        $qb
            ->select('rel', 'u', 'g')
            ->from(CLpRelUser::class, 'rel')
            ->join('rel.user', 'u')
            ->leftJoin('rel.group', 'g')
            ->where('rel.course = :course')
            ->andWhere('rel.lp = :lp')
            ->setParameter('course', $course)
            ->setParameter('lp', $lp)
        ;

        if ($session instanceof Session) {
            $qb->andWhere('rel.session = :session')->setParameter('session', $session);
        } else {
            $qb->andWhere('rel.session IS NULL');
        }

        $qb->andWhere($groupOnly ? 'rel.group IS NOT NULL' : 'rel.group IS NULL');

        /** @var list<CLpRelUser> $rows */
        $rows = $qb->getQuery()->getResult();

        $result = [];
        foreach ($rows as $row) {
            $user = $row->getUser();
            $result[(int) $user->getId()] = $this->formatRestriction($row);
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getRestrictionsByGroup(
        EntityManagerInterface $entityManager,
        Course $course,
        CLp $lp,
        ?Session $session,
    ): array {
        $qb = $entityManager->createQueryBuilder();

        $qb
            ->select('rel', 'g')
            ->from(CLpRelUser::class, 'rel')
            ->join('rel.group', 'g')
            ->where('rel.course = :course')
            ->andWhere('rel.lp = :lp')
            ->andWhere('rel.group IS NOT NULL')
            ->setParameter('course', $course)
            ->setParameter('lp', $lp)
        ;

        if ($session instanceof Session) {
            $qb->andWhere('rel.session = :session')->setParameter('session', $session);
        } else {
            $qb->andWhere('rel.session IS NULL');
        }

        /** @var list<CLpRelUser> $rows */
        $rows = $qb->getQuery()->getResult();

        $result = [];
        foreach ($rows as $row) {
            $group = $row->getGroup();
            if ($group instanceof CGroup) {
                $result[(int) $group->getIid()] = $this->formatRestriction($row);
            }
        }

        return $result;
    }

    /**
     * @return array<int, list<array<string, mixed>>>
     */
    private function getGroupsByUser(EntityManagerInterface $entityManager, Course $course): array
    {
        $qb = $entityManager->createQueryBuilder();

        $qb
            ->select('gru', 'u', 'g')
            ->from(CGroupRelUser::class, 'gru')
            ->join('gru.user', 'u')
            ->join('gru.group', 'g')
            ->where('gru.cId = :courseId')
            ->orderBy('g.title', 'ASC')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
        ;

        /** @var list<CGroupRelUser> $rows */
        $rows = $qb->getQuery()->getResult();

        $result = [];
        foreach ($rows as $row) {
            $user = $row->getUser();
            $group = $row->getGroup();

            $result[(int) $user->getId()][] = [
                'id' => $group->getIid(),
                'title' => $group->getTitle(),
            ];
        }

        return $result;
    }

    /**
     * @return list<User>
     */
    private function getGroupMembers(EntityManagerInterface $entityManager, Course $course, CGroup $group): array
    {
        $qb = $entityManager->createQueryBuilder();

        $qb
            ->select('gru', 'u')
            ->from(CGroupRelUser::class, 'gru')
            ->join('gru.user', 'u')
            ->where('gru.cId = :courseId')
            ->andWhere('gru.group = :group')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('group', $group)
        ;

        /** @var list<CGroupRelUser> $rows */
        $rows = $qb->getQuery()->getResult();

        $members = [];
        foreach ($rows as $row) {
            $user = $row->getUser();
            if ($user instanceof User) {
                $members[] = $user;
            }
        }

        return $members;
    }

    private function findRestriction(
        EntityManagerInterface $entityManager,
        Course $course,
        CLp $lp,
        ?Session $session,
        User $user,
        ?CGroup $group,
    ): ?CLpRelUser {
        $criteria = [
            'course' => $course,
            'lp' => $lp,
            'user' => $user,
            'group' => $group,
        ];

        if ($session instanceof Session) {
            $criteria['session'] = $session;
        } else {
            $criteria['session'] = null;
        }

        /** @var CLpRelUser|null $entry */
        $entry = $entityManager->getRepository(CLpRelUser::class)->findOneBy($criteria);

        return $entry;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRestriction(CLpRelUser $restriction): array
    {
        return [
            'id' => $restriction->getIid(),
            'startDate' => $this->formatDateTime($restriction->getStartDate()),
            'endDate' => $this->formatDateTime($restriction->getEndDate()),
            'isOpenWithoutDate' => $restriction->getIsOpenWithoutDate(),
            'group' => $restriction->getGroup() instanceof CGroup ? [
                'id' => $restriction->getGroup()->getIid(),
                'title' => $restriction->getGroup()->getTitle(),
            ] : null,
        ];
    }

    private function getManagedCurrentUser(EntityManagerInterface $entityManager): ?User
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User || null === $currentUser->getId()) {
            return null;
        }

        /** @var User|null $managedUser */
        $managedUser = $entityManager->getRepository(User::class)->find((int) $currentUser->getId());

        return $managedUser;
    }

    private function validateDateRange(array $payload): ?string
    {
        $isOpenWithoutDate = (bool) ($payload['isOpenWithoutDate'] ?? false);
        if ($isOpenWithoutDate) {
            return null;
        }

        $startDate = $this->parseDate($payload['startDate'] ?? null);
        $endDate = $this->parseDate($payload['endDate'] ?? null);

        if (null === $startDate && '' !== trim((string) ($payload['startDate'] ?? ''))) {
            return 'Invalid start date.';
        }

        if (null === $endDate && '' !== trim((string) ($payload['endDate'] ?? ''))) {
            return 'Invalid end date.';
        }

        if ($startDate instanceof DateTimeInterface && $endDate instanceof DateTimeInterface && $startDate > $endDate) {
            return 'The end date must be after the start date.';
        }

        return null;
    }

    private function applyDates(CLpRelUser $entry, array $payload): void
    {
        $isOpenWithoutDate = (bool) ($payload['isOpenWithoutDate'] ?? false);

        $entry
            ->setIsOpenWithoutDate($isOpenWithoutDate)
            ->setStartDate($isOpenWithoutDate ? null : $this->parseDate($payload['startDate'] ?? null))
            ->setEndDate($isOpenWithoutDate ? null : $this->parseDate($payload['endDate'] ?? null))
        ;
    }

    private function parseDate(mixed $value): ?DateTimeInterface
    {
        $value = trim((string) $value);
        if ('' === $value) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function formatDateTime(?DateTimeInterface $date): ?string
    {
        return $date instanceof DateTimeInterface ? $date->format('Y-m-d\TH:i') : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(Request $request): array
    {
        $payload = json_decode($request->getContent() ?: '[]', true);

        return \is_array($payload) ? $payload : [];
    }
}
