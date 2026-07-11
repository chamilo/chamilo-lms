<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\State\LearningPath\LearningPathReportingProvider;
use Chamilo\CoreBundle\State\LearningPath\LearningPathReportingRecalculateProcessor;
use Chamilo\CoreBundle\State\LearningPath\LearningPathReportingResetProcessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/learning_paths/{lpId}/reporting',
            requirements: ['lpId' => '\\d+'],
            name: 'get_learning_path_reporting',
            provider: LearningPathReportingProvider::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
        new Post(
            uriTemplate: '/learning_paths/{lpId}/reporting/recalculate',
            requirements: ['lpId' => '\\d+'],
            read: false,
            status: Response::HTTP_NO_CONTENT,
            input: LearningPathReportingRecalculateInput::class,
            output: false,
            denormalizationContext: ['groups' => ['learning_path_reporting_recalculate:write']],
            name: 'recalculate_learning_path_reporting',
            processor: LearningPathReportingRecalculateProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
        new Post(
            uriTemplate: '/learning_paths/{lpId}/reporting/reset',
            requirements: ['lpId' => '\\d+'],
            read: false,
            status: Response::HTTP_NO_CONTENT,
            input: LearningPathReportingResetInput::class,
            output: false,
            name: 'reset_learning_path_reporting',
            processor: LearningPathReportingResetProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
    ],
    normalizationContext: ['groups' => ['learning_path_reporting:read']],
    denormalizationContext: ['groups' => ['learning_path_reporting_reset:write']],
)]
final class LearningPathReporting
{
    #[ApiProperty(identifier: true)]
    #[Groups(['learning_path_reporting:read'])]
    public int $lpId = 0;

    #[Groups(['learning_path_reporting:read'])]
    public string $lpTitle = '';

    #[Groups(['learning_path_reporting:read'])]
    public int $courseId = 0;

    #[Groups(['learning_path_reporting:read'])]
    public string $courseTitle = '';

    #[Groups(['learning_path_reporting:read'])]
    public int $sessionId = 0;

    #[Groups(['learning_path_reporting:read'])]
    public bool $showEmail = false;

    #[Groups(['learning_path_reporting:read'])]
    public bool $hideTime = false;

    #[Groups(['learning_path_reporting:read'])]
    public bool $reducedReport = false;

    #[Groups(['learning_path_reporting:read'])]
    public bool $allowUserGroups = false;

    #[Groups(['learning_path_reporting:read'])]
    public bool $showTeachers = false;

    #[Groups(['learning_path_reporting:read'])]
    public string $groupFilter = '';

    /** @var array<int, array{label: string, value: string}> */
    #[Groups(['learning_path_reporting:read'])]
    public array $groupOptions = [];

    /** @var array<int, array<string, mixed>> */
    #[Groups(['learning_path_reporting:read'])]
    public array $learners = [];

    /** @var array<string, mixed> */
    #[Groups(['learning_path_reporting:read'])]
    public array $detail = [];

    #[Groups(['learning_path_reporting:read'])]
    public string $csrfToken = '';
}
