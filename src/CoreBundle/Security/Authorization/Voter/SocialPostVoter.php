<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\SocialPost;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class SocialPostVoter extends Voter
{
    public const CREATE = 'CREATE';
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    public function __construct(
        private Security $security,
        private SettingsManager $settingsManager
    ) {
    }

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

        /** @var User $currentUser */
        $currentUser = $token->getUser();

        if (!$currentUser instanceof UserInterface) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var SocialPost $post */
        $post = $subject;
        $sender = $post->getSender();
        $userReceiver = $post->getUserReceiver();

        switch ($attribute) {
            case self::CREATE:
                if (
                    $userReceiver &&
                    !$sender->hasFriendWithRelationType($userReceiver, UserRelUser::USER_RELATION_TYPE_FRIEND)
                ) {
                    return false;
                }
                // no break
            case self::EDIT:
            case self::DELETE:
                if ($sender === $currentUser) {
                    return true;
                }

                break;
            case self::VIEW:
                return true;
        }

        return false;
    }
}
