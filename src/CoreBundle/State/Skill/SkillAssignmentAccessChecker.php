<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Skill;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class SkillAssignmentAccessChecker
{
    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
        private SettingsManager $settingsManager,
    ) {}

    public function assertCanManage(User $targetUser): User
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        if ($this->security->isGranted('ROLE_ADMIN') && $this->security->isGranted('EDIT', $targetUser)) {
            return $currentUser;
        }

        $currentUserId = (int) $currentUser->getId();
        $targetUserId = (int) $targetUser->getId();

        if (
            $this->security->isGranted('ROLE_STUDENT_BOSS')
            && $this->userRepository->isUserBossOfStudent($targetUserId, $currentUserId)
        ) {
            return $currentUser;
        }

        if ($this->security->isGranted('ROLE_HR')) {
            $hrSkillManagementEnabled = 'true' === $this->settingsManager->getSetting(
                'skill.allow_hr_skills_management',
                true,
            );
            $privateSkillManagementEnabled = 'true' === $this->settingsManager->getSetting(
                'skill.allow_private_skills',
                true,
            );

            if (
                ($hrSkillManagementEnabled || $privateSkillManagementEnabled)
                && $this->userRepository->isUserFollowedByDrh($targetUserId, $currentUserId)
            ) {
                return $currentUser;
            }
        }

        throw new AccessDeniedHttpException('You cannot manage skills for this user.');
    }
}
