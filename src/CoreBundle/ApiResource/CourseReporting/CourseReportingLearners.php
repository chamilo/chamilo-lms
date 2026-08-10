<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseReporting;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\CourseReporting\CourseReportingLearnersProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/course-reporting/learners',
            name: 'get_course_reporting_learners',
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

                'page' => new QueryParameter(schema: ['type' => 'integer']),
                'itemsPerPage' => new QueryParameter(schema: ['type' => 'integer']),
                'keyword' => new QueryParameter(schema: ['type' => 'string']),
                'groupFilter' => new QueryParameter(schema: ['type' => 'string']),
                'showTeachers' => new QueryParameter(schema: ['type' => 'boolean']),
                'showActiveUsers' => new QueryParameter(schema: ['type' => 'boolean']),
                'sort' => new QueryParameter(schema: ['type' => 'string']),
                'direction' => new QueryParameter(schema: ['type' => 'string']),
                'extraFieldIds' => new QueryParameter(schema: ['type' => 'string']),
                'extraFieldFilters' => new QueryParameter(schema: ['type' => 'string']),
            ],
            provider: CourseReportingLearnersProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
    ],
    normalizationContext: ['groups' => ['course_reporting_learners:read']],
)]
final class CourseReportingLearners
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_reporting_learners:read'])]
    public string $id = 'course_reporting_learners';

    #[Groups(['course_reporting_learners:read'])]
    public int $total = 0;

    #[Groups(['course_reporting_learners:read'])]
    public int $page = 1;

    #[Groups(['course_reporting_learners:read'])]
    public int $itemsPerPage = 20;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_reporting_learners:read'])]
    public array $items = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['course_reporting_learners:read'])]
    public array $groupSummary = [];
}
