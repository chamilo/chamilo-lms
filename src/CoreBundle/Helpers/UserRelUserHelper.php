<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Single source of truth for reading, writing and authorizing user relations.
 *
 * Access rules (who may touch a row) are consumed by UserRelUserVoter; write
 * invariants and the friend-request consent rules are consumed by the API
 * processor and by the create/update/delete methods below, so any caller gets
 * the same checks.
 */
final class UserRelUserHelper
{
    public function __construct(
        private readonly Security $security,
        private readonly UserHelper $userHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly SettingsManager $settingsManager,
        private readonly MessageRepository $messageRepository,
    ) {}

    // ---- Access rules (used by UserRelUserVoter) ----

    public function canView(UserRelUser $relation, User $user): bool
    {
        return $this->isParty($relation, $user);
    }

    public function canEdit(UserRelUser $relation, User $user): bool
    {
        if ($relation->getUser() === $user) {
            return true;
        }

        // The recipient may only act on a pending friend request addressed to them.
        return $relation->getFriend() === $user && $relation->isFriendRequest();
    }

    public function canDelete(UserRelUser $relation, User $user): bool
    {
        return $this->isParty($relation, $user);
    }

    public function canCreate(UserRelUser $relation, User $user): bool
    {
        return $relation->getUser() === $user;
    }

    // ---- Write operations with validation (reusable outside the API) ----

    public function create(UserRelUser $relation): UserRelUser
    {
        $this->assertValidCreate($relation);
        $this->entityManager->persist($relation);
        $this->entityManager->flush();
        $this->ensureInverseFriendship($relation);

        return $relation;
    }

    public function update(UserRelUser $relation, UserRelUser $previous): UserRelUser
    {
        $this->assertValidUpdate($relation, $previous);
        $this->entityManager->flush();
        $this->ensureInverseFriendship($relation);

        return $relation;
    }

    public function delete(UserRelUser $relation): void
    {
        if (!$this->isAdmin() && !$this->canDelete($relation, $this->currentUser())) {
            throw new AccessDeniedHttpException('You are not allowed to manage this relation.');
        }

        $this->entityManager->remove($relation);
        $this->entityManager->flush();
    }

    // ---- Write invariants (also used by the API processor) ----

    public function assertValidCreate(UserRelUser $relation): void
    {
        if ($this->isAdmin()) {
            return;
        }

        // A user may only create a relation they own.
        if (!$this->canCreate($relation, $this->currentUser())) {
            throw new AccessDeniedHttpException('You can only create relations you own.');
        }

        // HR-manager, boss and their request variants are assigned by administrators only.
        if (!$relation->isFriendRequest()) {
            throw new AccessDeniedHttpException('Only friend requests can be created.');
        }

        $this->assertFriendRequestAllowed($relation);
    }

    public function assertValidUpdate(UserRelUser $relation, UserRelUser $previous): void
    {
        if ($this->isAdmin()) {
            return;
        }

        // Only a party to the stored row may change it.
        if (!$this->canEdit($previous, $this->currentUser())) {
            throw new AccessDeniedHttpException('You are not allowed to manage this relation.');
        }

        // The two parties are immutable, so an owned row cannot be re-pointed at
        // another user to fabricate a relationship.
        if ($relation->getUser()->getId() !== $previous->getUser()->getId()
            || $relation->getFriend()->getId() !== $previous->getFriend()->getId()
        ) {
            throw new AccessDeniedHttpException('The users of a relation cannot be changed.');
        }

        if (!$relation->isEditableSocialType()) {
            throw new AccessDeniedHttpException('This relation type cannot be set.');
        }
    }

    public function ensureInverseFriendship(UserRelUser $friendship): void
    {
        if (UserRelUser::USER_RELATION_TYPE_FRIEND !== $friendship->getRelationType()) {
            return;
        }

        $repository = $this->entityManager->getRepository(UserRelUser::class);
        foreach ([UserRelUser::USER_RELATION_TYPE_FRIEND, UserRelUser::USER_RELATION_TYPE_GOODFRIEND] as $relationType) {
            $existing = $repository->findOneBy([
                'user' => $friendship->getFriend(),
                'friend' => $friendship->getUser(),
                'relationType' => $relationType,
            ]);

            if ($existing instanceof UserRelUser) {
                return;
            }
        }

        // Convert an old crossed pending request instead of creating a third row.
        $inverseRequest = $repository->findOneBy([
            'user' => $friendship->getFriend(),
            'friend' => $friendship->getUser(),
            'relationType' => UserRelUser::USER_RELATION_TYPE_FRIEND_REQUEST,
        ]);

        if ($inverseRequest instanceof UserRelUser) {
            $inverseRequest->setRelationType(UserRelUser::USER_RELATION_TYPE_FRIEND);
            $this->entityManager->flush();

            return;
        }

        $inverse = (new UserRelUser())
            ->setUser($friendship->getFriend())
            ->setFriend($friendship->getUser())
            ->setRelationType(UserRelUser::USER_RELATION_TYPE_FRIEND)
        ;

        $this->entityManager->persist($inverse);
        $this->entityManager->flush();
    }

    private function assertFriendRequestAllowed(UserRelUser $request): void
    {
        $requester = $request->getUser();
        $target = $request->getFriend();

        if ($requester->getId() === $target->getId()) {
            throw new ConflictHttpException('A user cannot send a friend request to themselves.');
        }

        $targetIsStudent = $target->isStudent() || 'ROLE_STUDENT' === User::getRoleFromStatus($target->getStatus());
        if ($this->isTeacherOrAdmin($requester)
            && $targetIsStudent
            && !$this->teachersCanConnectToStudents()
        ) {
            throw new AccessDeniedHttpException('Friend invitations from teachers or administrators to learners are disabled.');
        }

        if ($this->hasFriendshipOrPendingRequest($requester, $target)
            || $this->messageRepository->existingInvitations($requester, $target)
        ) {
            throw new ConflictHttpException('A friendship or friend request already exists between these users.');
        }
    }

    private function isTeacherOrAdmin(User $user): bool
    {
        return $user->isTeacher()
            || $user->isAdmin()
            || $user->isSuperAdmin()
            || 'ROLE_TEACHER' === User::getRoleFromStatus($user->getStatus());
    }

    private function teachersCanConnectToStudents(): bool
    {
        $value = strtolower(trim((string) $this->settingsManager->getSetting('social.social_make_teachers_friend_all')));

        return \in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    private function hasFriendshipOrPendingRequest(User $user, User $friend): bool
    {
        $repository = $this->entityManager->getRepository(UserRelUser::class);
        $relationTypes = [
            UserRelUser::USER_RELATION_TYPE_FRIEND,
            UserRelUser::USER_RELATION_TYPE_GOODFRIEND,
            UserRelUser::USER_RELATION_TYPE_FRIEND_REQUEST,
        ];

        foreach ([[$user, $friend], [$friend, $user]] as [$source, $relationTarget]) {
            foreach ($relationTypes as $relationType) {
                if (null !== $repository->findOneBy([
                    'user' => $source,
                    'friend' => $relationTarget,
                    'relationType' => $relationType,
                ])) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isParty(UserRelUser $relation, User $user): bool
    {
        return $relation->getUser() === $user || $relation->getFriend() === $user;
    }

    private function isAdmin(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function currentUser(): User
    {
        $user = $this->userHelper->getCurrent();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A current user is required.');
        }

        return $user;
    }
}
