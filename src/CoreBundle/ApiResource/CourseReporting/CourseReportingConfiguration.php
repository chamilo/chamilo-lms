<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseReporting;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\CourseReporting\CourseReportingConfigurationProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/course-reporting/configuration',
            name: 'get_course_reporting_configuration',
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
            provider: CourseReportingConfigurationProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
    ],
    normalizationContext: ['groups' => ['course_reporting_configuration:read']],
)]
final class CourseReportingConfiguration
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_reporting_configuration:read'])]
    public string $id = 'course_reporting_configuration';

    #[Groups(['course_reporting_configuration:read'])]
    public int $courseId = 0;

    #[Groups(['course_reporting_configuration:read'])]
    public int $courseResourceNodeId = 0;

    #[Groups(['course_reporting_configuration:read'])]
    public string $courseCode = '';

    #[Groups(['course_reporting_configuration:read'])]
    public string $courseTitle = '';

    #[Groups(['course_reporting_configuration:read'])]
    public int $sessionId = 0;

    #[Groups(['course_reporting_configuration:read'])]
    public string $sessionTitle = '';

    #[Groups(['course_reporting_configuration:read'])]
    public int $groupId = 0;

    #[Groups(['course_reporting_configuration:read'])]
    public int $currentUserId = 0;

    #[Groups(['course_reporting_configuration:read'])]
    public bool $allowMessageTracking = false;

    #[Groups(['course_reporting_configuration:read'])]
    public bool $showEmailAddresses = false;

    #[Groups(['course_reporting_configuration:read'])]
    public bool $showCharts = true;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_reporting_configuration:read'])]
    public array $groups = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_reporting_configuration:read'])]
    public array $classes = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_reporting_configuration:read'])]
    public array $teachers = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_reporting_configuration:read'])]
    public array $sessions = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_reporting_configuration:read'])]
    public array $extraFields = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_reporting_configuration:read'])]
    public array $configuredExercises = [];

    /**
     * @var int[]
     */
    #[Groups(['course_reporting_configuration:read'])]
    public array $hiddenColumnIndexes = [];

    /**
     * @var string[]
     */
    #[Groups(['course_reporting_configuration:read'])]
    public array $defaultExtraFieldVariables = [];

    /**
     * @var int[]
     */
    #[Groups(['course_reporting_configuration:read'])]
    public array $inactiveDayOptions = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_reporting_configuration:read'])]
    public array $tabs = [];
}
