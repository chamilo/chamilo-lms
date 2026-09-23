<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * @implements ProcessorInterface<UserRelUser, UserRelUser|void>
 */
final class UserRelUserStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly ProcessorInterface $removeProcessor,
        private readonly EntityManagerInterface $entityManager,
        private readonly SettingsManager $settingsManager,
        private readonly MessageRepository $messageRepository,
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?UserRelUser
    {
        if ($operation instanceof DeleteOperationInterface) {
            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        if ($operation instanceof Post
            && $data instanceof UserRelUser
            && UserRelUser::USER_RELATION_TYPE_FRIEND_REQUEST === $data->getRelationType()
        ) {
            $this->assertFriendRequestAllowed($data);
        }

        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        \assert($result instanceof UserRelUser);

        // Friend-request acceptance is currently sent as PATCH by the Vue UI.
        // Keep the friendship symmetric independently of whether API Platform
        // reached this processor through POST, PUT or PATCH.
        if (UserRelUser::USER_RELATION_TYPE_FRIEND === $result->getRelationType()) {
            $this->ensureInverseFriendship($result);
        }

        return $result;
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

        foreach ([[$user, $friend], [$friend, $user]] as [$source, $target]) {
            foreach ($relationTypes as $relationType) {
                if (null !== $repository->findOneBy([
                    'user' => $source,
                    'friend' => $target,
                    'relationType' => $relationType,
                ])) {
                    return true;
                }
            }
        }

        return false;
    }

    private function ensureInverseFriendship(UserRelUser $friendship): void
    {
        $repository = $this->entityManager->getRepository(UserRelUser::class);
        $inverseFriend = $repository->findOneBy([
            'user' => $friendship->getFriend(),
            'friend' => $friendship->getUser(),
            'relationType' => UserRelUser::USER_RELATION_TYPE_FRIEND,
        ]);

        if ($inverseFriend instanceof UserRelUser) {
            return;
        }

        $inverseGoodFriend = $repository->findOneBy([
            'user' => $friendship->getFriend(),
            'friend' => $friendship->getUser(),
            'relationType' => UserRelUser::USER_RELATION_TYPE_GOODFRIEND,
        ]);
        if ($inverseGoodFriend instanceof UserRelUser) {
            return;
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

        $inverseFriend = (new UserRelUser())
            ->setUser($friendship->getFriend())
            ->setFriend($friendship->getUser())
            ->setRelationType(UserRelUser::USER_RELATION_TYPE_FRIEND)
        ;

        $this->entityManager->persist($inverseFriend);
        $this->entityManager->flush();
    }
}
