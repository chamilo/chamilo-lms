<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseSession;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\CourseSession\CourseSessionOverviewProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseSessionOverview',
    operations: [
        new Get(
            uriTemplate: '/course-sessions/{sessionId}/overview',
            openapi: new Operation(
                summary: 'Overview of a manageable session',
                parameters: [
                    new Parameter(name: 'sessionId', in: 'path', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_session_overview',
            provider: CourseSessionOverviewProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['course_session_overview:read']],
)]
final class CourseSessionOverview
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_session_overview:read'])]
    public string $id = 'course_session_overview';

    /**
     * @var array<string, mixed>
     */
    #[Groups(['course_session_overview:read'])]
    public array $session = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_session_overview:read'])]
    public array $courses = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_session_overview:read'])]
    public array $users = [];

    #[Groups(['course_session_overview:read'])]
    public bool $canManageUsers = false;

    #[Groups(['course_session_overview:read'])]
    public bool $canManageUserCourses = false;

    public function getId(): string
    {
        return $this->id;
    }
}
