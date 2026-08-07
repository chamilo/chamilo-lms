<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\GlobalReporting;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\State\GlobalReporting\GlobalReportingDashboardProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/global-reporting/dashboard',
            name: 'get_global_reporting_dashboard',
            provider: GlobalReportingDashboardProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
    ],
    normalizationContext: ['groups' => ['global_reporting_dashboard:read']],
)]
final class GlobalReportingDashboard
{
    #[ApiProperty(identifier: true)]
    #[Groups(['global_reporting_dashboard:read'])]
    public string $id = 'global_reporting_dashboard';

    #[Groups(['global_reporting_dashboard:read'])]
    public int $currentUserId = 0;

    #[Groups(['global_reporting_dashboard:read'])]
    public bool $isAdministrator = false;

    #[Groups(['global_reporting_dashboard:read'])]
    public bool $isHumanResourcesManager = false;

    #[Groups(['global_reporting_dashboard:read'])]
    public bool $isSessionAdministratorOnly = false;

    #[Groups(['global_reporting_dashboard:read'])]
    public bool $canViewGlobalReports = false;

    #[Groups(['global_reporting_dashboard:read'])]
    public bool $isStudentBoss = false;

    #[Groups(['global_reporting_dashboard:read'])]
    public bool $skipGenericData = false;

    #[Groups(['global_reporting_dashboard:read'])]
    public bool $canManageFollowedScope = false;

    #[Groups(['global_reporting_dashboard:read'])]
    public bool $myProgressEnabled = true;

    #[Groups(['global_reporting_dashboard:read'])]
    public bool $learningCalendarEnabled = false;

    #[Groups(['global_reporting_dashboard:read'])]
    public bool $studentFollowUpEnabled = false;

    #[Groups(['global_reporting_dashboard:read'])]
    public ?string $redirectUrl = null;

    #[Groups(['global_reporting_dashboard:read'])]
    public int $students = 0;

    #[Groups(['global_reporting_dashboard:read'])]
    public int $studentBosses = 0;

    #[Groups(['global_reporting_dashboard:read'])]
    public int $teachers = 0;

    #[Groups(['global_reporting_dashboard:read'])]
    public int $humanResources = 0;

    #[Groups(['global_reporting_dashboard:read'])]
    public int $totalUsers = 0;

    #[Groups(['global_reporting_dashboard:read'])]
    public int $assignedCourses = 0;

    #[Groups(['global_reporting_dashboard:read'])]
    public int $followedCourses = 0;

    #[Groups(['global_reporting_dashboard:read'])]
    public int $followedSessions = 0;

    #[Groups(['global_reporting_dashboard:read'])]
    public ?float $averageCoursesPerStudent = null;

    #[Groups(['global_reporting_dashboard:read'])]
    public ?int $inactiveStudents = null;

    #[Groups(['global_reporting_dashboard:read'])]
    public ?int $averageTimeSpentSeconds = null;

    #[Groups(['global_reporting_dashboard:read'])]
    public ?float $averageLearningPathProgress = null;

    #[Groups(['global_reporting_dashboard:read'])]
    public ?float $averageScore = null;

    #[Groups(['global_reporting_dashboard:read'])]
    public ?int $forumPosts = null;

    #[Groups(['global_reporting_dashboard:read'])]
    public ?float $averageAssignments = null;
}
