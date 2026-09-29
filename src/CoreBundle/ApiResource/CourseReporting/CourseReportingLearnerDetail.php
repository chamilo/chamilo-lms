<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseReporting;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\CourseReporting\CourseReportingLearnerDetailProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/course-reporting/learner-detail',
            name: 'get_course_reporting_learner_detail',
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

                'userId' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Learner identifier',
                    required: true,
                ),
                'limit' => new QueryParameter(schema: ['type' => 'integer']),
            ],
            provider: CourseReportingLearnerDetailProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
    ],
    normalizationContext: ['groups' => ['course_reporting_learner_detail:read']],
)]
final class CourseReportingLearnerDetail
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_reporting_learner_detail:read'])]
    public string $id = 'course_reporting_learner_detail';

    /**
     * @var array<string, mixed>
     */
    #[Groups(['course_reporting_learner_detail:read'])]
    public array $user = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_reporting_learner_detail:read'])]
    public array $downloads = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_reporting_learner_detail:read'])]
    public array $forumThreads = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_reporting_learner_detail:read'])]
    public array $forumPosts = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_reporting_learner_detail:read'])]
    public array $courseAccess = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_reporting_learner_detail:read'])]
    public array $resourceAccess = [];
}
