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
    shortName: 'UserGradebookResultInCourseAndSession',
    operations: [
        new Get(
            uriTemplate: '/tracking/user_gradebook_result_in_course_and_session',
            openapi: new Operation(
                summary: 'Global gradebook result for a user in a course and optional session',
                parameters: [
                    new Parameter(name: 'userId', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'courseId', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sessionId', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_USER')",
            name: 'tracking_user_gradebook_result_in_course_and_session',
            provider: UserTrackingMetricStateProvider::class,
        ),
    ],
)]
final class UserGradebookResultInCourseAndSession
{
    public function __construct(
        public float $score = 0.0,
        public float $max = 0.0,
        public float $percentage = 0.0,
    ) {}

    #[ApiProperty(identifier: true, readable: false, writable: false)]
    public function getId(): string
    {
        return 'user-gradebook-result-in-course-and-session';
    }
}
