<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseReporting;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\CourseReporting\CourseReportingSectionProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/course-reporting/activity',
            name: 'get_course_reporting_activity',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer']),
                'gid' => new QueryParameter(schema: ['type' => 'integer']),
                'page' => new QueryParameter(schema: ['type' => 'integer']),
                'itemsPerPage' => new QueryParameter(schema: ['type' => 'integer']),
                'keyword' => new QueryParameter(schema: ['type' => 'string']),
                'sort' => new QueryParameter(schema: ['type' => 'string']),
                'direction' => new QueryParameter(schema: ['type' => 'string']),
                'startDate' => new QueryParameter(schema: ['type' => 'string', 'format' => 'date']),
                'mode' => new QueryParameter(schema: ['type' => 'string']),
                'userId' => new QueryParameter(schema: ['type' => 'integer']),
                'peerUserId' => new QueryParameter(schema: ['type' => 'integer']),
            ],
            provider: CourseReportingSectionProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/course-reporting/groups',
            name: 'get_course_reporting_groups',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer']),
                'gid' => new QueryParameter(schema: ['type' => 'integer']),
                'page' => new QueryParameter(schema: ['type' => 'integer']),
                'itemsPerPage' => new QueryParameter(schema: ['type' => 'integer']),
                'keyword' => new QueryParameter(schema: ['type' => 'string']),
                'sort' => new QueryParameter(schema: ['type' => 'string']),
                'direction' => new QueryParameter(schema: ['type' => 'string']),
                'startDate' => new QueryParameter(schema: ['type' => 'string', 'format' => 'date']),
                'mode' => new QueryParameter(schema: ['type' => 'string']),
                'userId' => new QueryParameter(schema: ['type' => 'integer']),
                'peerUserId' => new QueryParameter(schema: ['type' => 'integer']),
            ],
            provider: CourseReportingSectionProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/course-reporting/resources',
            name: 'get_course_reporting_resources',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer']),
                'gid' => new QueryParameter(schema: ['type' => 'integer']),
                'page' => new QueryParameter(schema: ['type' => 'integer']),
                'itemsPerPage' => new QueryParameter(schema: ['type' => 'integer']),
                'keyword' => new QueryParameter(schema: ['type' => 'string']),
                'sort' => new QueryParameter(schema: ['type' => 'string']),
                'direction' => new QueryParameter(schema: ['type' => 'string']),
                'startDate' => new QueryParameter(schema: ['type' => 'string', 'format' => 'date']),
                'mode' => new QueryParameter(schema: ['type' => 'string']),
                'userId' => new QueryParameter(schema: ['type' => 'integer']),
                'peerUserId' => new QueryParameter(schema: ['type' => 'integer']),
            ],
            provider: CourseReportingSectionProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/course-reporting/tools',
            name: 'get_course_reporting_tools',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer']),
                'gid' => new QueryParameter(schema: ['type' => 'integer']),
                'page' => new QueryParameter(schema: ['type' => 'integer']),
                'itemsPerPage' => new QueryParameter(schema: ['type' => 'integer']),
                'keyword' => new QueryParameter(schema: ['type' => 'string']),
                'sort' => new QueryParameter(schema: ['type' => 'string']),
                'direction' => new QueryParameter(schema: ['type' => 'string']),
                'startDate' => new QueryParameter(schema: ['type' => 'string', 'format' => 'date']),
                'mode' => new QueryParameter(schema: ['type' => 'string']),
                'userId' => new QueryParameter(schema: ['type' => 'integer']),
                'peerUserId' => new QueryParameter(schema: ['type' => 'integer']),
            ],
            provider: CourseReportingSectionProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/course-reporting/exams',
            name: 'get_course_reporting_exams',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer']),
                'gid' => new QueryParameter(schema: ['type' => 'integer']),
                'page' => new QueryParameter(schema: ['type' => 'integer']),
                'itemsPerPage' => new QueryParameter(schema: ['type' => 'integer']),
                'keyword' => new QueryParameter(schema: ['type' => 'string']),
                'sort' => new QueryParameter(schema: ['type' => 'string']),
                'direction' => new QueryParameter(schema: ['type' => 'string']),
                'startDate' => new QueryParameter(schema: ['type' => 'string', 'format' => 'date']),
                'mode' => new QueryParameter(schema: ['type' => 'string']),
                'userId' => new QueryParameter(schema: ['type' => 'integer']),
                'peerUserId' => new QueryParameter(schema: ['type' => 'integer']),
                'score' => new QueryParameter(schema: ['type' => 'integer']),
                'exerciseId' => new QueryParameter(schema: ['type' => 'integer']),
            ],
            provider: CourseReportingSectionProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/course-reporting/audit',
            name: 'get_course_reporting_audit',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer']),
                'gid' => new QueryParameter(schema: ['type' => 'integer']),
                'page' => new QueryParameter(schema: ['type' => 'integer']),
                'itemsPerPage' => new QueryParameter(schema: ['type' => 'integer']),
                'keyword' => new QueryParameter(schema: ['type' => 'string']),
                'sort' => new QueryParameter(schema: ['type' => 'string']),
                'direction' => new QueryParameter(schema: ['type' => 'string']),
                'startDate' => new QueryParameter(schema: ['type' => 'string', 'format' => 'date']),
                'mode' => new QueryParameter(schema: ['type' => 'string']),
                'userId' => new QueryParameter(schema: ['type' => 'integer']),
                'peerUserId' => new QueryParameter(schema: ['type' => 'integer']),
            ],
            provider: CourseReportingSectionProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/course-reporting/learning-paths',
            name: 'get_course_reporting_learning_paths',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer']),
                'gid' => new QueryParameter(schema: ['type' => 'integer']),
                'page' => new QueryParameter(schema: ['type' => 'integer']),
                'itemsPerPage' => new QueryParameter(schema: ['type' => 'integer']),
                'keyword' => new QueryParameter(schema: ['type' => 'string']),
                'sort' => new QueryParameter(schema: ['type' => 'string']),
                'direction' => new QueryParameter(schema: ['type' => 'string']),
                'startDate' => new QueryParameter(schema: ['type' => 'string', 'format' => 'date']),
                'mode' => new QueryParameter(schema: ['type' => 'string']),
                'userId' => new QueryParameter(schema: ['type' => 'integer']),
                'peerUserId' => new QueryParameter(schema: ['type' => 'integer']),
            ],
            provider: CourseReportingSectionProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/course-reporting/total-time',
            name: 'get_course_reporting_total_time',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer']),
                'gid' => new QueryParameter(schema: ['type' => 'integer']),
                'page' => new QueryParameter(schema: ['type' => 'integer']),
                'itemsPerPage' => new QueryParameter(schema: ['type' => 'integer']),
                'keyword' => new QueryParameter(schema: ['type' => 'string']),
                'sort' => new QueryParameter(schema: ['type' => 'string']),
                'direction' => new QueryParameter(schema: ['type' => 'string']),
                'startDate' => new QueryParameter(schema: ['type' => 'string', 'format' => 'date']),
                'mode' => new QueryParameter(schema: ['type' => 'string']),
                'userId' => new QueryParameter(schema: ['type' => 'integer']),
                'peerUserId' => new QueryParameter(schema: ['type' => 'integer']),
            ],
            provider: CourseReportingSectionProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/course-reporting/session',
            name: 'get_course_reporting_session',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer']),
                'gid' => new QueryParameter(schema: ['type' => 'integer']),
                'page' => new QueryParameter(schema: ['type' => 'integer']),
                'itemsPerPage' => new QueryParameter(schema: ['type' => 'integer']),
                'keyword' => new QueryParameter(schema: ['type' => 'string']),
                'sort' => new QueryParameter(schema: ['type' => 'string']),
                'direction' => new QueryParameter(schema: ['type' => 'string']),
                'startDate' => new QueryParameter(schema: ['type' => 'string', 'format' => 'date']),
                'mode' => new QueryParameter(schema: ['type' => 'string']),
                'userId' => new QueryParameter(schema: ['type' => 'integer']),
                'peerUserId' => new QueryParameter(schema: ['type' => 'integer']),
            ],
            provider: CourseReportingSectionProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/course-reporting/messages',
            name: 'get_course_reporting_messages',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer']),
                'gid' => new QueryParameter(schema: ['type' => 'integer']),
                'page' => new QueryParameter(schema: ['type' => 'integer']),
                'itemsPerPage' => new QueryParameter(schema: ['type' => 'integer']),
                'keyword' => new QueryParameter(schema: ['type' => 'string']),
                'sort' => new QueryParameter(schema: ['type' => 'string']),
                'direction' => new QueryParameter(schema: ['type' => 'string']),
                'startDate' => new QueryParameter(schema: ['type' => 'string', 'format' => 'date']),
                'mode' => new QueryParameter(schema: ['type' => 'string']),
                'userId' => new QueryParameter(schema: ['type' => 'integer']),
                'peerUserId' => new QueryParameter(schema: ['type' => 'integer']),
            ],
            provider: CourseReportingSectionProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
    ],
    normalizationContext: ['groups' => ['course_reporting_section:read']],
)]
final class CourseReportingSection
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_reporting_section:read'])]
    public string $id = 'course_reporting_section';

    #[Groups(['course_reporting_section:read'])]
    public string $section = '';

    #[Groups(['course_reporting_section:read'])]
    public string $title = '';

    #[Groups(['course_reporting_section:read'])]
    public int $total = 0;

    #[Groups(['course_reporting_section:read'])]
    public int $page = 1;

    #[Groups(['course_reporting_section:read'])]
    public int $itemsPerPage = 20;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_reporting_section:read'])]
    public array $summary = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_reporting_section:read'])]
    public array $columns = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_reporting_section:read'])]
    public array $items = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_reporting_section:read'])]
    public array $sections = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['course_reporting_section:read'])]
    public array $meta = [];
}
