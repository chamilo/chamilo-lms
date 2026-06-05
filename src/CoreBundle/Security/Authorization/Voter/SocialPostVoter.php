<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\SocialPost;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<'CREATE'|'VIEW'|'EDIT'|'DELETE', SocialPost>
 */
class SocialPostVoter extends Voter
{
    public const CREATE = 'CREATE';
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    public function __construct(
        private SettingsManager $settingsManager
    ) {}

    protected function supports(string $attribute, $subject): bool
    {
        $options = [
            self::CREATE,
            self::VIEW,
            self::EDIT,
            self::DELETE,
        ];

        if (!\in_array($attribute, $options, true)) {
            return false;
        }

        if (!$subject instanceof SocialPost) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        if ('true' !== $this->settingsManager->getSetting('social.allow_social_tool')) {
            return false;
        }

        /** @var ?User $currentUser */
        $currentUser = $token->getUser();

        if (null === $currentUser) {
            return false;
        }

        /** @var SocialPost $post */
        $post = $subject;
        $sender = $post->getSender();
        $userReceiver = $post->getUserReceiver();

        switch ($attribute) {
            case self::CREATE:
                if ($currentUser !== $sender) {
                    return false;
                }

                if (
                    $userReceiver
                    && !$currentUser->hasFriendWithRelationType($userReceiver, UserRelUser::USER_RELATION_TYPE_FRIEND)
                ) {
                    return false;
                }

                if (SocialPost::TYPE_PROMOTED_MESSAGE === $post->getType() && !$currentUser->isAdmin()) {
                    return false;
                }

                return true;

            case self::EDIT:
            case self::DELETE:
                if ($sender === $currentUser) {
                    return true;
                }

                break;

            case self::VIEW:
                // Sender and direct recipient can always read the post.
                if ($sender === $currentUser || $userReceiver === $currentUser) {
                    return true;
                }

                // Promoted messages are platform-wide announcements.
                if (SocialPost::TYPE_PROMOTED_MESSAGE === $post->getType()) {
                    return true;
                }

                // Wall posts/comments are visible only to friends of the sender.
                if (
                    \in_array($post->getType(), [SocialPost::TYPE_WALL_POST, SocialPost::TYPE_WALL_COMMENT], true)
                    && $currentUser->hasFriendWithRelationType($sender, UserRelUser::USER_RELATION_TYPE_FRIEND)
                ) {
                    return true;
                }

                // Group messages are visible only to members of the receiving group.
                $groupReceiver = $post->getGroupReceiver();
                if (
                    SocialPost::TYPE_GROUP_MESSAGE === $post->getType()
                    && $groupReceiver instanceof Usergroup
                    && $this->isMemberOfGroup($currentUser, $groupReceiver)
                ) {
                    return true;
                }

                break;
        }

        return false;
    }

    private function isMemberOfGroup(User $user, Usergroup $group): bool
    {
        foreach ($group->getUsers() as $relUser) {
            if ($relUser->getUser() === $user) {
                return true;
            }
        }

        return false;
    }
}
