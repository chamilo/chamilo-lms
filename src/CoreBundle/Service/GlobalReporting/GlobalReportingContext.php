<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\GlobalReporting;

use Chamilo\CoreBundle\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class GlobalReportingContext
{
    public function __construct(
        public User $currentUser,
        public int $accessUrlId,
        public bool $isAdministrator,
        public bool $isHumanResourcesManager,
        public bool $isSessionAdministratorOnly,
        public bool $canViewGlobalReports,
        public bool $isStudentBoss,
        public bool $humanResourcesCanAccessAllSessionContent,
        public bool $skipGenericData,
        public bool $showEmailAddresses,
        public bool $blockMyProgressPage,
        public bool $addUsersByCoach,
        public bool $allowTeacherAccessStudentSkills,
        public bool $learningCalendarEnabled,
        public bool $studentFollowUpEnabled,
    ) {}

    public function currentUserId(): int
    {
        return (int) $this->currentUser->getId();
    }
}
