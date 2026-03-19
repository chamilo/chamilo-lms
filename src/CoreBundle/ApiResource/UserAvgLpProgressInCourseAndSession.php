<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\UserTrackingMetricStateProvider;

#[ApiResource(
    shortName: 'UserAvgLpProgressInCourseAndSession',
    operations: [
        new Get(
            uriTemplate: '/tracking/user_avg_lp_progress_in_course_and_session',
            openapi: new Operation(
                summary: 'Average LP progress for a user in a course and optional session',
                parameters: [
                    new Parameter(name: 'userId', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'courseId', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sessionId', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_USER')",
            name: 'tracking_user_avg_lp_progress_in_course_and_session',
            provider: UserTrackingMetricStateProvider::class,
        ),
    ],
)]
final class UserAvgLpProgressInCourseAndSession
{
    public function __construct(
        public float $avg = 0.0,
        public int $count = 0,
    ) {}

    #[ApiProperty(readable: false, writable: false, identifier: true)]
    public function getId(): string
    {
        return 'user-avg-lp-progress-in-course-and-session';
    }
}
