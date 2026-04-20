<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\CourseTrackingMetricStateProvider;

#[ApiResource(
    shortName: 'CourseAverageMetricInSession',
    operations: [
        new Get(
            uriTemplate: '/tracking/course_average_score_in_session',
            openapi: new Operation(
                summary: 'Average course score in an optional session',
                parameters: [
                    new Parameter(name: 'courseId', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sessionId', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_USER')",
            name: 'tracking_course_average_score_in_session',
            provider: CourseTrackingMetricStateProvider::class,
        ),
        new Get(
            uriTemplate: '/tracking/course_average_progress_in_session',
            openapi: new Operation(
                summary: 'Average course progress in an optional session',
                parameters: [
                    new Parameter(name: 'courseId', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sessionId', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_USER')",
            name: 'tracking_course_average_progress_in_session',
            provider: CourseTrackingMetricStateProvider::class,
        ),
    ],
)]
final class CourseAverageMetricInSession
{
    public function __construct(
        public float $avg = 0.0,
        public int $participants = 0,
    ) {}

    #[ApiProperty(readable: false, writable: false, identifier: true)]
    public function getId(): string
    {
        return 'course-average-metric-in-session';
    }
}
