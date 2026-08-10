<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseSession;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\CourseSession\CourseSessionUserCoursesProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseSessionUserCourses',
    operations: [
        new Get(
            uriTemplate: '/course-sessions/{sessionId}/users/{userId}/courses',
            openapi: new Operation(
                summary: 'Course access restrictions for a session student',
                parameters: [
                    new Parameter(name: 'sessionId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'userId', in: 'path', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_session_user_courses',
            provider: CourseSessionUserCoursesProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['course_session_user_courses:read']],
)]
final class CourseSessionUserCourses
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_session_user_courses:read'])]
    public string $id = 'course_session_user_courses';

    #[Groups(['course_session_user_courses:read'])]
    public int $sessionId = 0;

    #[Groups(['course_session_user_courses:read'])]
    public string $sessionTitle = '';

    /**
     * @var array<string, mixed>
     */
    #[Groups(['course_session_user_courses:read'])]
    public array $user = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_session_user_courses:read'])]
    public array $courses = [];

    public function getId(): string
    {
        return $this->id;
    }
}
