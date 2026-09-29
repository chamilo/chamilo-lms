<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseReporting;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\CourseReporting\CourseReportingOverviewProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/course-reporting/overview',
            name: 'get_course_reporting_overview',
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier',
                    required: true,
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier',
                ),
                'gid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Group identifier',
                ),
            ],
            provider: CourseReportingOverviewProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
    ],
    normalizationContext: ['groups' => ['course_reporting_overview:read']],
)]
final class CourseReportingOverview
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_reporting_overview:read'])]
    public string $id = 'course_reporting_overview';

    #[Groups(['course_reporting_overview:read'])]
    public int $numberStudents = 0;

    #[Groups(['course_reporting_overview:read'])]
    public int $completedLearningPaths = 0;

    #[Groups(['course_reporting_overview:read'])]
    public float $exerciseAverage = 0.0;

    #[Groups(['course_reporting_overview:read'])]
    public int $certificateCount = 0;

    /**
     * @var int[]
     */
    #[Groups(['course_reporting_overview:read'])]
    public array $scoreDistribution = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_reporting_overview:read'])]
    public array $topStudents = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_reporting_overview:read'])]
    public array $timeStudents = [];
}
