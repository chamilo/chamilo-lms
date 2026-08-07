<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseSession;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\CourseSession\CourseSessionUserListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseSessionUserList',
    operations: [
        new Get(
            uriTemplate: '/course-sessions/{sessionId}/users',
            openapi: new Operation(
                summary: 'Registered or available students for a manageable session',
                parameters: [
                    new Parameter(name: 'sessionId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'view', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'scope', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'search', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'firstLetter', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'page', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'itemsPerPage', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'sort', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'order', in: 'query', required: false, schema: ['type' => 'string']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_session_user_list',
            provider: CourseSessionUserListProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['course_session_user_list:read']],
)]
final class CourseSessionUserList
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_session_user_list:read'])]
    public string $id = 'course_session_user_list';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_session_user_list:read'])]
    public array $items = [];

    #[Groups(['course_session_user_list:read'])]
    public int $totalItems = 0;

    #[Groups(['course_session_user_list:read'])]
    public int $sessionId = 0;

    #[Groups(['course_session_user_list:read'])]
    public string $view = 'registered';

    #[Groups(['course_session_user_list:read'])]
    public string $scope = 'all';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_session_user_list:read'])]
    public array $profilingFields = [];

    public function getId(): string
    {
        return $this->id;
    }
}
