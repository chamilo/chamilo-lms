<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\GlobalReporting;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class GlobalReportingContextResolver
{
    public function __construct(
        private Security $security,
        private AccessUrlHelper $accessUrlHelper,
        private SettingsManager $settingsManager,
        private PluginHelper $pluginHelper,
    ) {}

    public function resolve(): GlobalReportingContext
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User || null === $currentUser->getId()) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        $isTeacher = $this->security->isGranted('ROLE_TEACHER');
        $isSessionAdministrator = $this->security->isGranted('ROLE_SESSION_MANAGER');
        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl || null === $accessUrl->getId()) {
            throw new RuntimeException('The current access URL could not be resolved.');
        }

        return new GlobalReportingContext(
            $currentUser,
            (int) $accessUrl->getId(),
            $this->security->isGranted('ROLE_ADMIN'),
            $this->security->isGranted('ROLE_HR'),
            $isSessionAdministrator && !$isTeacher,
            $isTeacher || $isSessionAdministrator,
            $this->security->isGranted('ROLE_STUDENT_BOSS'),
            $this->isEnabled(
                $this->settingsManager->getSetting('session.drh_can_access_all_session_content', true),
            ),
            $this->isEnabled(
                $this->settingsManager->getSetting('tracking.tracking_skip_generic_data', true),
            ),
            $this->isEnabled(
                $this->settingsManager->getSetting('display.show_email_addresses', true),
            ),
            $this->isEnabled(
                $this->settingsManager->getSetting('tracking.block_my_progress_page', true),
            ),
            $this->isEnabled(
                $this->settingsManager->getSetting('add_users_by_coach', true),
            ),
            $this->isEnabled(
                $this->settingsManager->getSetting('skill.allow_teacher_access_student_skills', true),
            ),
            $this->pluginHelper->isPluginEnabled('LearningCalendar'),
            $this->pluginHelper->isPluginEnabled('StudentFollowUp'),
        );
    }

    private function isEnabled(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_int($value)) {
            return 1 === $value;
        }

        if (\is_string($value)) {
            return \in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}
