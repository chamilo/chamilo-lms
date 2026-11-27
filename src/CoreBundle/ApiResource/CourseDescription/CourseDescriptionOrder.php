<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseDescription;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\CourseDescription\CourseDescriptionOrderProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/course-description/reorder',
            status: 200,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_SESSION_MANAGER') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            read: false,
            name: 'reorder_course_description',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Course identifier', required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Session identifier'),
            ],
            processor: CourseDescriptionOrderProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['course_description_order:read']],
    denormalizationContext: ['groups' => ['course_description_order:write']],
)]
final class CourseDescriptionOrder
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_description_order:read', 'course_description_order:write'])]
    public int $iid = 0;

    #[Groups(['course_description_order:write'])]
    public string $direction = '';

    #[Groups(['course_description_order:read'])]
    public bool $saved = false;

    public function getIid(): int
    {
        return $this->iid;
    }
}
