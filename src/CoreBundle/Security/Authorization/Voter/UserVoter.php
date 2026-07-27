<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends Voter<'CREATE'|'VIEW'|'EDIT'|'DELETE', UserVoter>
 */
class UserVoter extends Voter
{
    public const string CREATE = 'CREATE';
    public const string VIEW = 'VIEW';
    public const string EDIT = 'EDIT';
    public const string DELETE = 'DELETE';

    public function __construct(
        private AccessDecisionManagerInterface $accessDecisionManager,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack
    ) {}

    protected function supports(string $attribute, $subject): bool
    {
        $options = [
            self::CREATE,
            self::VIEW,
            self::EDIT,
            self::DELETE,
        ];

        // if the attribute isn't one we support, return false
        if (!\in_array($attribute, $options, true)) {
            return false;
        }

        return $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var User $currentUser */
        $currentUser = $token->getUser();

        if (!$currentUser instanceof UserInterface) {
            return false;
        }

        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        /** @var User $user */
        $user = $subject;

        if (self::EDIT === $attribute) {
            // Only the owner can edit private data
            return (int) $currentUser->getId() === (int) $user->getId();
        }

        if (self::VIEW === $attribute) {
            if ((int) $currentUser->getId() === (int) $user->getId()) {
                return true;
            }

            if ($user->hasFriendWithRelationType($currentUser, UserRelUser::USER_RELATION_TYPE_FRIEND)) {
                return true;
            }

            $friendsOfFriends = $currentUser->getFriendsOfFriends();
            if (\in_array($user, $friendsOfFriends, true)) {
                return true;
            }

            if (
                $user->hasFriendWithRelationType($currentUser, UserRelUser::USER_RELATION_TYPE_BOSS)
                || $user->isFriendWithMeByRelationType($currentUser, UserRelUser::USER_RELATION_TYPE_BOSS)
            ) {
                return true;
            }

            if ($this->haveSharedMessages($currentUser, $user)) {
                return true;
            }
        }

        return false;
    }

    private function isFromSocialPage(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $pageOrigin = $request->query->get('page_origin');

            return 'social' === $pageOrigin;
        }

        return false;
    }

    private function haveSharedMessages(User $currentUser, User $targetUser): bool
    {
        $messageRepository = $this->entityManager->getRepository(Message::class);

        return $messageRepository->usersHaveSharedMessages($currentUser, $targetUser);
    }
}
