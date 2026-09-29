<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseSession;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\CourseSession\CourseSessionListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseSessionList',
    operations: [
        new Get(
            uriTemplate: '/course-sessions/list',
            openapi: new Operation(
                summary: 'Sessions manageable by the authenticated tutor',
                parameters: [
                    new Parameter(name: 'search', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'name', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'courses', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'users', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'category', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'startDate', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'endDate', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'active', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'page', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'itemsPerPage', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'sort', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'order', in: 'query', required: false, schema: ['type' => 'string']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_session_list',
            provider: CourseSessionListProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['course_session_list:read']],
)]
final class CourseSessionList
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_session_list:read'])]
    public string $id = 'course_session_list';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_session_list:read'])]
    public array $items = [];

    #[Groups(['course_session_list:read'])]
    public int $totalItems = 0;

    #[Groups(['course_session_list:read'])]
    public int $active = 1;

    #[Groups(['course_session_list:read'])]
    public bool $canCreate = false;

    #[Groups(['course_session_list:read'])]
    public string $createSessionUrl = '';

    #[Groups(['course_session_list:read'])]
    public string $addToCategoryUrl = '';

    #[Groups(['course_session_list:read'])]
    public string $categoriesUrl = '';

    public function getId(): string
    {
        return $this->id;
    }
}
